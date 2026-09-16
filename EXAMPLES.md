# HIP Ad Manager v2 — Quick Examples

## 1. WordPress setup

1. Activate **HIP Ad Manager**.
2. Open **HIP Ads → Ayarlar**.
3. Enter the Google Ad Manager network code and a stable property code such as `hipinup`.
4. Keep Single Request enabled unless there is a specific reason not to.
5. Configure lazy-load margins and global targeting.
6. Add ads.txt rows and save.

## 2. Create a slot manually

Open **HIP Ads → Yeni Alan Ekle** and define:

- Name: `Article Inline 1`
- Stable slot key: `article_inline_1`
- Placement key: `article_inline_1`
- Placement group: `content`
- GAM ad unit path: `/1234567/hipinup/article_inline_1`
- Sizes: `300x250`, `336x280`
- Page type: `article`
- Device: `all`

The stable key is what the headless frontend should request. Do not use a GAM numeric inventory ID as the frontend key.

## 3. Import Google Ad Manager CSV

Open **HIP Ads → İçe / Dışa Aktar** and upload the GAM CSV export.

The importer runs as:

`validate → preview → create/update/skip → confirm`

Rows are matched against existing inventory by GAM ad-unit path first and stable key second. Re-importing the same inventory does not blindly create duplicate posts.

## 4. REST API

### Health

```bash
curl https://cms.example.com/wp-json/hip-ads/v1/health
```

### Full client configuration

```bash
curl https://cms.example.com/wp-json/hip-ads/v1/config
```

### Live slots only

```bash
curl https://cms.example.com/wp-json/hip-ads/v1/slots
```

### Article content slots

```bash
curl "https://cms.example.com/wp-json/hip-ads/v1/slots?page_type=article&placement_group=content"
```

### A specific stable key

```bash
curl "https://cms.example.com/wp-json/hip-ads/v1/slots?key=article_inline_1"
```

Representative response:

```json
{
  "schemaVersion": 2,
  "networkCode": "1234567",
  "propertyCode": "hipinup",
  "adsEnabled": true,
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
  "slots": [
    {
      "id": 42,
      "key": "article_inline_1",
      "slotId": "article_inline_1",
      "adUnitPath": "/1234567/hipinup/article_inline_1",
      "placementKey": "article_inline_1",
      "placementGroup": "content",
      "device": "all",
      "sizes": [[300, 250], [336, 280]],
      "pageTypes": ["article"],
      "lazyLoad": true,
      "collapseEmpty": true,
      "responsiveMinHeight": {
        "desktop": 280,
        "tablet": 280,
        "mobile": 280
      }
    }
  ]
}
```

## 5. Frontend GPT contract

WordPress is the inventory control plane. The frontend owns the GPT runtime.

Load Google Publisher Tag directly from Google:

```text
https://securepubads.g.doubleclick.net/tag/js/gpt.js
```

Then configure modern GPT settings from the API response:

```js
window.googletag = window.googletag || { cmd: [] };

googletag.cmd.push(() => {
  googletag.setConfig({
    singleRequest: config.gpt.singleRequest,
    collapseDiv: config.gpt.collapseEmpty ? 'ON_NO_FILL' : 'DISABLED',
    lazyLoad: config.gpt.lazyLoad || undefined,
    targeting: config.globalTargeting,
  });

  googletag.enableServices();
});
```

Create/destroy individual slot objects in the frontend as route components mount/unmount. In SPA/Next.js navigation, destroy old GPT slots before reusing their DOM IDs.

## 6. Gutenberg marker block

The **Ad Slot** block stores a stable slot key in content. It does not execute GPT inside WordPress content. A headless renderer can detect the marker and mount its own ad component.

Legacy blocks that stored the old numeric WordPress slot ID are resolved to the migrated stable key when possible.

## 7. ads.txt

Manage rows in **HIP Ads → Ayarlar** and fetch them from:

```text
/wp-json/hip-ads/v1/ads-txt
```

A headless frontend should expose the returned content at the public origin's `/ads.txt` URL as `text/plain`.
