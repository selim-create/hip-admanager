# HIP Ad Manager - Quick Start Examples

## Example 1: Basic Setup

### WordPress Admin Setup

1. **Install the Plugin**
   ```bash
   # Upload to wp-content/plugins/hip-admanager
   # Or install via WordPress admin
   ```

2. **Activate & Configure**
   - Navigate to: WP Admin → Plugins → Activate "HIP Ad Manager"
   - Go to: HIP Ad Manager → Settings
   - Set Network Code: `273585429`
   - Set Site Name: `kidsgourmet`
   - Enable Lazy Load: ✓
   - Enable Single Request: ✓
   - Save Settings

3. **Import Ad Slots**
   - Go to: HIP Ad Manager → Import
   - Upload your CSV file from Google Ad Manager
   - Preview the slots
   - Click "Confirm Import"

## Example 2: REST API Usage

### Fetch All Ad Slots

```bash
curl https://your-site.com/wp-json/hip-ads/v1/slots
```

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
      "sizes": [[300, 250], [336, 280]],
      "sizeMappings": [...],
      "targeting": {},
      "lazyLoad": true,
      "placement": "in-content",
      "device": "desktop",
      "priority": 10
    }
  ]
}
```

### Filter by Device

```bash
curl https://your-site.com/wp-json/hip-ads/v1/slots?device=mobile
```

### Filter by Placement

```bash
curl https://your-site.com/wp-json/hip-ads/v1/slots?placement=header
```

### Multiple Filters

```bash
curl https://your-site.com/wp-json/hip-ads/v1/slots?device=desktop&placement=sidebar
```

## Example 3: Simple HTML/JavaScript Implementation

```html
<!DOCTYPE html>
<html>
<head>
    <title>Ad Example</title>
    <script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
    <script>
        window.googletag = window.googletag || {cmd: []};
        
        // Fetch ad configuration
        fetch('https://your-wp-site.com/wp-json/hip-ads/v1/slots?placement=header')
            .then(res => res.json())
            .then(config => {
                googletag.cmd.push(function() {
                    // Configure GPT
                    if (config.enableSingleRequest) {
                        googletag.pubads().enableSingleRequest();
                    }
                    
                    if (config.enableLazyLoad) {
                        googletag.pubads().enableLazyLoad();
                    }
                    
                    // Define slots
                    config.slots.forEach(slot => {
                        googletag.defineSlot(
                            slot.adUnitPath,
                            slot.sizes,
                            `ad-${slot.id}`
                        ).addService(googletag.pubads());
                    });
                    
                    googletag.enableServices();
                    
                    // Display ads
                    config.slots.forEach(slot => {
                        googletag.display(`ad-${slot.id}`);
                    });
                });
            });
    </script>
</head>
<body>
    <header>
        <!-- Ad will be displayed here -->
        <div id="ad-123"></div>
    </header>
</body>
</html>
```

## Example 4: Next.js App Router

```typescript
// app/components/AdSlot.tsx
'use client';

import { useEffect, useRef } from 'react';

interface AdSlotProps {
  slot: {
    id: number;
    adUnitPath: string;
    sizes: number[][];
    sizeMappings: Array<{
      viewport: number[];
      sizes: number[][];
    }>;
  };
}

export default function AdSlot({ slot }: AdSlotProps) {
  const adRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const { googletag } = window as any;
    if (!googletag) return;

    googletag.cmd.push(() => {
      const gptSlot = googletag.defineSlot(
        slot.adUnitPath,
        slot.sizes,
        `ad-${slot.id}`
      );

      if (slot.sizeMappings?.length) {
        const mapping = googletag.sizeMapping();
        slot.sizeMappings.forEach(map => {
          mapping.addSize(map.viewport, map.sizes);
        });
        gptSlot.defineSizeMapping(mapping.build());
      }

      gptSlot.addService(googletag.pubads());
      googletag.display(`ad-${slot.id}`);
    });
  }, [slot]);

  return <div id={`ad-${slot.id}`} ref={adRef} />;
}
```

```typescript
// app/page.tsx
import AdSlot from './components/AdSlot';

async function getAds() {
  const res = await fetch(
    'https://your-wp-site.com/wp-json/hip-ads/v1/slots?placement=header',
    { next: { revalidate: 3600 } } // Cache for 1 hour
  );
  return res.json();
}

export default async function Home() {
  const adConfig = await getAds();

  return (
    <main>
      <header>
        {adConfig.slots?.[0] && <AdSlot slot={adConfig.slots[0]} />}
      </header>
      <h1>Welcome to Kids Gourmet</h1>
    </main>
  );
}
```

## Example 5: CSV Import Format

```csv
#ID,Parent Id,Code,Name,Sizes:,Description,Enabled for AdSense,Placements,Target Window,Labels
23335123404,23335085636,KidsGourmet.com.tr/kidsgourmet_160x600_wideskyscraper_left,kidsgourmet_160x600_WideSkyscraper_Left,120x600; 160x600; 161x600,,no,,_blank,
```

**What happens during import:**

1. **Name parsing:**
   - `kidsgourmet_160x600_WideSkyscraper_Left` → Placement: `sidebar` (contains "wideskyscraper")
   - `kidsgourmet_160x600_WideSkyscraper_Left` → Device: `all` (no mobile keyword)

2. **Size parsing:**
   - `120x600; 160x600; 161x600` → `[[120, 600], [160, 600], [161, 600]]`

3. **Ad Unit Path:**
   - Network Code: `273585429` + Code: `KidsGourmet.com.tr/kidsgourmet_160x600_wideskyscraper_left`
   - Result: `/273585429/KidsGourmet.com.tr/kidsgourmet_160x600_wideskyscraper_left`

4. **Size Mappings:**
   - Based on placement (`sidebar`), applies "skyscraper" size mappings automatically

## Example 6: Manual Ad Slot Creation

Instead of CSV import, you can manually create ad slots:

1. Go to: HIP Ad Manager → Ad Slots → Add New
2. Enter Title: `Header Leaderboard`
3. Fill in metaboxes:
   - **GAM Information:**
     - Slot ID: `23335123659`
     - Ad Unit Path: `/273585429/KidsGourmet.com.tr/kidsgourmet_728x90_leaderboard`
   - **Ad Sizes:**
     - Sizes: `[[728, 90], [970, 90], [320, 50]]`
     - Size Mappings:
       ```json
       [
         {"viewport": [1024, 0], "sizes": [[970, 90], [728, 90]]},
         {"viewport": [768, 0], "sizes": [[728, 90]]},
         {"viewport": [0, 0], "sizes": [[320, 50]]}
       ]
       ```
   - **Display Rules:**
     - Placement: Header
     - Device: All Devices
     - Lazy Load: ✓
   - **Status:**
     - Status: Active
     - Priority: 10
4. Click "Publish"

## Example 7: WordPress Integration (for Traditional WordPress Sites)

```php
<?php
// In your theme's functions.php or plugin

// Enqueue GPT
function my_theme_enqueue_gpt() {
    wp_enqueue_script(
        'google-publisher-tag',
        'https://securepubads.g.doubleclick.net/tag/js/gpt.js',
        array(),
        null,
        false
    );
    wp_script_add_data('google-publisher-tag', 'async', true);
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_gpt');

// Display ad in template
function display_ad_slot($placement) {
    $slots = HIP_Ad_Slot::get_active_slots(array(
        'meta_query' => array(
            array(
                'key' => 'gam_placement',
                'value' => $placement,
            ),
        ),
    ));
    
    if (!empty($slots)) {
        $slot = $slots[0];
        $slot_data = HIP_Ad_Slot::format_slot_data($slot);
        ?>
        <div id="ad-<?php echo esc_attr($slot_data['id']); ?>"></div>
        <script>
            googletag.cmd.push(function() {
                googletag.defineSlot(
                    '<?php echo esc_js($slot_data['adUnitPath']); ?>',
                    <?php echo wp_json_encode($slot_data['sizes']); ?>,
                    'ad-<?php echo esc_js($slot_data['id']); ?>'
                ).addService(googletag.pubads());
                
                googletag.display('ad-<?php echo esc_js($slot_data['id']); ?>');
            });
        </script>
        <?php
    }
}

// Use in template
// header.php
display_ad_slot('header');

// sidebar.php
display_ad_slot('sidebar');
?>
```

## Troubleshooting Tips

### Ads Not Showing Up

1. Check if slots are published and active in WP Admin
2. Verify network code is correct in Settings
3. Check browser console for JavaScript errors
4. Ensure GPT script is loaded

### API Returns Empty Array

1. Make sure you have published ad slots
2. Check that slots are marked as "active" status
3. Verify your filter parameters match existing slots

### Import Fails

1. Ensure CSV file has correct format (see sample)
2. Check that Network Code is set in Settings
3. Verify file upload permissions

For more help, see the full [README.md](README.md) and [INTEGRATION.md](INTEGRATION.md) files.
