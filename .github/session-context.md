# Session Context — Farmers IMS
_Last updated: April 28, 2026_

> **⚠ Runtime environment changed (April 28, 2026):** TrueAsync (`php-async` extension) has been **removed** from the active stack. After 3 hours rebuilding the Docker environment and the VS Code devcontainer, the project now runs as a standard Mezzio application served by the **PHP built-in web server** (`php -S`) inside a `php:latest` Docker container. There is **no PHP-FPM and no nginx** in the active devcontainer stack. The decision was driven by current usability issues in TrueAsync (SIGABRT crashes, `proc_open` incompatibility inside coroutines, devcontainer instability). The `src/mezzio-async/` source tree and all TrueAsync planning docs (`docs/planning/php-async-api.md`, `docs/planning/trueasync-bugs.md`) are **retained** for future reintegration once the extension matures.
>
> **Verified from:** `.devcontainer/docker-compose.yml` (image `php:latest`, command `sleep infinity`), `.devcontainer/docker/php/Dockerfile` (`FROM php:latest`), `public/index.php` (`PHP_SAPI === 'cli-server'`), `devcontainer.json` (port 8080 forwarded). DO NOT assume the stack — read the files.

---

## Project Overview

**Farmers IMS** — Inventory Management System for Farmers Home Furniture stores.
Built for store-floor staff: receiving manifests from the DC, scanning SKU barcodes,
recording and photographing damage, submitting items for PQA assessment.

**Stack**: Mezzio + HTMX + Bootstrap 5.3.3 + Bootstrap Icons 1.13.1. PHP 8.6+ served by
the **PHP built-in web server** (`php -S 0.0.0.0:8080 -t public/`) inside a `php:latest`
Docker container (standard synchronous, single-threaded). ~~TrueAsync extension~~ — removed
(see note above); `src/mezzio-async/` retained for future use.

> **There is no PHP-FPM and no nginx in this project's active devcontainer.** Verified from
> `.devcontainer/docker-compose.yml` and `public/index.php`. Do not assume — verify.

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
3. **Analytics endpoint** — JSON response for Chart.js data arrays.
4. **Analytics — date range switching** — the 30d/90d/6mo buttons in the topbar are
   wired visually but not yet functional; will need an HTMX swap on the chart data.
5. **mezzio-authorization-acl config** — create `config/autoload/authorization.global.php`
   with `mezzio-authorization-acl` block (roles, resources = route names, allow rules).
   Add `AuthorizationInterface::class => LaminasAcl::class` alias.
6. **Template stubs** — `src/User/templates/user/` (login, list-users, create-user, edit-user).
7. **Manifest, Inventory, Fulfilment modules** — not started.

---

## Application Layer Status (as of April 27, 2026)

### Packages Installed
- `mezzio/mezzio-authentication ^1.13`
- `mezzio/mezzio-authorization ^1.11`
- `mezzio/mezzio-authorization-acl ^1.13`
- `mezzio/mezzio-valinor ^1.0`
- `mezzio/mezzio-authentication-session` (dev)
- All ConfigProviders injected into `config/config.php`

### Database Schema (`data/schema/`)
- 14 table files (001–014) + `999_seed.sql`
- All files have `DROP TABLE IF EXISTS` for clean reimport
- `user` is backticked everywhere (reserved word)
- **`role` table**: `id TINYINT UNSIGNED AUTO_INCREMENT` + `role_id VARCHAR(50)` (was `name`)
  — `role_id` is the Laminas ACL role identifier; Title Case with spaces is fine
- **`user` table**: `display_name VARCHAR(150)` (was `name`) — renamed to avoid conflict
  with `NamedCommandTrait`'s `protected readonly string $name` property
- `999_seed.sql`: inserts use `role_id` column, `ON DUPLICATE KEY UPDATE role_id`
- FK-safe DROP order (reverse): `transfer_item` → `transfer` → `ticket_item` → `ticket`
  → `product_image` → `product_status` → `product` → `manifest_item` → `manifest`
  → `sku_catalogue` → `major_code` → `user` → `role` → `store`

### User Module (`src/User/src/`)
**Do not touch without reading files first — user has done heavy refactoring.**

Key decisions made:
- `Entity\User` — `final readonly`, implements `UserInterface` + `NamedCommandInterface`
  (via `NamedCommandTrait`). Properties: `displayName` (not `name` — avoids trait clash).
- `Entity\Role` — `final readonly`, implements `NamedCommandInterface`. Properties:
  `int $id`, `string $roleId`. No `$name` property — trait owns that for FQCN resolution.
- `Entity\Store` — `final readonly`, implements `NamedCommandInterface`.
- `Repository\UserRepositoryInterface` — extends `UserRepositoryContract` only (not `UserInterface`).
  Identity comes from session at runtime, not the repository.
- `Repository\UserRepository` — composes `TableGateway('user', $adapter)` internally;
  uses `getSql()` + `prepareStatementForSqlObject()` for all DML queries.
  Manual `hydrate()` method retained until phpdb readonly-clone support is merged.
  See `@todo` in `UserRepositoryFactory` for the HydratingResultSet upgrade path.
- `Command\CreateUserCommand` — implements `NamedCommandInterface`, wraps `UserInterface`.

### Query Conventions
All DML queries must use `PhpDb\Sql\*` via `TableGateway::getSql()`.
See `.github/instructions/phpdb-sql-queries.instructions.md`.

### Hot-Reload
~~Disabled via `config/autoload/development.local.php` (`mezzio-async.hot-reload.enabled = false`)
to prevent TrueAsync SIGABRT crash on `fgets()` in `proc_open` pipe inside coroutine.~~
**No longer applicable** — TrueAsync and `mezzio-async` are not active. The PHP built-in
web server (`php -S`) must be manually stopped and restarted after code changes in the
devcontainer terminal.

### NamedCommandTrait / commandbus
`NamedCommandTrait` declares `protected readonly string $name`. Domain classes must NOT
have a `$name` property. Planned fix: rename to `$commandName` in the command-bus package.

---

## Dev Server

> **Verified stack**: PHP built-in web server (`php -S`) inside `php:latest` container.
> No PHP-FPM. No nginx. Source: `.devcontainer/docker-compose.yml`, `public/index.php`.

- **Mezzio app**: PHP built-in server on port 8080 (forwarded by devcontainer)
  ```bash
  php -S 0.0.0.0:8080 -t /workspaces/farmers-store-inventory/public/
  ```
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
