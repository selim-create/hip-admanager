# HIP Ad Manager v2

HIP Ad Manager is a headless-first WordPress control plane for Google Ad Manager (GAM). WordPress stores and validates inventory configuration; the frontend owns Google Publisher Tag (GPT) loading and runtime rendering.

## Architecture

### WordPress / HIP Ad Manager

- Inventory and placement management
- GAM ad-unit paths and responsive size mappings
- Page/device/category delivery rules
- Slot and global key-value targeting
- Refresh policy metadata
- ads.txt source content
- Validation, diagnostics, import/export and cache invalidation
- Read-only headless REST API

### Headless frontend

- Loads GPT from Google’s official URL
- Calls the current GPT Config API (`googletag.setConfig`)
- Defines and destroys slots during SPA navigation
- Applies page-context targeting
- Renders, refreshes and observes slots
- Serves public `/ads.txt` from the plugin API source

The plugin never sends executable advertising JavaScript from WordPress to the frontend.

## Admin workflow

HIP Ads provides a dedicated WordPress admin application:

1. **Genel Bakış** — inventory status, system health and API summary.
2. **Reklam Alanları** — searchable/filterable slot list.
3. **Yeni Alan Ekle** — unified slot editor; no legacy metabox maze.
4. **İçe / Dışa Aktar** — dry-run CSV preview, idempotent upsert and JSON backup.
5. **Ayarlar** — GAM network/property, SRA, lazy load, global targeting, cache and ads.txt.
6. **Tanılama** — schema validation, duplicate checks, unsafe refresh warnings and API endpoints.

Existing `hip_ad_slot` posts are preserved. On upgrade, v2 performs a non-destructive migration and adds normalized v2 metadata without deleting legacy fields.

## Slot model

Each slot has separate identifiers:

- `key`: stable internal/frontend key, e.g. `article_inline_1`
- `placementKey`: placement requested by the frontend
- `placementGroup`: `header`, `content`, `sidebar`, `footer`, `overlay`, `other`
- `inventoryId`: optional GAM inventory ID
- `adUnitPath`: full GAM path, e.g. `/1234567/hipinup/article_inline_1`

This separation removes the old ambiguity between WordPress post IDs, GAM inventory IDs and frontend DOM/placement identifiers.

Additional slot fields include sizes, responsive mappings, device, page types, category slugs, targeting, lazy-load preference, CLS reserve heights, schedule, priority and refresh policy.

## REST API

Namespace: `/wp-json/hip-ads/v1`

### `GET /config`

Returns global frontend configuration plus all currently deliverable slots.

### `GET /slots`

Returns active or currently-live scheduled slots. Optional filters:

- `device=desktop|tablet|mobile|all`
- `placement=<placementKey>`
- `placement_group=header|content|sidebar|footer|overlay|other`
- `page_type=home|article|category|search|page`
- `category=<slug>`
- `key=<stableSlotKey>`

### `GET /slots/{id}`

Returns one live slot by internal WordPress ID.

### `GET /health`

Returns a small non-sensitive health payload suitable for deployment checks.

### `GET /ads-txt`

Returns the managed ads.txt source:

```json
{
  "content": "google.com, pub-..., DIRECT, f08c47fec0942fa0",
  "lineCount": 1
}
```

### `POST /cache/clear`

Admin-authenticated maintenance endpoint. Normal saves already invalidate cache automatically.

## Frontend contract

Example config shape:

```json
{
  "schemaVersion": 2,
  "adsEnabled": true,
  "networkCode": "1234567",
  "propertyCode": "hipinup",
  "gpt": {
    "singleRequest": true,
    "collapseEmpty": true,
    "lazyLoad": {
      "fetchMarginPercent": 500,
      "renderMarginPercent": 200,
      "mobileScaling": 2
    }
  },
  "globalTargeting": {
    "site": "hipinup"
  },
  "slots": []
}
```

Frontend implementations should use the current GPT Config API instead of deprecated per-service configuration methods:

```js
window.googletag = window.googletag || { cmd: [] };

googletag.cmd.push(() => {
  googletag.setConfig({
    singleRequest: config.gpt.singleRequest,
    targeting: config.globalTargeting,
    lazyLoad: config.gpt.lazyLoad || undefined,
  });

  for (const slot of config.slots) {
    const gptSlot = googletag.defineSlot(
      slot.adUnitPath,
      slot.sizes,
      `hip-ad-${slot.key}`,
    );

    if (!gptSlot) continue;

    if (slot.sizeMappings?.length) {
      const builder = googletag.sizeMapping();
      for (const mapping of slot.sizeMappings) {
        builder.addSize(mapping.viewport, mapping.sizes);
      }
      gptSlot.defineSizeMapping(builder.build());
    }

    for (const [key, value] of Object.entries(slot.targeting || {})) {
      gptSlot.setTargeting(key, value);
    }

    gptSlot.addService(googletag.pubads());
  }

  googletag.enableServices();
});
```

Load GPT only from Google’s official source:

```html
<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
```

Do not proxy, self-host or cache `gpt.js` yourself.

For Next.js/App Router navigation, destroy page-specific GPT slots before their DOM containers disappear, then define the new route’s slots. Keep the GPT library itself loaded once for the application lifecycle.

## Refresh safety

Refresh is off by default. For time/event-based refresh:

- v2 enforces a minimum 30-second interval;
- visibility gating is enabled by default;
- background-tab pause is enabled by default;
- the inventory must also be declared as refreshing in Google Ad Manager, matching actual page behavior.

User-action refresh may use a different trigger model, but the frontend remains responsible for refreshing only when that declared trigger actually occurs.

## CSV import

The importer accepts typical GAM CSV columns such as `ID`, `Code`, `Name` and `Sizes`; header punctuation/case variants are normalized.

Import is intentionally two-phase:

1. Upload → parse and validate.
2. Preview → each row is classified as `CREATE`, `UPDATE` or `INVALID`.
3. Confirm → changes are committed.

Existing slots match by ad-unit path first, stable key second. Re-importing the same inventory updates instead of creating duplicates. GAM-owned fields are refreshed while custom targeting/delivery overrides are preserved.

Responsive mappings created during import are restricted to sizes actually declared on that GAM ad unit.

## Cache model

The API uses versioned transient cache keys. Every slot/settings mutation increments `hip_ad_cache_version`, making all previous cached responses unreachable immediately without wildcard transient deletion.

## Security

- Admin mutations require `manage_options` plus WordPress nonces.
- Public endpoints are read-only and contain no credentials.
- Debug mode exposes only safe cache state, not PHP/WordPress environment details.
- CSV uploads are extension/size limited and parsed from the temporary upload.
- All stored data is normalized/sanitized through one schema service.

## Gutenberg

The optional `Ad Slot` block stores a stable slot key, not executable ad code. It renders a semantic marker such as:

```html
<div
  class="hip-ad-injection align-center"
  data-hip-ad-slot-key="article_inline_1"
  data-hip-ad-placement="content"
></div>
```

The headless frontend may translate this marker into its own `AdSlot` component during content rendering.

## Requirements

- WordPress 6.2+
- PHP 7.4+
- A headless frontend capable of running Google Publisher Tag

## Quality gate

Pull requests run:

- PHP syntax validation on every PHP file
- Node syntax validation for admin JavaScript
- required v2 file checks

## Upgrade from v1

Activation or first v2 load runs a non-destructive migration. Old metadata remains in place for compatibility. The Diagnostics screen can rerun normalization and surfaces duplicates/invalid legacy records for manual correction rather than silently overwriting them.
