# Changelog

All notable changes to HIP Ad Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-21

### Added
- Initial release of HIP Ad Manager
- Custom Post Type (`hip_ad_slot`) for managing ad slots
- CSV import functionality from Google Ad Manager exports
- Automatic placement detection from ad names (header, sidebar, in-content, footer, mobile-sticky, interstitial)
- Automatic device detection from ad names (mobile, tablet, desktop, all)
- Automatic size parsing from CSV format
- REST API endpoints:
  - `GET /wp-json/hip-ads/v1/config` - Get global configuration
  - `GET /wp-json/hip-ads/v1/slots` - Get all active ad slots with filtering
  - `GET /wp-json/hip-ads/v1/slots/{id}` - Get single ad slot
  - `POST /wp-json/hip-ads/v1/track` - Track impressions/clicks (optional)
- Admin dashboard with statistics and quick links
- Settings page for network configuration
- Import page with CSV upload, preview, and confirmation
- Ad slot editing with metaboxes:
  - GAM Information (Slot ID, Ad Unit Path)
  - Ad Sizes (sizes array, size mappings)
  - Targeting (key-value pairs)
  - Display Rules (placement, device, lazy load, display rules)
  - Status & Priority
- Predefined responsive size mappings:
  - Leaderboard (header ads)
  - MPU (medium rectangle)
  - Skyscraper (sidebar)
  - Mobile Sticky
- Targeting and display rules system
- Lazy loading support
- Device-specific ad targeting
- Priority-based ad ordering
- Admin CSS for dashboard styling
- Admin JavaScript for JSON validation
- Sample CSV template for import
- Comprehensive README with installation and usage instructions
- Integration guide (INTEGRATION.md) with examples for Next.js, React, and Vue.js
- Composer support for PSR-4 autoloading
- WordPress Coding Standards compliance
- Proper sanitization and validation
- Nonce security checks
- Capability checks for admin functions
- Internationalization ready (text domain: hip-admanager)

### Security
- All input sanitized and validated
- Nonce verification for form submissions
- Capability checks for admin actions
- JSON parsing with error handling
- File upload validation (CSV only)

[1.0.0]: https://github.com/selim-create/hip-admanager/releases/tag/v1.0.0
