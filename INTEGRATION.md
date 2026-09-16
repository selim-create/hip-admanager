# HIP Ad Manager v2 — Headless Integration Guide

This guide defines the runtime contract between HIP Ad Manager v2 and a headless frontend such as Next.js.

## Principle

HIP Ad Manager is a **configuration/control plane**. It does not inject executable GAM tags into content or return arbitrary scripts from WordPress.

The frontend is the **runtime/render plane**. It loads GPT once, fetches validated configuration, defines slots, handles SPA route transitions, renders ads and performs any permitted refresh operations.

## 1. Load GPT once

Use Google’s official Publisher Tag URL:

```html
<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
```

Do not proxy or self-host GPT.

In Next.js, load this once near the application root. Keep the library alive across client-side navigation.

## 2. Fetch configuration

```ts
const API = 'https://api.example.com/wp-json/hip-ads/v1';

export async function getAdsConfig() {
  const response = await fetch(`${API}/config`, {
    headers: { Accept: 'application/json' },
    next: { revalidate: 300 },
  });

  if (!response.ok) return null;
  return response.json();
}
```

For page-specific delivery you may query `/slots` instead:

```ts
const params = new URLSearchParams({
  page_type: 'article',
  category: 'moda',
  device: 'desktop',
});

const response = await fetch(`${API}/slots?${params}`);
```

Do not trust frontend filters as security controls; they are delivery hints for public inventory configuration.

## 3. Current GPT configuration

Use `googletag.setConfig()` for page-level GPT settings.

```ts
declare global {
  interface Window {
    googletag: any;
  }
}

export function configureGpt(config: any) {
  window.googletag = window.googletag || { cmd: [] };

  window.googletag.cmd.push(() => {
    window.googletag.setConfig({
      singleRequest: Boolean(config.gpt?.singleRequest),
      targeting: config.globalTargeting || {},
      lazyLoad: config.gpt?.lazyLoad || undefined,
    });
  });
}
```

Do not build new integrations around deprecated `pubads().enableSingleRequest()` or `pubads().enableLazyLoad()` calls.

## 4. Define slots from stable keys

The API separates:

- `key`: stable frontend/internal slot key
- `placementKey`: page placement contract
- `adUnitPath`: GAM inventory path
- `inventoryId`: optional GAM inventory identifier

Example slot definition:

```ts
export function defineHipSlot(slot: any) {
  let definedSlot: any = null;

  window.googletag.cmd.push(() => {
    const elementId = `hip-ad-${slot.key}`;
    const gptSlot = window.googletag.defineSlot(
      slot.adUnitPath,
      slot.sizes,
      elementId,
    );

    if (!gptSlot) return;

    if (slot.sizeMappings?.length) {
      const mapping = window.googletag.sizeMapping();

      for (const item of slot.sizeMappings) {
        mapping.addSize(item.viewport, item.sizes);
      }

      gptSlot.defineSizeMapping(mapping.build());
    }

    for (const [key, value] of Object.entries(slot.targeting || {})) {
      gptSlot.setTargeting(key, value as any);
    }

    gptSlot.addService(window.googletag.pubads());
    definedSlot = gptSlot;
  });

  return definedSlot;
}
```

## 5. Render only into reserved containers

Reserve dimensions before the ad loads to protect CLS.

```tsx
export function AdContainer({ slot }: { slot: any }) {
  const minHeight = slot.responsiveMinHeight?.desktop || slot.minHeight || 0;

  return (
    <div
      className="hip-ad-shell"
      style={{ minHeight }}
      data-slot-key={slot.key}
    >
      <div id={`hip-ad-${slot.key}`} />
    </div>
  );
}
```

For responsive layouts, calculate the appropriate desktop/tablet/mobile reserve height in the frontend without waiting for GPT to load.

## 6. Enable services after defining the initial page batch

For Single Request Architecture, define the relevant slots before enabling services/displaying them.

```ts
window.googletag.cmd.push(() => {
  window.googletag.enableServices();

  for (const slot of pageSlots) {
    window.googletag.display(`hip-ad-${slot.key}`);
  }
});
```

## 7. Next.js / SPA route lifecycle

Client-side navigation does not reload the page. Page-scoped GPT slots therefore need an explicit lifecycle.

Recommended sequence:

1. Before old route slot containers disappear, call `googletag.destroySlots(oldSlots)`.
2. Clear old page-level targeting that should not leak to the new route.
3. Resolve/fetch the new route’s slot configuration.
4. Define the new route’s slots.
5. Apply new route targeting.
6. Display the new slots.

Do not define the same GPT element/slot repeatedly without destroying the previous instance.

## 8. Route targeting

Keep global targeting in the plugin and derive dynamic page targeting in the frontend.

Example:

```ts
window.googletag.cmd.push(() => {
  window.googletag.setConfig({
    targeting: {
      ...config.globalTargeting,
      page_type: 'article',
      category: article.category.key,
      article_id: String(article.id),
    },
  });
});
```

Avoid sending personal or sensitive user information as targeting keys.

## 9. Lazy loading

The plugin returns lazy-load defaults under `config.gpt.lazyLoad`. The frontend applies them to GPT.

The default values are designed as safe starting points, not immutable policy. Measure ad viewability, Core Web Vitals and revenue before tuning them.

## 10. Refresh

Refresh is **off by default** at the slot level.

For configured time/event refresh:

- v2 enforces a minimum 30-second interval;
- visible-only refresh is enabled by default;
- refresh pauses when the browser tab is hidden by default;
- the same inventory must be declared as refreshing in Google Ad Manager.

The frontend owns the runtime refresh trigger.

Example visibility-gated pattern:

```ts
function refreshSlot(gptSlot: any) {
  if (document.visibilityState !== 'visible') return;

  window.googletag.cmd.push(() => {
    window.googletag.pubads().refresh([gptSlot]);
  });
}
```

Do not implement uncontrolled refresh loops.

## 11. No-fill and layout collapse

`collapseEmpty` is returned as a delivery preference. The frontend may use GPT’s current collapse configuration and/or its own shell state after slot render events.

Even when collapse is enabled, reserve enough initial height to prevent content from jumping while the request is pending.

## 12. ads.txt

HIP Ad Manager stores the canonical ads.txt source and exposes it at:

```text
/wp-json/hip-ads/v1/ads-txt
```

For Next.js App Router, create a root `/ads.txt` route that fetches the JSON source and returns `payload.content` as `text/plain`.

Example:

```ts
export async function GET() {
  const response = await fetch(
    'https://api.example.com/wp-json/hip-ads/v1/ads-txt',
    { next: { revalidate: 300 } },
  );

  const payload = response.ok ? await response.json() : { content: '' };

  return new Response(payload.content || '', {
    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
  });
}
```

## 13. Health check

Use:

```text
GET /wp-json/hip-ads/v1/health
```

The endpoint intentionally exposes only operational state needed for deployment checks, not server versions or internal WordPress diagnostics.

## 14. Gutenberg markers

The optional WordPress block renders a stable marker, not ad JavaScript:

```html
<div
  class="hip-ad-injection align-center"
  data-hip-ad-slot-key="article_inline_1"
  data-hip-ad-placement="content"
></div>
```

A headless content renderer can replace/map these markers to its native `AdSlot` component.

## 15. Failure behavior

The frontend should fail closed and preserve UX:

- API unavailable → render no ad, keep layout rules intentional.
- Slot missing → render nothing for that placement.
- GPT blocked → do not retry aggressively.
- Invalid GAM slot → surface in development/debug tooling, not to readers.
- Route navigation → always destroy old route slots.

## 16. Recommended integration boundary

Keep these values in WordPress/API:

- network code
- ad unit path
- stable slot/placement keys
- sizes and responsive mapping
- global/slot targeting
- page/device/category rules
- refresh policy
- lazy-load preference
- CLS reserve heights
- master on/off switch

Keep these responsibilities in the frontend:

- GPT script loading
- consent/measurement gating
- DOM element lifecycle
- route-aware targeting
- `defineSlot`, `display`, `destroySlots`, `refresh`
- viewport observation
- public `/ads.txt` response

This boundary allows AdOps changes without frontend deployments while keeping performance-sensitive JavaScript deterministic and version-controlled.
