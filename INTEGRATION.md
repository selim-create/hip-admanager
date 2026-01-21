# HIP Ad Manager - Integration Guide

This comprehensive guide explains how to integrate HIP Ad Manager with your headless WordPress project, including advanced features like CLS prevention, caching, refresh logic, and more.

## Table of Contents

1. [Quick Start](#quick-start)
2. [API Endpoints](#api-endpoints)
3. [CLS Prevention](#cls-prevention)
4. [Cache Strategy](#cache-strategy)
5. [Dynamic Targeting](#dynamic-targeting)
6. [Ad Refresh Logic](#ad-refresh-logic)
7. [Lazy Loading](#lazy-loading)
8. [In-Content Ads (Gutenberg Block)](#in-content-ads)
9. [ads.txt Management](#adstxt-management)
10. [Framework Examples](#framework-examples)

---

## Quick Start

### 1. Install & Configure

1. Install and activate the plugin in WordPress
2. Navigate to **HIP Ad Manager** → **Settings**
3. Configure your Google Ad Manager network code (e.g., `273585429`)
4. Set your site name for targeting (e.g., `kidsgourmet`)
5. Enable lazy loading and single request mode
6. Configure cache duration (default: 1 hour)
7. Add your ads.txt content

### 2. Import Your Ad Slots

1. Export your ad units from Google Ad Manager as CSV
2. Go to **HIP Ad Manager** → **Import**
3. Upload the CSV file
4. Preview and confirm the import

---

## API Endpoints

### Get All Slots

```
GET /wp-json/hip-ads/v1/slots
```

**Query Parameters:**
- `device` - Filter by device (mobile, tablet, desktop, all)
- `placement` - Filter by placement (header, sidebar, content, footer, etc.)
- `page_type` - Filter by page type
- `category` - Filter by category

**Response:**

```json
{
  "networkCode": "273585429",
  "enableLazyLoad": true,
  "enableSingleRequest": true,
  "globalTargeting": {
    "site": "kidsgourmet",
    "env": "production"
  },
  "dynamicTargetingKeys": ["category", "tags", "author", "postType", "customKey"],
  "slots": [
    {
      "id": 123,
      "name": "Header Leaderboard",
      "slotId": "header-leaderboard",
      "adUnitPath": "/273585429/header",
      "sizes": [[970, 250], [728, 90]],
      "sizeMappings": [...],
      "targeting": {},
      "placement": "header",
      "device": "all",
      "priority": 10,
      "minHeight": 250,
      "responsiveMinHeight": {
        "desktop": 250,
        "tablet": 90,
        "mobile": 100
      },
      "placeholder": {
        "enabled": true,
        "backgroundColor": "#f0f0f0",
        "showLabel": true,
        "labelText": "Advertisement"
      },
      "refresh": {
        "enabled": true,
        "interval": 30,
        "maxRefreshes": 10,
        "refreshOnVisible": true,
        "pauseOnHidden": true
      },
      "lazyLoadConfig": {
        "enabled": true,
        "strategy": "intersection",
        "fetchMarginPercent": 200,
        "renderMarginPercent": 100,
        "mobileScaling": 2.0,
        "idleTimeout": 200
      }
    }
  ]
}
```

### Get ads.txt

```
GET /wp-json/hip-ads/v1/ads-txt
```

### Clear Cache

```
POST /wp-json/hip-ads/v1/cache/clear
```

---

## CLS Prevention

Cumulative Layout Shift (CLS) is a critical Core Web Vitals metric. HIP Ad Manager provides automatic CLS prevention through minHeight and placeholder configurations.

### Implementation Example

```javascript
export default function GoogleAd({ slot }) {
  const [adLoaded, setAdLoaded] = useState(false);

  const getMinHeight = () => {
    if (typeof window === 'undefined') return slot.minHeight;
    
    const width = window.innerWidth;
    if (width >= 1024) return slot.responsiveMinHeight.desktop;
    if (width >= 768) return slot.responsiveMinHeight.tablet;
    return slot.responsiveMinHeight.mobile;
  };

  return (
    <div 
      id={`ad-${slot.id}`}
      style={{
        minHeight: `${getMinHeight()}px`,
        backgroundColor: !adLoaded && slot.placeholder?.enabled 
          ? slot.placeholder.backgroundColor 
          : 'transparent',
      }}
    >
      {!adLoaded && slot.placeholder?.showLabel && (
        <span>{slot.placeholder.labelText}</span>
      )}
    </div>
  );
}
```

---

## Cache Strategy

### Client-Side Caching

```javascript
let cachedConfig = null;
let cacheTime = null;
const CACHE_DURATION = 60 * 60 * 1000; // 1 hour

export async function getAdConfig(filters = {}) {
  if (cachedConfig && cacheTime && Date.now() - cacheTime < CACHE_DURATION) {
    return cachedConfig;
  }

  const params = new URLSearchParams(filters);
  const response = await fetch(
    `${process.env.NEXT_PUBLIC_WP_URL}/wp-json/hip-ads/v1/slots?${params}`
  );
  
  const config = await response.json();
  cachedConfig = config;
  cacheTime = Date.now();
  
  return config;
}
```

---

## Dynamic Targeting

```javascript
useEffect(() => {
  if (!window.googletag) return;

  window.googletag.cmd.push(() => {
    const pubads = window.googletag.pubads();
    
    // Set dynamic targeting
    pubads.setTargeting('category', recipe.category);
    pubads.setTargeting('tags', recipe.tags);
    pubads.setTargeting('author', recipe.author.slug);
    pubads.setTargeting('postType', 'recipe');
    
    // Refresh ads with new targeting
    pubads.refresh();
  });
}, [recipe]);
```

---

## Ad Refresh Logic

```javascript
export function useAdRefresh(slot, gptSlot) {
  const refreshCount = useRef(0);
  const intervalRef = useRef(null);

  useEffect(() => {
    if (!slot?.refresh?.enabled || !gptSlot) return;

    const { interval, maxRefreshes } = slot.refresh;

    intervalRef.current = setInterval(() => {
      if (refreshCount.current >= maxRefreshes) {
        clearInterval(intervalRef.current);
        return;
      }

      window.googletag.cmd.push(() => {
        window.googletag.pubads().refresh([gptSlot]);
        refreshCount.current++;
      });
    }, interval * 1000);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [slot, gptSlot]);
}
```

---

## Lazy Loading

```javascript
export default function LazyAd({ slot }) {
  const [shouldLoad, setShouldLoad] = useState(false);
  const containerRef = useRef(null);

  useEffect(() => {
    if (!slot?.lazyLoadConfig?.enabled) {
      setShouldLoad(true);
      return;
    }

    const { fetchMarginPercent } = slot.lazyLoadConfig;
    const rootMargin = `${fetchMarginPercent}% 0px`;
    
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          setShouldLoad(true);
          observer.disconnect();
        }
      },
      { rootMargin }
    );

    if (containerRef.current) {
      observer.observe(containerRef.current);
    }

    return () => observer.disconnect();
  }, [slot]);

  return (
    <div ref={containerRef}>
      {shouldLoad ? <GoogleAd slot={slot} /> : (
        <div style={{ minHeight: `${slot.minHeight}px` }} />
      )}
    </div>
  );
}
```

---

## In-Content Ads

### Using Gutenberg Block

1. Open post/page in WordPress editor
2. Add "Ad Slot" block
3. Select ad slot
4. Configure placement and alignment
5. Publish

The block outputs HTML with data attributes that can be processed in your headless frontend.

---

## ads.txt Management

### Next.js

Create `pages/ads.txt.js`:

```javascript
export async function getServerSideProps({ res }) {
  const response = await fetch(
    `${process.env.NEXT_PUBLIC_WP_URL}/wp-json/hip-ads/v1/ads-txt`
  );
  const adsTxt = await response.text();

  res.setHeader('Content-Type', 'text/plain; charset=utf-8');
  res.write(adsTxt);
  res.end();

  return { props: {} };
}

export default function AdsTxt() {
  return null;
}
```

---

## Debug Mode

Debug mode helps AdOps teams verify ad placement and troubleshoot integration issues. When enabled from the admin panel, API responses include additional debug information.

### Enabling Debug Mode

1. Navigate to **HIP Ad Manager** → **Settings**
2. Under **General Settings**, check **Enable Debug Mode**
3. Save settings

### API Response (Debug Mode Enabled)

When debug mode is enabled, the `/wp-json/hip-ads/v1/config` endpoint includes:

```json
{
  "networkCode": "273585429",
  "siteName": "kidsgourmet",
  "enableLazyLoad": true,
  "enableSingleRequest": true,
  "debug": {
    "enabled": true,
    "timestamp": "2024-01-21T10:30:00+03:00",
    "cacheStatus": "HIT",
    "phpVersion": "8.2.0",
    "wpVersion": "6.4",
    "pluginVersion": "1.0.0"
  }
}
```

The `/wp-json/hip-ads/v1/slots` endpoint includes debug info in the main response and for each slot:

```json
{
  "debug": {
    "enabled": true,
    "timestamp": "2024-01-21T10:30:00+03:00",
    "cacheStatus": "HIT",
    "phpVersion": "8.2.0",
    "wpVersion": "6.4",
    "pluginVersion": "1.0.0"
  },
  "slots": [
    {
      "id": 123,
      "name": "Header Banner",
      "slotId": "header-leaderboard",
      "adUnitPath": "/273585429/header",
      "sizes": [[970, 250], [728, 90]],
      "debug": {
        "postId": 123,
        "postStatus": "publish",
        "created": "2024-01-15 10:00:00",
        "modified": "2024-01-20 15:30:00",
        "sizesRaw": "[[970,250],[728,90]]",
        "filteredMeta": {
          "gam_slot_id": "header-leaderboard",
          "gam_placement": "header",
          "gam_status": "active"
        },
        "sizeLabel": "970x250, 728x90",
        "displayInfo": "Slot ID: header-leaderboard | Sizes: 970x250, 728x90 | Placement: header"
      }
    }
  ]
}
```

### Frontend Debug Component (Next.js Example)

```tsx
interface AdSlotProps {
  slot: AdSlotType;
}

export function AdSlot({ slot }: AdSlotProps) {
  const { config } = useAds();
  const isDebugMode = config?.debug?.enabled;
  
  if (isDebugMode) {
    // Debug mode: Display placeholder box with slot details
    return (
      <div
        style={{
          minHeight: `${slot.minHeight}px`,
          backgroundColor: '#f0f0f0',
          border: '2px dashed #666',
          padding: '20px',
          textAlign: 'center',
          fontFamily: 'monospace'
        }}
      >
        <div style={{ fontSize: '14px', fontWeight: 'bold', marginBottom: '10px' }}>
          DEBUG MODE
        </div>
        <div style={{ fontSize: '12px' }}>
          {slot.debug?.displayInfo}
        </div>
        <div style={{ fontSize: '10px', marginTop: '5px', color: '#666' }}>
          Post ID: {slot.debug?.postId} | Min Height: {slot.minHeight}px
        </div>
      </div>
    );
  }
  
  // Normal ad rendering
  return <GoogleAd slot={slot} />;
}
```

### Debug Mode Use Cases

1. **Ad Placement Testing**: AdOps team can visually verify that ads appear in the correct locations on the page
2. **Size Validation**: Each placeholder shows which ad sizes are configured for that slot
3. **API Response Inspection**: Network tab shows all slot details and metadata for troubleshooting
4. **Cache Debugging**: The `cacheStatus` field helps verify whether responses are being served from cache
5. **Integration Verification**: Confirm that all required slots are loading and properly configured

### Security Note

Debug mode should only be enabled in development or staging environments. In production, debug information could expose internal implementation details. Always disable debug mode before launching to production.

**Important**: Debug information is filtered to only include GAM-specific metadata fields. Sensitive WordPress metadata (like custom fields unrelated to ad management) is excluded from debug responses for security.

---

## Framework Examples

### Next.js with Device Detection

```javascript
// middleware.js
import { NextResponse } from 'next/server';

export function middleware(request) {
  const userAgent = request.headers.get('user-agent') || '';
  
  let device = 'desktop';
  if (/mobile/i.test(userAgent)) device = 'mobile';
  else if (/tablet|ipad/i.test(userAgent)) device = 'tablet';
  
  const response = NextResponse.next();
  response.headers.set('x-device-type', device);
  
  return response;
}
```

---

## Best Practices

1. **Cache Wisely**: Cache ad configuration but allow manual clearing
2. **CLS Prevention**: Always use minHeight and placeholders
3. **Lazy Load**: Use IntersectionObserver for better performance
4. **Refresh Responsibly**: Limit refresh count and respect visibility
5. **Dynamic Targeting**: Update targeting based on page context
6. **Error Handling**: Always handle API errors gracefully
7. **Testing**: Test on multiple devices and screen sizes
8. **Monitoring**: Monitor Core Web Vitals and ad performance

---

## Additional Resources

- [Google Publisher Tag (GPT) Documentation](https://developers.google.com/publisher-tag/guides/get-started)
- [Google Ad Manager Help Center](https://support.google.com/admanager)
- [Core Web Vitals](https://web.dev/vitals/)
- [HIP Ad Manager GitHub Repository](https://github.com/selim-create/hip-admanager)
