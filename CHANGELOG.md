# Changelog

All notable changes to HIP Ad Manager are documented here.

The project follows Semantic Versioning.

## [2.0.0] - 2026-09-16

### Rebuilt
- Replaced the fragmented v1 metabox workflow with a dedicated HIP Ads admin application.
- Introduced a canonical schema v2 and a repository layer as the single source of truth for slot reads/writes.
- Kept the `hip_ad_slot` post type as an internal persistence layer for backwards compatibility while removing the native CPT editing UI.
- Added non-destructive migration from legacy v1 metadata and settings.

### Admin UX
- New dashboard with inventory health, slot statistics, quick actions and diagnostics.
- New searchable/filterable ad-slot list.
- New single-screen slot editor for GAM identity, placement, sizes, responsive mappings, page/category/device rules, targeting, CLS reservations, scheduling, refresh policy, status and notes.
- New settings screen for master enable/disable, network/property code, GPT config, lazy-load config, targeting, caching, debug and ads.txt.
- New diagnostics screen with validation, duplicate detection and migration/cache actions.
- New import/export screen with user-scoped previews and JSON backups.
- Replaced raw JSON editing with structured repeatable controls.
- Removed jQuery dependency from the plugin admin UI.

### Data & Safety
- Stable frontend slot keys are now separate from GAM inventory IDs and ad-unit paths.
- Duplicate slot-key and ad-unit-path validation.
- Duplicated slots are created paused so placeholder GAM paths cannot accidentally serve.
- Versioned cache invalidation.
- Active/paused/scheduled delivery model with schedule enforcement.
- Device, page-type, category and placement filtering.
- Responsive min-height reservations for CLS protection.
- Refresh validation with visibility/background-tab safeguards and 30-second minimum for time/event refresh.

### Import
- Rebuilt GAM CSV importer as validate → preview → create/update/skip.
- Existing inventory is updated by ad-unit path or stable key instead of blindly duplicated.
- Imported size mappings are restricted to creative sizes declared by GAM CSV data.
- Import preview/result state is isolated per WordPress user.

### Headless API
- Versioned schema response for `/wp-json/hip-ads/v1/config` and `/slots`.
- Added `/health` endpoint.
- Public responses contain only normalized live inventory.
- ETag, cache headers and cache-version metadata.
- Debug responses no longer expose PHP/WordPress version details.
- Gutenberg marker block now uses stable slot keys with legacy numeric-ID fallback.
- GPT runtime is intentionally delegated to the headless frontend; WordPress acts as the inventory control plane.

### Quality
- Added GitHub Actions PHP syntax and admin JavaScript checks.
- Removed unused v1 admin views/metaboxes and obsolete block build artifacts.
- Updated integration documentation for the modern GPT Config API and SPA slot lifecycle.

## [1.0.0] - 2026-01-21

### Added
- Initial HIP Ad Manager release.
- `hip_ad_slot` custom post type.
- GAM CSV import.
- REST configuration and slot endpoints.
- Legacy metabox-based admin interface.
- Responsive size mappings, targeting, lazy loading and device rules.

[2.0.0]: https://github.com/selim-create/hip-admanager/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/selim-create/hip-admanager/releases/tag/v1.0.0
