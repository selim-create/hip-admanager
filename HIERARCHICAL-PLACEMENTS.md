# HIP Ad Manager v2 — Placement Model

HIP Ads v2 separates three concepts that were mixed together in v1:

1. **Stable slot key** — the permanent frontend identifier, for example `article_inline_1`.
2. **Placement key** — the logical place in the product where inventory is mounted. It may equal the slot key, but does not have to.
3. **Placement group** — a broad operational group used for filtering and API queries.

Google Ad Manager numeric inventory IDs and ad-unit paths are not frontend placement identifiers.

## Placement groups

The canonical groups are:

- `header`
- `content`
- `sidebar`
- `footer`
- `overlay`
- `other`

Placement keys are intentionally free-form stable keys. Recommended examples:

### Header
- `home_top_leaderboard`
- `archive_top_leaderboard`
- `article_top_billboard`

### Content
- `home_mid_1`
- `article_inline_1`
- `article_inline_2`
- `archive_in_feed_1`
- `article_end`

### Sidebar
- `article_sidebar_top`
- `article_sidebar_sticky`
- `archive_sidebar_top`

### Footer
- `site_footer_banner`

### Overlay
- `mobile_anchor`
- `interstitial`

## Why keys are not hard-coded by the plugin

The plugin should not decide the product's visual layout. The headless frontend owns physical placement. WordPress/GAM control which inventory is assigned to that placement.

For example, the frontend can render:

```tsx
<AdSlot placement="article_inline_1" />
```

The REST request can then resolve live inventory for that placement:

```text
GET /wp-json/hip-ads/v1/slots?placement=article_inline_1&page_type=article
```

This allows AdOps to change the GAM ad-unit path, sizes, targeting, schedule, status or device rules without a frontend deployment.

## Stable slot key vs placement key

For simple properties, use the same value for both:

```text
key: article_inline_1
placement_key: article_inline_1
```

For advanced setups, multiple inventory variants can share a placement concept while keeping independent stable keys. Example:

```text
key: article_inline_1_desktop
placement_key: article_inline_1

device: desktop
```

```text
key: article_inline_1_mobile
placement_key: article_inline_1

device: mobile
```

The frontend requests the placement and device context; HIP Ads returns the matching live slot set.

## Page types

Canonical page-type filters are:

- `all`
- `home`
- `article`
- `category`
- `search`
- `page`

If `all` is selected, the slot is eligible for every page type.

## Category rules

Category values are WordPress/category slugs. An empty category list means there is no category restriction.

Examples:

```text
moda
seyahat
wellness
```

## Device rules

Canonical device modes:

- `all`
- `desktop`
- `tablet`
- `mobile`

Responsive size mappings remain separate from device eligibility. A slot can be `all` devices and still use breakpoint-specific creative sizes.

## Ordering

`priority` is an operational ordering field from 1–100. Lower values are returned first. It does not replace GAM line-item priority.

## Scheduling

A slot can be:

- `active`
- `paused`
- `scheduled`

Active and scheduled slots are returned by the public API only when their optional start/end window is currently live. Paused slots are never returned as public inventory.

## Migration from v1

Existing `gam_placement` values are mapped non-destructively to v2 placement groups. Legacy metadata remains readable for backwards compatibility, but all new admin writes use the canonical v2 schema.
