# HIP Ad Manager - Integration Guide

This guide explains how to integrate HIP Ad Manager with your headless WordPress project.

## Quick Start

### 1. Install & Configure

1. Install and activate the plugin in WordPress
2. Navigate to **HIP Ad Manager** → **Settings**
3. Configure your Google Ad Manager network code: `273585429`
4. Set your site name: `kidsgourmet`
5. Enable lazy loading and single request mode

### 2. Import Your Ad Slots

1. Export your ad units from Google Ad Manager as CSV
2. Go to **HIP Ad Manager** → **Import**
3. Upload the CSV file
4. Preview and confirm the import

### 3. Fetch Ad Configuration in Your Frontend

```javascript
// Fetch all active ad slots
const response = await fetch('https://your-wp-site.com/wp-json/hip-ads/v1/slots');
const adConfig = await response.json();

console.log(adConfig);
// {
//   "networkCode": "273585429",
//   "enableLazyLoad": true,
//   "enableSingleRequest": true,
//   "globalTargeting": { "site": "kidsgourmet" },
//   "slots": [...]
// }
```

## Integration Examples

### Next.js Example

#### 1. Create an Ad Component

```javascript
// components/GoogleAd.js
import { useEffect, useRef } from 'react';

export default function GoogleAd({ slot }) {
  const adRef = useRef(null);

  useEffect(() => {
    if (!slot || !window.googletag) return;

    window.googletag.cmd.push(() => {
      const gptSlot = window.googletag.defineSlot(
        slot.adUnitPath,
        slot.sizes,
        `ad-${slot.id}`
      );

      // Add size mappings for responsive ads
      if (slot.sizeMappings && slot.sizeMappings.length > 0) {
        const mapping = window.googletag.sizeMapping();
        slot.sizeMappings.forEach(map => {
          mapping.addSize(map.viewport, map.sizes);
        });
        gptSlot.defineSizeMapping(mapping.build());
      }

      // Add targeting
      if (slot.targeting) {
        Object.entries(slot.targeting).forEach(([key, value]) => {
          gptSlot.setTargeting(key, value);
        });
      }

      gptSlot.addService(window.googletag.pubads());
      window.googletag.display(`ad-${slot.id}`);
    });

    return () => {
      window.googletag.cmd.push(() => {
        window.googletag.destroySlots();
      });
    };
  }, [slot]);

  if (!slot) return null;

  return (
    <div 
      id={`ad-${slot.id}`}
      className="ad-container"
      style={{ minHeight: '100px' }}
    />
  );
}
```

#### 2. Initialize GPT in _app.js

```javascript
// pages/_app.js
import { useEffect } from 'react';
import Head from 'next/head';

function MyApp({ Component, pageProps }) {
  useEffect(() => {
    // Load GPT script
    const script = document.createElement('script');
    script.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
    script.async = true;
    document.head.appendChild(script);

    script.onload = () => {
      window.googletag = window.googletag || { cmd: [] };
      
      // Fetch ad configuration
      fetch('https://your-wp-site.com/wp-json/hip-ads/v1/slots')
        .then(res => res.json())
        .then(config => {
          window.googletag.cmd.push(() => {
            // Configure GPT
            const pubads = window.googletag.pubads();
            
            if (config.enableSingleRequest) {
              pubads.enableSingleRequest();
            }
            
            if (config.enableLazyLoad) {
              pubads.enableLazyLoad({
                fetchMarginPercent: 200,
                renderMarginPercent: 100,
                mobileScaling: 2.0
              });
            }
            
            // Set global targeting
            if (config.globalTargeting) {
              Object.entries(config.globalTargeting).forEach(([key, value]) => {
                pubads.setTargeting(key, value);
              });
            }
            
            window.googletag.enableServices();
          });
        });
    };
  }, []);

  return <Component {...pageProps} />;
}

export default MyApp;
```

#### 3. Use Ad Components in Pages

```javascript
// pages/index.js
import { useState, useEffect } from 'react';
import GoogleAd from '../components/GoogleAd';

export default function Home() {
  const [headerAd, setHeaderAd] = useState(null);
  const [sidebarAd, setSidebarAd] = useState(null);

  useEffect(() => {
    // Fetch ads for this page
    fetch('https://your-wp-site.com/wp-json/hip-ads/v1/slots?placement=header')
      .then(res => res.json())
      .then(data => {
        if (data.slots && data.slots.length > 0) {
          setHeaderAd(data.slots[0]);
        }
      });

    fetch('https://your-wp-site.com/wp-json/hip-ads/v1/slots?placement=sidebar')
      .then(res => res.json())
      .then(data => {
        if (data.slots && data.slots.length > 0) {
          setSidebarAd(data.slots[0]);
        }
      });
  }, []);

  return (
    <div>
      <header>
        <GoogleAd slot={headerAd} />
      </header>
      
      <main>
        <h1>Welcome to Kids Gourmet</h1>
        <aside>
          <GoogleAd slot={sidebarAd} />
        </aside>
      </main>
    </div>
  );
}
```

### React (Vite/CRA) Example

```javascript
// src/hooks/useGoogleAds.js
import { useEffect, useState } from 'react';

export function useGoogleAds(filters = {}) {
  const [ads, setAds] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const params = new URLSearchParams(filters);
    const url = `${process.env.REACT_APP_WP_URL}/wp-json/hip-ads/v1/slots?${params}`;

    fetch(url)
      .then(res => res.json())
      .then(data => {
        setAds(data.slots || []);
        setLoading(false);
      })
      .catch(err => {
        console.error('Failed to load ads:', err);
        setLoading(false);
      });
  }, [JSON.stringify(filters)]);

  return { ads, loading };
}

// Usage in component
function HomePage() {
  const { ads, loading } = useGoogleAds({ placement: 'header', device: 'desktop' });

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      {ads.map(ad => (
        <GoogleAd key={ad.id} slot={ad} />
      ))}
    </div>
  );
}
```

### Vue.js Example

```javascript
// composables/useGoogleAds.js
import { ref, onMounted } from 'vue';

export function useGoogleAds(filters = {}) {
  const ads = ref([]);
  const loading = ref(true);

  onMounted(async () => {
    try {
      const params = new URLSearchParams(filters);
      const response = await fetch(
        `${import.meta.env.VITE_WP_URL}/wp-json/hip-ads/v1/slots?${params}`
      );
      const data = await response.json();
      ads.value = data.slots || [];
    } catch (error) {
      console.error('Failed to load ads:', error);
    } finally {
      loading.value = false;
    }
  });

  return { ads, loading };
}

// Component
<template>
  <div v-if="!loading">
    <GoogleAd 
      v-for="ad in ads" 
      :key="ad.id" 
      :slot="ad" 
    />
  </div>
</template>

<script setup>
import { useGoogleAds } from '@/composables/useGoogleAds';
import GoogleAd from '@/components/GoogleAd.vue';

const { ads, loading } = useGoogleAds({ placement: 'header' });
</script>
```

## Advanced Usage

### Dynamic Targeting

Add page-specific targeting:

```javascript
// Set page-level targeting
window.googletag.cmd.push(() => {
  window.googletag.pubads().setTargeting('category', 'recipes');
  window.googletag.pubads().setTargeting('author', 'john-doe');
  window.googletag.pubads().refresh();
});
```

### Refresh Ads

```javascript
// Refresh all ads
window.googletag.cmd.push(() => {
  window.googletag.pubads().refresh();
});

// Refresh specific slot
window.googletag.cmd.push(() => {
  const slots = window.googletag.pubads().getSlots();
  const slot = slots.find(s => s.getSlotElementId() === 'ad-123');
  if (slot) {
    window.googletag.pubads().refresh([slot]);
  }
});
```

### Mobile-Specific Ads

```javascript
// Detect device and fetch appropriate ads
const isMobile = window.innerWidth < 768;
const device = isMobile ? 'mobile' : 'desktop';

const response = await fetch(
  `https://your-wp-site.com/wp-json/hip-ads/v1/slots?device=${device}`
);
```

### Filtering by Multiple Criteria

```javascript
// Get ads for specific page type, device, and placement
const filters = {
  placement: 'header',
  device: 'mobile',
  page_type: 'article'
};

const params = new URLSearchParams(filters);
const response = await fetch(
  `https://your-wp-site.com/wp-json/hip-ads/v1/slots?${params}`
);
```

## Best Practices

1. **Cache Ad Configuration**: Cache the ad configuration in your application state to avoid repeated API calls
2. **Error Handling**: Always handle API errors gracefully
3. **Lazy Loading**: Enable lazy loading for better performance
4. **Responsive Ads**: Use size mappings for responsive ad display
5. **Testing**: Test ads in different devices and screen sizes
6. **Monitoring**: Monitor ad performance and adjust as needed

## Troubleshooting

### Ads Not Displaying

1. Check browser console for errors
2. Verify network code is correct
3. Ensure GPT script is loaded
4. Check if ad slots are marked as "active" in WordPress

### API Returning Empty Slots

1. Verify ad slots exist and are published
2. Check that slots are marked as "active"
3. Verify filters (device, placement) match existing slots

### Size Mapping Issues

1. Review size mappings in WordPress admin
2. Test on different screen sizes
3. Check browser console for GPT warnings

## Additional Resources

- [Google Publisher Tag (GPT) Documentation](https://developers.google.com/publisher-tag/guides/get-started)
- [Google Ad Manager Help Center](https://support.google.com/admanager)
- [HIP Ad Manager GitHub Repository](https://github.com/selim-create/hip-admanager)
