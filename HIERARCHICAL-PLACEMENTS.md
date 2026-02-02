# Hierarchical Placement System - Documentation

This document explains the new hierarchical placement system implemented in HIP Ad Manager.

## Overview

The hierarchical placement system replaces the simple placement system (header, sidebar, in-content, footer) with a more flexible and granular structure that allows better control over ad placement and organization.

## New Placement Types

### Header Placements
- `header-leaderboard` - 728x90 (desktop) / 320x100 (mobile)
- `header-masthead` - 970x250 (large banner)
- `header-mobile` - 320x100 (mobile only)

### Sidebar Placements
- `sidebar-top` - 300x250 (top - MediumRectangle)
- `sidebar-middle` - 300x600 (middle - HalfPage)
- `sidebar-bottom` - 300x250 (bottom)
- `sidebar-sticky` - 160x600 (sticky/skyscraper)

### Content Placements
- `content-top` - Top of article/content
- `content-after-hero` - Below hero/search area (homepage)
- `content-in-feed` - Between list/card items
- `content-after-section` - Below sections
- `content-middle` - Middle of article
- `content-bottom` - End of article

### Footer Placements
- `footer-banner` - 728x90 (desktop)
- `footer-sticky-mobile` - 320x50 (mobile sticky)

### Special Placements
- `interstitial` - Full-page interstitial
- `native` - Native ad format

### Legacy Placements (Backward Compatibility)
- `header` - Generic header placement
- `sidebar` - Generic sidebar placement
- `in-content` - Generic in-content placement
- `footer` - Generic footer placement
- `mobile-sticky` - Generic mobile sticky placement

## New Meta Fields

### Zone (Optional)
The zone field provides additional control over ad placement:
- `top` - Top area
- `middle` - Middle area
- `bottom` - Bottom area
- `sticky` - Sticky/fixed position

### Position Order
A number field (1-100) that controls the display order of ads within the same placement/zone. Lower numbers appear first.

### Page Types
Checkboxes to specify which page types should display the ad:
- `home` - Homepage
- `post` - Single Post
- `page` - Single Page
- `category` - Category Archive
- `tag` - Tag Archive
- `search` - Search Results
- `archive` - Other Archives
- `all` - All Pages (default)

## API Response Format

### Slot Data Example
```json
{
  "id": 123,
  "name": "Sidebar Top Ad",
  "slotId": "23335123656",
  "adUnitPath": "/273585429/site.com/sidebar-top",
  "placement": "sidebar-top",
  "zone": "top",
  "position": 1,
  "page_types": ["home", "post"],
  "device": "all",
  "sizes": [
    {"width": 300, "height": 250}
  ],
  "priority": 10,
  "lazyLoad": true
}
```

### Config Endpoint - Placement Groups
The config endpoint (`/wp-json/hip-ads/v1/config`) now includes placement groups:

```json
{
  "placement_groups": {
    "header": [
      "header-leaderboard",
      "header-masthead", 
      "header-mobile"
    ],
    "sidebar": [
      "sidebar-top",
      "sidebar-middle",
      "sidebar-bottom",
      "sidebar-sticky"
    ],
    "content": [
      "content-top",
      "content-after-hero",
      "content-in-feed",
      "content-after-section",
      "content-middle",
      "content-bottom"
    ],
    "footer": [
      "footer-banner",
      "footer-sticky-mobile"
    ]
  }
}
```

## Usage Examples

### 1. Creating an Ad Slot with New Placement

In WordPress Admin:
1. Go to **HIP Ad Manager** → **Ad Slots** → **Add New**
2. Fill in the basic information
3. Select **Placement**: Choose from the hierarchical options (e.g., "Sidebar - Top (300x250)")
4. Optionally set **Zone**: Select "top" for top priority
5. Set **Position Order**: Enter a number (e.g., 1 for first position)
6. Select **Page Types**: Check which page types should show this ad
7. Save the ad slot

### 2. Filtering Slots by Placement (API)

```bash
# Get all sidebar ads
curl https://your-site.com/wp-json/hip-ads/v1/slots?placement=sidebar-top

# Get all header ads  
curl https://your-site.com/wp-json/hip-ads/v1/slots?placement=header-leaderboard
```

### 3. Frontend Implementation

```javascript
// Fetch slots for a specific placement
async function loadAds() {
  const response = await fetch('/wp-json/hip-ads/v1/config');
  const config = await response.json();
  
  // Get all sidebar placements
  const sidebarPlacements = config.placement_groups.sidebar;
  
  // Filter slots by sidebar placements
  const sidebarAds = config.slots.filter(slot => 
    sidebarPlacements.includes(slot.placement)
  );
  
  // Sort by position
  sidebarAds.sort((a, b) => a.position - b.position);
  
  // Filter by page type (example: 'post')
  const currentPageType = 'post';
  const relevantAds = sidebarAds.filter(slot =>
    slot.page_types.includes(currentPageType) || 
    slot.page_types.includes('all')
  );
  
  // Render ads
  relevantAds.forEach(ad => {
    renderAd(ad);
  });
}
```

### 4. Grouping by Placement Prefix

```javascript
// Group slots by their placement prefix
function groupSlotsByCategory(slots) {
  return slots.reduce((groups, slot) => {
    const prefix = slot.placement.split('-')[0]; // 'header', 'sidebar', etc.
    if (!groups[prefix]) {
      groups[prefix] = [];
    }
    groups[prefix].push(slot);
    return groups;
  }, {});
}

// Usage
const grouped = groupSlotsByCategory(config.slots);
console.log(grouped.header);  // All header-* ads
console.log(grouped.sidebar); // All sidebar-* ads
```

## Migration Guide

### For Existing Installations

1. **Existing ads continue to work**: All existing ad slots with legacy placements (`header`, `sidebar`, etc.) will continue to function without changes.

2. **Gradual migration**: You can gradually migrate to the new hierarchical placements:
   - Edit existing ad slots
   - Change placement from "Header (Legacy)" to "Header - Leaderboard"
   - Optionally set zone and position for better control
   - Set page types if needed

3. **API compatibility**: The API returns both old and new placement formats, so frontend code will continue to work.

### For New Installations

Start using the new hierarchical placements from the beginning for better organization and control.

## Best Practices

1. **Use specific placements**: Instead of generic "sidebar", use "sidebar-top" or "sidebar-middle" for better clarity
2. **Set position order**: When multiple ads share the same placement, use position to control display order
3. **Leverage page types**: Use page types to show different ads on different page types without complex targeting rules
4. **Zone for priority**: Use zones to group ads by priority (e.g., all "top" zone ads appear before "middle" zone ads)

## Backward Compatibility

- All legacy placement values continue to work
- API supports both old and new formats
- Legacy placements are clearly marked in the admin interface
- Frontend can use placement prefix matching to group both old and new placements

## Summary

The hierarchical placement system provides:
- ✅ More granular control over ad placement
- ✅ Better organization with logical grouping
- ✅ Enhanced targeting with page types
- ✅ Flexible ordering with position field
- ✅ Optional zone classification
- ✅ Full backward compatibility
- ✅ Clear migration path for existing installations
