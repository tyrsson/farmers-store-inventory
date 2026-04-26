# Session Context — Farmers IMS UI Mockup
_Last updated: April 26, 2026_

---

## Project Overview

**Farmers IMS** — Inventory Management System for Farmers Home Furniture stores.
Built for store-floor staff: receiving manifests from the DC, scanning SKU barcodes,
recording and photographing damage, submitting items for PQA assessment.

**Stack**: Mezzio + HTMX + Bootstrap 5.3.3 + Bootstrap Icons 1.13.1. PHP 8.6+ with the
TrueAsync extension for the async server layer.

---

## Mockup Status

### v1 — `resources/ui-mockup/v1/`
Complete reference implementation (custom CSS, no Bootstrap JS). Retained for reference only.
**Do not modify v1.**

### v2 — `resources/ui-mockup/v2/`  ← **ACTIVE**
Full Bootstrap 5.3.3 rebuild. All pages complete and working.

| File | Status | Notes |
|---|---|---|
| `custom.css` | ✅ Complete | Thin override layer only — brand tokens, sidebar fix, status badges, bottom nav, scan zone, photo grid |
| `app.js` | ✅ Complete | Minimal: store switcher active state, status toggle buttons |
| `index.html` | ✅ Complete | Dashboard — stat cards, quick actions, recent damage list |
| `inventory.html` | ✅ Complete | Product list, `btn-check` filter chips, search input |
| `manifests.html` | ✅ Complete | Manifest list with progress bars, status badges |
| `manifest-detail.html` | ✅ Complete | Summary card, damaged/clean item sections |
| `damage-detail.html` | ✅ Complete | Product identity, status toggles, damage notes, photo grid, PQA card, Send Images modal |
| `process-manifest.html` | ✅ Complete | Scan zone, manual entry, processed list, Finish Manifest modal |
| `process-manifest.js` | ✅ Complete | Hardware wedge + camera stub (ZXing placeholder), Bootstrap toast feedback |
| `settings.html` | ✅ Complete | Profile form, notification switches, store config, Change Password modal |
| `login.html` | ✅ Complete | Centered card, password show/hide toggle |
| `analytics.html` | ✅ Complete | Chart.js 4 — damage trend (line), status doughnut, items/manifest grouped bar, top categories horizontal bar, manifest summary table |

---

## Key Design Decisions

### Bootstrap Usage
- **Always use native Bootstrap** over custom implementations.
- All modals: `data-bs-toggle="modal"` / `data-bs-dismiss="modal"` — zero custom JS.
- Sidebar: `offcanvas-lg offcanvas-start` — drawer on mobile, fixed panel on desktop.
- Notifications: `form-switch`. Filter chips: `btn-check` radio groups.
- Progress bars: native `.progress` / `.progress-bar`.

### Sidebar Layout Fix
The `offcanvas-lg` becomes a block element at ≥992px, which pushed content down.
**Fix (in `custom.css`):** At `@media (min-width: 992px)`, sidebar is `position: fixed`,
`top: 56px`, `bottom: 0`, `z-index: 1020`. `.ims-layout` gets `margin-left: 260px`.

### Navigation Structure
- **Desktop**: Fixed top navbar (56px) + fixed left sidebar (260px) + main content area.
- **Mobile**: Fixed top navbar + bottom nav (60px, 5 items) with centre scan FAB.
- Sidebar collapse accordion for store switching.

### Data Model (as shown in mockup)
Each product item displays three identifiers:
```
SKU: 195844 · AO#: A006523361 · 207-0401
```
- **SKU** — 6-digit integer (Farmers/DC internal catalogue number)
- **AO#** — per-unit unique ID (format: `A` + 9 digits); encoded in Code 128B barcode on SKU card
- **Manifest ID** — format `{store}-{MMDD}` e.g. `207-0401`; the date = DC load date (consignment date)

Manifest ID is shown on all product list items, manifest detail rows, damage detail metadata.

### Barcode Scanning
- Barcode format: **Code 128B** on DC-printed SKU cards
- AO# is encoded in the barcode (confirmed by field analysis of physical cards)
- **ZXing-js** (`@zxing/library`) is the planned camera scanner library
- Hardware wedge scanners work natively (rapid keystrokes → Enter on pre-focused AO# input)
- `process-manifest.js` has a ZXing stub (simulates scan after 1.5s timeout)

### Charting
- **Chart.js 4.4.3** via CDN — used on analytics page
- Dark theme wired via `Chart.defaults.color` and `borderColor`
- Brand colour tokens in `analytics.html` script block mirror `custom.css` CSS variables
- No ApexCharts dependency — Chart.js was chosen for bundle size and simplicity

### PQA Email
- Each store has a `pqa_email` field (e.g. `pqa@farmers-store207.com`)
- "Send Images to PQA" on damage-detail triggers a Bootstrap modal pre-filled with:
  - To: store PQA email
  - Subject: `Damage Report — AO# … — {Product Name}`
  - Body: summary of damage
- Store PQA email is configurable in Settings → Store Configuration

---

## Routes / Page Connections
```
login.html → index.html (dashboard)
index.html → inventory.html, manifests.html, damage-detail.html, analytics.html, settings.html
inventory.html → damage-detail.html (damaged items)
manifests.html → process-manifest.html (in-progress), manifest-detail.html (complete)
manifest-detail.html → damage-detail.html
damage-detail.html ← back → manifest-detail.html
process-manifest.html ← back → manifests.html
analytics.html → manifest-detail.html, process-manifest.html (table links)
settings.html → login.html (sign out)
```

---

## What Was Removed
- **Transfer Lookup** — removed from all sidebars. Handled manually by the company; not in scope for v1.

---

## Pages Still Needing Sidebar Analytics Link
The abbreviated sidebars on `manifest-detail.html`, `damage-detail.html`,
`process-manifest.html`, and `settings.html` do not have a Reporting section.
Only `index.html`, `inventory.html`, `manifests.html`, and `analytics.html` have
the full Reporting nav section. This is intentional (these are detail pages).

---

## Next Steps (not yet started)
1. **Start Mezzio handler/template layer** — use the `htmx-mezzio` SKILL.md for the
   3-layer rendering stack (layout / body / page).
2. **Integrate ZXing-js** into `process-manifest` for real camera scanning.
3. **Database schema** — SKU catalogue, manifests, manifest_items, damage_reports, stores, users.
4. **Auth** — session-based login handler.
5. **Analytics endpoint** — JSON response for Chart.js data arrays.
6. **Analytics — date range switching** — the 30d/90d/6mo buttons in the topbar are
   wired visually but not yet functional; will need an HTMX swap on the chart data.

---

## Dev Server
- **v2 mockup**: PHP built-in server on port 7655
  ```bash
  php -S 0.0.0.0:7655 -t /workspaces/farmers-store-inventory/resources/ui-mockup/v2/ &
  ```
- **v1 mockup**: port 7654 (python3 http.server or PHP)
- Files also browseable directly via Windows→WSL2 filesystem bridge (`file://`)

---

## Repo Info
- Repository: `tyrsson/farmers-store-inventory`
- Branch: `master`
- Workspace: `/workspaces/farmers-store-inventory`
- v2 mockup path: `resources/ui-mockup/v2/`
- Planning docs: `docs/planning/farmers-store-inventory/`
