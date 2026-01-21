# HIP Ad Manager

A comprehensive WordPress plugin for Google Ad Manager integration, specifically designed for headless WordPress projects.

## Features

- **Custom Post Type for Ad Slots**: Manage all your ad slots through a familiar WordPress interface
- **CSV Import**: Import ad slots directly from Google Ad Manager CSV exports
- **REST API**: Full-featured REST API for headless WordPress integration
- **Targeting Rules**: Advanced targeting and display rules
- **Responsive Size Mappings**: Built-in responsive ad size configurations
- **Lazy Loading**: Support for lazy loading ads
- **Device-Specific Ads**: Target specific devices (mobile, tablet, desktop)
- **Placement Management**: Organize ads by placement (header, sidebar, in-content, footer, etc.)

## Installation

1. Upload the `hip-admanager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **HIP Ad Manager** → **Settings** to configure your network code

## Configuration

### Basic Settings

1. Go to **HIP Ad Manager** → **Settings**
2. Enter your Google Ad Manager **Network Code** (e.g., 273585429)
3. Enter your **Site Name** for targeting (e.g., kidsgourmet)
4. Configure additional options:
   - Enable/disable lazy loading
   - Enable/disable single request mode
   - Set global targeting parameters (JSON format)

### Example Settings

```json
{
  "networkCode": "273585429",
  "siteName": "kidsgourmet",
  "enableLazyLoad": true,
  "enableSingleRequest": true,
  "globalTargeting": {
    "site": "kidsgourmet"
  }
}
```

## CSV Import

### Importing Ad Slots from Google Ad Manager

1. Export your ad units from Google Ad Manager as CSV
2. Go to **HIP Ad Manager** → **Import**
3. Upload your CSV file
4. Preview the ad slots that will be created
5. Confirm the import

### CSV Format

The CSV should contain the following columns:

```csv
#ID,Parent Id,Code,Name,Sizes:,Description,Enabled for AdSense,Placements,Target Window,Labels
```

Example:

```csv
#ID,Parent Id,Code,Name,Sizes:,Description,Enabled for AdSense,Placements,Target Window,Labels
23335123404,23335085636,KidsGourmet.com.tr/kidsgourmet_160x600_wideskyscraper_left,kidsgourmet_160x600_WideSkyscraper_Left,120x600; 160x600; 161x600,,no,,_blank,
23335123656,23335085636,KidsGourmet.com.tr/kidsgourmet_300x250_mediumrectangle,kidsgourmet_300x250_MediumRectangle,250x250; 300x250; 336x280,,no,,_blank,
```

### Import Logic

During import, the plugin automatically:

- **Parses ad sizes**: Converts `120x600; 160x600` to `[[120,600], [160,600]]`
- **Determines placement**: Based on ad name keywords (leaderboard → header, mediumrectangle → in-content, etc.)
- **Determines device**: Based on ad name (mobile → mobile, otherwise desktop or all)
- **Assigns size mappings**: Based on placement type
- **Creates ad unit path**: Combines network code with ad code

## REST API

### Endpoints

#### Get Configuration

```
GET /wp-json/hip-ads/v1/config
```

Returns global configuration including network code, site settings, and global targeting.

**Response:**

```json
{
  "networkCode": "273585429",
  "siteName": "kidsgourmet",
  "enableLazyLoad": true,
  "enableSingleRequest": true,
  "globalTargeting": {
    "site": "kidsgourmet"
  }
}
```

#### Get All Active Slots

```
GET /wp-json/hip-ads/v1/slots
```

**Query Parameters:**

- `device` - Filter by device (mobile, tablet, desktop, all)
- `placement` - Filter by placement (header, sidebar, in-content, footer, mobile-sticky, interstitial)
- `page_type` - Filter by page type
- `category` - Filter by category

**Response:**

```json
{
  "networkCode": "273585429",
  "enableLazyLoad": true,
  "enableSingleRequest": true,
  "globalTargeting": {
    "site": "kidsgourmet"
  },
  "slots": [
    {
      "id": 123,
      "name": "kidsgourmet_300x250_MediumRectangle",
      "slotId": "23335123656",
      "adUnitPath": "/273585429/KidsGourmet.com.tr/kidsgourmet_300x250_mediumrectangle",
      "sizes": [[300, 250], [336, 280], [250, 250]],
      "sizeMappings": [
        {
          "viewport": [1024, 0],
          "sizes": [[300, 250], [336, 280]]
        },
        {
          "viewport": [0, 0],
          "sizes": [[300, 250]]
        }
      ],
      "targeting": {},
      "lazyLoad": true,
      "placement": "in-content",
      "device": "desktop",
      "priority": 10
    }
  ]
}
```

#### Get Single Slot

```
GET /wp-json/hip-ads/v1/slots/{id}
```

Returns details for a specific ad slot.

#### Track (Optional)

```
POST /wp-json/hip-ads/v1/track
```

Optional endpoint for tracking impressions and clicks.

## Ad Slot Fields

Each ad slot includes the following metadata:

- **gam_slot_id**: Google Ad Manager Slot ID
- **gam_ad_unit_path**: Full ad unit path
- **gam_sizes**: JSON array of ad sizes (e.g., `[[300, 250], [336, 280]]`)
- **gam_size_mappings**: JSON array of responsive size mappings
- **gam_targeting**: JSON object with slot-level targeting
- **gam_placement**: Placement type (header, sidebar, in-content, footer, mobile-sticky, interstitial)
- **gam_device**: Device targeting (all, mobile, desktop, tablet)
- **gam_lazy_load**: Enable/disable lazy loading (boolean)
- **gam_display_rules**: JSON object with display rules (page types, categories, schedules)
- **gam_priority**: Priority (1-100, lower = higher priority)
- **gam_status**: Status (active, paused, scheduled)

## Size Mappings

The plugin includes predefined responsive size mappings:

### Leaderboard (Header Ads)

```json
[
  { "viewport": [1024, 0], "sizes": [[970, 250], [970, 90], [728, 90]] },
  { "viewport": [768, 0], "sizes": [[728, 90]] },
  { "viewport": [0, 0], "sizes": [[320, 100], [320, 50]] }
]
```

### MPU (Medium Rectangle)

```json
[
  { "viewport": [768, 0], "sizes": [[300, 600], [300, 250], [336, 280]] },
  { "viewport": [0, 0], "sizes": [[300, 250]] }
]
```

### Skyscraper (Sidebar)

```json
[
  { "viewport": [1024, 0], "sizes": [[160, 600], [120, 600]] },
  { "viewport": [0, 0], "sizes": [] }
]
```

### Mobile Sticky

```json
[
  { "viewport": [0, 0], "sizes": [[320, 50], [320, 100]] }
]
```

## Headless Integration Example

### Next.js / React Example

```javascript
// Fetch ad configuration
const fetchAdConfig = async () => {
  const response = await fetch('https://your-wp-site.com/wp-json/hip-ads/v1/slots?device=mobile&placement=header');
  const data = await response.json();
  return data;
};

// Use in component
import { useEffect, useState } from 'react';

export default function AdComponent({ placement, device }) {
  const [adConfig, setAdConfig] = useState(null);

  useEffect(() => {
    const loadAds = async () => {
      const config = await fetchAdConfig();
      setAdConfig(config);
      
      // Initialize GPT
      window.googletag = window.googletag || { cmd: [] };
      googletag.cmd.push(function() {
        // Configure GPT based on config
        googletag.pubads().enableSingleRequest();
        if (config.enableLazyLoad) {
          googletag.pubads().enableLazyLoad();
        }
        
        // Define slots
        config.slots.forEach(slot => {
          const gptSlot = googletag.defineSlot(
            slot.adUnitPath,
            slot.sizes,
            `div-gpt-ad-${slot.id}`
          );
          
          // Add size mappings
          if (slot.sizeMappings && slot.sizeMappings.length > 0) {
            const mapping = googletag.sizeMapping();
            slot.sizeMappings.forEach(map => {
              mapping.addSize(map.viewport, map.sizes);
            });
            gptSlot.defineSizeMapping(mapping.build());
          }
          
          // Add targeting
          if (slot.targeting) {
            Object.keys(slot.targeting).forEach(key => {
              gptSlot.setTargeting(key, slot.targeting[key]);
            });
          }
          
          gptSlot.addService(googletag.pubads());
        });
        
        googletag.enableServices();
      });
    };
    
    loadAds();
  }, []);

  return (
    <div id={`div-gpt-ad-${adConfig?.slots[0]?.id}`}>
      {/* Ad will be rendered here */}
    </div>
  );
}
```

## Hooks & Filters

### Filters

```php
// Modify ad slot data before API response
add_filter('hip_ad_slot_data', function($data, $post_id) {
    // Modify $data
    return $data;
}, 10, 2);

// Modify slots query arguments
add_filter('hip_ad_slots_query_args', function($args) {
    // Modify $args
    return $args;
});

// Modify imported slot data
add_filter('hip_ad_import_slot_data', function($slot_data, $csv_row) {
    // Modify $slot_data
    return $slot_data;
}, 10, 2);
```

### Actions

```php
// After slot is imported
add_action('hip_ad_slot_imported', function($post_id, $slot_data) {
    // Do something
}, 10, 2);

// After settings are saved
add_action('hip_ad_settings_saved', function($settings) {
    // Do something
});
```

## Requirements

- PHP 7.4 or higher
- WordPress 5.8 or higher

## Support

For issues and questions, please visit the [GitHub repository](https://github.com/selim-create/hip-admanager).

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Custom Post Type for ad slots
- CSV import functionality
- REST API endpoints
- Admin interface
- Responsive size mappings
- Targeting and display rules