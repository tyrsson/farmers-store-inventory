---
applyTo: "src/**/*.php,config/**/*.php,test/**/*.php"
---

# Vendor Component Documentation Reference

Quick reference for canonical documentation of every major dependency used in
this project. Consult these before implementing against any listed component.

---

## PHP Runtime

| Component | Version | Docs |
|---|---|---|
| **TrueAsync** (php-async extension) | PHP 8.6-dev | https://true-async.github.io/en/docs.html |
| TrueAsync API quick-ref | — | `docs/planning/php-async-api.md` (workspace) |
| TrueAsync known bugs | — | `docs/planning/trueasync-bugs.md` (workspace) |

---

## Mezzio Framework

| Component | Constraint | Docs |
|---|---|---|
| **mezzio/mezzio** | ^3.26 | https://docs.mezzio.dev/mezzio/ |
| **mezzio/mezzio-fastroute** | ^3.14 | https://docs.mezzio.dev/mezzio/v3/features/router/fast-route/ |
| **mezzio/mezzio-helpers** | ^5.20 | https://docs.mezzio.dev/mezzio/v3/cookbook/helpers/ |
| **mezzio/mezzio-laminasviewrenderer** | ^3.0 | https://docs.mezzio.dev/mezzio/v3/features/template/laminas-view/ |

### Mezzio Auth & Authorization
These packages are **not yet in composer.json** — to be added during auth module work.

| Component | Docs |
|---|---|
| **mezzio/mezzio-authentication** | https://docs.mezzio.dev/mezzio-authentication/ |
| **mezzio/mezzio-authorization** | https://docs.mezzio.dev/mezzio-authorization/ |
| **laminas/laminas-permissions-acl** | https://docs.laminas.dev/laminas-permissions-acl/ |

> The `mezzio-authorization` adapter for `laminas-acl` is
> `mezzio/mezzio-authorization-acl`. Role names support spaces — store them
> exactly as they appear in the `role` table (e.g. `'Warehouse Supervisor'`).
> No normalisation is needed between DB and ACL.

---

## Laminas Components

| Component | Constraint | Docs |
|---|---|---|
| **laminas/laminas-view** | ^3.0 | https://docs.laminas.dev/laminas-view/ |
| **laminas/laminas-servicemanager** | ^4.5 | https://docs.laminas.dev/laminas-servicemanager/ |
| **laminas/laminas-config-aggregator** | ^1.19 | https://docs.laminas.dev/laminas-config-aggregator/ |
| **laminas/laminas-diactoros** | ^3.8 | https://docs.laminas.dev/laminas-diactoros/ |
| **laminas/laminas-stdlib** | ^3.21 | https://docs.laminas.dev/laminas-stdlib/ |

---

## Internal / Workspace Packages

| Package | Namespace | Location |
|---|---|---|
| **mezzio-async** | `Mezzio\Async` | `src/mezzio-async/src/` |
| **phpdb-async** | `PhpDb\Async` | `src/phpdb-async/src/` |
| **App module** | `App` | `src/App/src/` |
| **Htmx module** | `Htmx` | `src/Htmx/src/` |

### mezzio-async
Custom Mezzio integration for the TrueAsync runtime. See:
- Architecture instructions: `.github/instructions/mezzio-async-architecture.instructions.md`
- HTTP server instructions: `.github/instructions/http-server-implementation.instructions.md`
- TrueAsync primitives: `.github/instructions/php-async-primitives.instructions.md`

### phpdb-async
Async-aware database abstraction (webware/phpdb fork). MySQL driver is in
`require-dev` as `php-db/phpdb-mysql` `0.4.x-dev`. No X Protocol — uses
`pdo_mysql` + TrueAsync PDO Pool. See `src/phpdb-async/src/` for current
adapters (Pdo/, Pgsql/).

---

## Axleus Packages (Internal)

| Package | Constraint | Purpose |
|---|---|---|
| **axleus/axleus-log** | 0.0.x-dev | Monolog integration; adds StreamHandlers to file + stderr |
| **axleus/axleus-mailer** | dev-master | PHPMailer wrapper; used for PQA damage image emails |
| **axleus/axleus-message** | dev-master | Flash message / notification support |

---

## Command Bus

| Package | Constraint | Docs |
|---|---|---|
| **webware/commandbus-event** | 0.1.x-dev | Internal package — no public docs; see `vendor/webware/` |

> **IMPORTANT:** `vendor/webware/*`, `vendor/phpdb/*`, and `vendor/axleus/*` are all
> maintained by the project owner. Do **not** assume API shape, config keys, or
> behaviour for any of these packages. If the source code or existing workspace usage
> does not make the answer clear, **ask before implementing**.

---

## Frontend

| Component | Version | CDN / Source |
|---|---|---|
| **Bootstrap** | 5.3.3 | https://getbootstrap.com/docs/5.3/ — CDN: `cdn.jsdelivr.net/npm/bootstrap@5.3.3` |
| **Bootstrap Icons** | 1.13.1 | https://icons.getbootstrap.com/ — CDN: `cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1` |
| **HTMX** | latest stable | https://htmx.org/docs/ |
| **Chart.js** | 4.4.3 | https://www.chartjs.org/docs/latest/ — analytics page only |
| **ZXing-js** | @zxing/library | https://github.com/zxing-js/library — Code 128B barcode scanning via device camera |

### HTMX rendering stack
This project uses a custom 3-layer rendering stack (layout / body / page).
See `.github/skills/htmx-mezzio/SKILL.md` before implementing any handler,
page, partial, or template.

---

## Dev Tooling

| Tool | Constraint | Docs |
|---|---|---|
| **phpunit/phpunit** | ^13.0 | https://docs.phpunit.de/en/11.0/ |
| **phpstan/phpstan** | ^2.1 | https://phpstan.org/user-guide/getting-started |
| **webware/coding-standard** | ^0.1.0 | Internal — enforces PER 3.0 via php-cs-fixer |

---

## Key Config & Conventions

- **Config key:** `mezzio-async` (server settings under `mezzio-async.http-server`)
- **Module pattern:** each namespace in `src/{Module}` exposes its own `ConfigProvider`
- **DI:** laminas-servicemanager v4 — every service has a `*Factory`; no service locator
- **DB config key:** `db` (to be confirmed when MySQL module is wired)
- **Log dir:** `data/psr/log/`
- **Image storage root:** local filesystem (configurable path, v1)
- **Schema files:** `data/schema/` — numbered `001`–`014` by FK dependency order; `999_seed.sql`
