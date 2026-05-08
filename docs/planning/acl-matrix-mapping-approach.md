---
title: ACL Role / Resource / Privilege Matrix — Mapping Approach
date_created: 2026-05-08
last_updated: 2026-05-08
status: Agreed
owner: tyrsson
---

# ACL Role / Resource / Privilege Matrix — Mapping Approach

> **Purpose:** Document the agreed conventions for mapping modules into the
> role → resource → privilege matrix. This is the authoritative reference for
> all Block 1+ implementation — no ambiguity between design intent and code.

---

## 1. Overview

Each application module is structured as a `{Domain}Manager`
(e.g. `ManifestManager`, `TransferManager`, `UserManager`). Every manager
has two logical faces:

- **Public-facing** — routes and handlers accessible to authenticated users
  within the bounds of their role (e.g. warehouse staff processing a manifest).
- **Admin-facing** — routes and handlers under `/admin/{module}.*` that expose
  management UI for that domain. These are guarded by `AuthorizationMiddleware`
  and require explicit ACL grants.

`webware-admin` owns the `/admin` base route and the dashboard. Each module
plugs its admin area into `/admin/{module}.*` and contributes a widget to the
admin dashboard via a PSR-14 listener on `RegisterWidgetEvent`.

Modules are designed to be portable — each is an independently usable Composer
package that wires itself into any Mezzio application via its `ConfigProvider`.

---

## 2. Data Model

The ACL is backed by five tables:

| Table | Purpose |
|---|---|
| `acl_role` | Role definitions (`role_id` is the Laminas ACL role string, e.g. `'Sales'`) |
| `acl_resource` | Resource definitions (`resource_id` is the ACL resource string, e.g. `'admin.manifest'`) |
| `acl_privilege` | Privilege definitions scoped to a resource (`privilege_id` e.g. `'read'`, `'create'`) |
| `acl_rule` | Allow/deny mappings: `role_pk → resource_pk → privilege_pk → type` |
| `acl_route_privilege` | Route → resource + privilege binding: `route_name → resource_pk → privilege_pk` |

`acl_version` (integer counter in a settings table or standalone row) is
incremented on any ACL data change, triggering a cache rebuild on the next
request.

---

## 3. Role Hierarchy

Roles are stored in the `role` table with parent relationships in
`acl_role_parent`. Laminas ACL resolves inherited grants automatically —
granting a privilege to a parent role propagates to all children unless
explicitly denied at a child level.

```
guest                   ← unauthenticated; granted: public/read, user/login, user/register
  └── Sales             ← lowest authenticated role; denied: login, register
        └── Warehouse
              └── DC Warehouse
                    └── Credit Manager
                          └── Warehouse Supervisor
                                └── Manager
                                      └── Administrator
                                            └── Developer   ← top of hierarchy; denied: login, register
```

> Role names use Title Case with spaces exactly as stored in the DB. No
> normalisation between DB and ACL — Laminas ACL accepts them as-is.

---

## 4. Naming Conventions

### 4.1 URL Path Convention

Module admin areas use a **dot separator** in the URL path:

```
/admin/{module.name}            ← base GET route (overview/dashboard)
/admin/{module.name}/{action}   ← sub-routes
```

Examples:
```
/admin/manifest.manager
/admin/manifest.manager/create
/admin/transfer.manager
/admin/user.manager
/admin/acl.manager
```

The dot is chosen deliberately so that the URL segment mirrors the
`resourceId` and route name, making it easy for developers to trace the
full path from URL → route name → resource → privilege.

### 4.2 Route Name Convention

FastRoute route names follow `admin.{module}` for the base route and
`admin.{module}.{action}` for sub-routes:

```
admin.manifest                  ← GET /admin/manifest.manager
admin.manifest.create           ← GET /admin/manifest.manager/create
admin.manifest.create.post      ← POST /admin/manifest.manager/create
admin.manifest.edit             ← GET /admin/manifest.manager/{id}/edit
admin.manifest.edit.post        ← PUT/PATCH /admin/manifest.manager/{id}/edit
admin.manifest.delete           ← DELETE /admin/manifest.manager/{id}
```

### 4.3 Resource ID Convention

Every module admin area maps to a single ACL resource named `admin.{module}`:

```
admin.dashboard     ← owned by webware-admin
admin.acl           ← owned by webware-acl
admin.manifest      ← owned by webware-manifestmanager
admin.transfer      ← owned by webware-transfermanager
admin.user          ← owned by webware-usermanager (future rename)
```

### 4.4 Privilege Convention

Every module admin resource exposes the CRUD baseline by default, plus any
module-specific extensions:

| Privilege | Meaning |
|---|---|
| `read` | View lists, detail pages, overview |
| `create` | Add new records |
| `update` | Edit existing records |
| `delete` | Remove records |
| *(module-specific)* | e.g. `process` for manifest scanning, `approve` for transfers |

---

## 5. Route → Resource + Privilege Mapping

Each route name is bound to exactly one `(resource_id, privilege_id)` pair in
`acl_route_privilege`. `AuthorizationMiddleware` looks up the matched route
name, resolves its resource + privilege, and calls `$acl->isAllowed($role,
$resource, $privilege)`.

Mapping pattern:

```
admin.manifest              → admin.manifest / read
admin.manifest.create       → admin.manifest / create
admin.manifest.create.post  → admin.manifest / create
admin.manifest.edit         → admin.manifest / update
admin.manifest.edit.post    → admin.manifest / update
admin.manifest.delete       → admin.manifest / delete
```

POST routes share the privilege of their corresponding GET route because the
ACL check is on intent (what the user is doing) not HTTP method.

---

## 6. Module Directory & Namespace Structure

```
src/webware-{module}manager/src/
  ConfigProvider.php
  RouteProvider.php
  Admin/
    RequestHandler/
      {Module}ManagerHandler.php       ← base GET /admin/{module}.manager
      {Module}Create{Action}Handler.php
      ...
    Listener/
      Register{Module}ResourcesListener.php
      Register{Module}RulesListener.php
      Register{Module}RouteMappingsListener.php
      Register{Module}WidgetListener.php
    Container/
      ...Factories...
    Widget/
      {Module}DashboardWidget.php
  RequestHandler/          ← public-facing handlers
    ...
```

Namespace: `Webware\{Module}Manager\Admin\RequestHandler\{Module}ManagerHandler`

---

## 7. Listener-Based ACL Registration

**Rule: Every module registers its own ACL data via PSR-14 listeners.
No module-specific ACL rows belong in `999_seed.sql`.**

`999_seed.sql` is reserved for:
- Core role hierarchy
- Core public resources (`public`, `user`)
- Core deny rules (authenticated roles denied `login`/`register`)

Every module `ConfigProvider` registers three listeners under the
`config['listeners']` key:

| Listener | Event | Responsibility |
|---|---|---|
| `Register{Module}ResourcesListener` | `ResourcesLoadedEvent` | `$event->acl->addResource('admin.{module}')` |
| `Register{Module}RulesListener` | `RulesLoadedEvent` | Allow `Administrator` (+ `Developer`) all CRUD privileges |
| `Register{Module}RouteMappingsListener` | `AclBuiltEvent` | `$event->addRouteMapping(...)` for each route |
| `Register{Module}WidgetListener` | `RegisterWidgetEvent` | `$event->registerWidget(new {Module}DashboardWidget())` |

> **Exception — `webware-acl` (`admin.acl` resource):**
> `RegisterAclRulesListener` grants CRUD privileges to **`Developer` only**.
> `Administrator` is explicitly **not** granted access to the ACL Manager.
> Rationale: modifying roles, rules, and route mappings requires deep
> understanding of the ACL model. Granting this to Administrator by default
> would allow accidental privilege escalation or broken access control.
> An Administrator who also needs ACL access should be promoted to Developer.

This pattern keeps every module self-contained. Adding a new module to an
application requires only:
1. Adding its `ConfigProvider` to `config/config.php`
2. Running a DB seed (or migration) to create role grants for that module

---

## 8. Caching Contract

`FileAclCache` is a **DB-state-only** cache. It stores the raw arrays loaded
from the five ACL tables and is invalidated by incrementing `acl_version`.

Listener-contributed data (resources, rules, route mappings) is **not** written
to the cache. It is re-applied on every request via `buildFromArrays()` after
the cache arrays are loaded. This is intentional — listener data is code, not
DB state, so it does not need cache invalidation.

```
Every request:
  1. fetchVersion() — 1 DB query
  2. cache hit?  → buildFromArrays(cached arrays)
                 → fire all 5 events (listener data re-applied)
  3. cache miss? → fetch all 5 tables → set cache → buildFromArrays()
                 → fire all 5 events (listener data re-applied)
```

Future migration to Approach B (listener data written to DB via
`webware/composer-plugin`) is planned but deferred.

---

## 9. Open Questions

~~Should the `dashboard` resource be renamed to `admin.dashboard`?~~
**Resolved 2026-05-08:** `dashboard` and `admin.dashboard` are two entirely
distinct resources serving different purposes and must both exist:

| Resource | Route name | Handler | Owner | Purpose |
|---|---|---|---|---|
| `dashboard` | `dashboard` | `App\RequestHandler\DashboardHandler` | `src/App` | Main app home / inventory summary view |
| `admin.dashboard` | `admin.dashboard` | `Webware\Admin\RequestHandler\DashboardHandler` | `webware-admin` | Admin widget aggregation page |

The seed's `dashboard` resource, privilege, rules, and route mapping are
correct and must not be touched. `admin.dashboard` does not exist in the
seed — it will be registered exclusively by
`RegisterAdminResourcesListener` on `ResourcesLoadedEvent`.

**Sprint Task 1.8 consequence:** There are no `admin.dashboard` rows to
remove from `999_seed.sql`. Task 1.8 is a no-op and can be dropped from
the sprint plan.

---

## 10. Decision Log

| Date | Decision | Rationale |
|---|---|---|
| 2026-05-08 | URL separator = dot (`.`) not dash | Mirrors resourceId and route name; easier to trace |
| 2026-05-08 | ResourceId = `admin.{module}` | One resource per module admin area; clean and predictable |
| 2026-05-08 | Privileges = CRUD baseline + module-specific extensions | Covers all standard operations; extensible |
| 2026-05-08 | Route names = `admin.{module}` / `admin.{module}.{action}` | Consistent; mirrors URL and resourceId |
| 2026-05-08 | Module ACL registered by listeners only — no seed rows | Keeps modules self-contained and portable |
| 2026-05-08 | `admin.acl` granted to `Developer` only — not `Administrator` | ACL mutation requires deep system knowledge; default grants to Administrator would risk accidental privilege escalation |
| 2026-05-08 | FileAclCache = DB-state only (Approach A) | Listener data is code; does not need cache invalidation |
| 2026-05-08 | `dashboard` ≠ `admin.dashboard` — both resources must exist | `dashboard` = main app home; `admin.dashboard` = admin widget page (webware-admin) |
| 2026-05-08 | Sprint Task 1.8 is a no-op — no seed rows to remove | `admin.dashboard` was never in the seed; listener adds it fresh |
