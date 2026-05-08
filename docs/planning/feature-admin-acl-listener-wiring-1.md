---
goal: Wire webware-admin ACL resources, rules, and route mappings into webware-acl via PSR-14 listeners
version: 1.0
date_created: 2026-05-07
last_updated: 2026-05-07
owner: webware-admin / webware-acl
status: 'Planned'
tags: [feature, architecture, acl, events, listeners, caching, composer-plugin]
---

# Introduction

![Status: Planned](https://img.shields.io/badge/status-Planned-blue)

The `webware-acl` component fires PSR-14 lifecycle events during every ACL build
(`RolesLoadedEvent`, `ResourcesLoadedEvent`, `RulesLoadedEvent`, `AclBuiltEvent`).
This plan wires `webware-admin` ACL data — resources, privileges, allow rules, and
route→resource/privilege mappings — into the running Laminas ACL instance via listeners
rather than via the central DB seed, giving each module ownership of its own ACL
definitions.

The only structural gap is route mappings: the existing `AclBuiltEvent` only carries
`Acl $acl` and cannot communicate new route mappings back to `AclBuilder`. This plan
augments `AclBuiltEvent` to carry a mutable route mapping registry before writing the
admin listeners.

This document also records the caching strategy decision (§9) and a feasibility
assessment for a `webware/composer-plugin` that could automate module ACL registration
and other install-time tasks across the webware ecosystem (§10).

## 1. Requirements & Constraints

- **REQ-001**: `webware-admin` ACL resources, rules, and route mappings must be registered
  by listeners triggered from `webware-acl` lifecycle events — not via the central
  `999_seed.sql` file (existing rows may remain but new admin-specific entries must not be added there).
- **REQ-002**: The `AclBuiltEvent` must be augmented to carry a mutable route-mappings
  registry so that listener-registered route mappings are available to `AclFactory` via
  `AclBuilder::getRouteMappings()`.
- **REQ-003**: `webware-admin`'s `ConfigProvider` must register all listener classes under
  the `config['listeners']` key consumed by `ListenerProviderAggregateFactory`.
- **REQ-004**: All listener and factory classes must follow the existing namespace and DI
  conventions (`Container/` for factories, `Listener/` for listeners, `final readonly`
  classes, constructor property promotion).
- **REQ-005**: ACL data registered by listeners must be **additive only** — listeners must
  not remove or override DB-loaded roles, resources, or rules.
- **CON-001**: `ResourcesLoadedEvent` is fired after DB resources are loaded and is the
  correct hook for adding listener-provided resources (which rules will reference).
- **CON-002**: `RulesLoadedEvent` is fired after DB rules are applied and is the correct
  hook for adding listener-provided allow/deny rules.
- **CON-003**: Route mappings must be available from `AclBuilder::getRouteMappings()` before
  `AclFactory` reads them. Listeners must augment them during `AclBuiltEvent`.
- **CON-004**: `AclBuilder` is a `final` class — the augmentation must be via `AclBuiltEvent`
  data, not by subclassing.
- **GUD-001**: Follow the `declare(strict_types=1)` + `final` + constructor property promotion
  conventions used throughout the codebase.
- **GUD-002**: Every new service class must have a corresponding factory in `Container/`.
- **PAT-001**: Listener registration pattern follows the existing `commandbus-event.global.php`
  convention: `config['listeners'][EventClass::class] => [['listener' => ListenerClass::class, 'priority' => N]]`.

## 2. Implementation Steps

### Implementation Phase 1 — Augment `AclBuiltEvent` to carry mutable route mappings

- GOAL-001: Extend `AclBuiltEvent` so it holds the DB-loaded route mappings and exposes
  `addRouteMapping()` and `getRouteMappings()`, then update `AclBuilder::buildFromArrays()`
  to read back the (potentially listener-augmented) mappings from the event after dispatch.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-001 | Modify `src/webware-acl/src/Event/AclBuiltEvent.php`: add `private array $routeMappings` constructor parameter; add `addRouteMapping(string $routeName, string $resourceId, string $privilegeId): void` method; add `getRouteMappings(): array` method. Keep `public readonly Acl $acl`. | | |
| TASK-002 | Modify `AclBuilder::buildFromArrays()`: pass `$data['routeMappings']` as second constructor argument when instantiating `AclBuiltEvent`; after `$this->dispatch(new AclBuiltEvent(...))`, read `$event->getRouteMappings()` back into `$this->routeMappings`. Current code sets `$this->routeMappings = $data['routeMappings']` before the event — move the assignment to after the dispatch and read from the event. | | |
| TASK-003 | Update `src/webware-acl/src/Event/AclBuiltEvent.php` docblock to describe the new route mapping contract. | | |

### Implementation Phase 2 — Write webware-admin ACL listener classes

- GOAL-002: Create three focused listener classes in `webware-admin` — one per event type —
  each responsible for a single concern: resources, rules, or route mappings.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-004 | Create `src/webware-admin/src/Listener/RegisterAdminResourcesListener.php` in namespace `Webware\Admin\Listener`. Invokable on `ResourcesLoadedEvent`. Calls `$event->acl->addResource('admin.dashboard')`. No constructor dependencies. | | |
| TASK-005 | Create `src/webware-admin/src/Listener/RegisterAdminRulesListener.php`. Invokable on `RulesLoadedEvent`. Grants `Administrator` and `Developer` roles `allow` on `admin.dashboard / read`. Uses `$event->acl->allow(string $role, string $resource, string $privilege)`. No constructor dependencies. | | |
| TASK-006 | Create `src/webware-admin/src/Listener/RegisterAdminRouteMappingsListener.php`. Invokable on `AclBuiltEvent`. Calls `$event->addRouteMapping('admin.dashboard', 'admin.dashboard', 'read')`. No constructor dependencies. | | |

### Implementation Phase 3 — Wire factories and ConfigProvider

- GOAL-003: Register all three listener classes in `webware-admin`'s DI container and
  listener provider.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-007 | Create `src/webware-admin/src/Container/RegisterAdminResourcesListenerFactory.php` — returns `new RegisterAdminResourcesListener()` (no injected dependencies). | | |
| TASK-008 | Create `src/webware-admin/src/Container/RegisterAdminRulesListenerFactory.php` — returns `new RegisterAdminRulesListener()`. | | |
| TASK-009 | Create `src/webware-admin/src/Container/RegisterAdminRouteMappingsListenerFactory.php` — returns `new RegisterAdminRouteMappingsListener()`. | | |
| TASK-010 | Update `src/webware-admin/src/ConfigProvider.php`: add all three listener classes to `getDependencies()['factories']`; add `'listeners'` key to the array returned by `__invoke()` mapping each event class to its listener factory following the `['listener' => ListenerClass::class, 'priority' => 1]` shape. | | |

### Implementation Phase 4 — Remove duplicate data from seed

- GOAL-004: Remove the `admin.dashboard` resource, its privilege, and its ACL rules from
  `999_seed.sql` since they are now owned by the listener. This prevents double-registration
  on each ACL build. Leave `admin.user` and all non-admin entries intact.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-011 | Remove from `data/schema/999_seed.sql`: the `admin.dashboard` INSERT into `acl_resource`; the `admin.dashboard / read` INSERT into `acl_privilege`; the `Sales`/`Warehouse` allow rules for `dashboard / read`; and the `admin.dashboard` row in `acl_route_privilege`. Leave the `dashboard` route mapping (which maps to the `dashboard` resource, not `admin.dashboard`) unchanged. | | |
| TASK-012 | Re-run the DB seed against a dev database and verify no duplicate-key errors occur. Clear `data/cache/config-cache.php` after the change. | | |

### Implementation Phase 5 — Tests

- GOAL-005: Add unit tests for all new listener classes and verify the augmented `AclBuiltEvent`.

| Task | Description | Completed | Date |
|------|-------------|-----------|------|
| TASK-013 | Add `test/AsyncTest/` (or admin test path) `RegisterAdminResourcesListenerTest`: construct listener, create a mock `ResourcesLoadedEvent` with a real `LaminasAcl`, invoke listener, assert `$acl->hasResource('admin.dashboard')`. | | |
| TASK-014 | Add `RegisterAdminRulesListenerTest`: same pattern; pre-register `admin.dashboard` resource and `Administrator`/`Developer` roles on a bare `LaminasAcl`; invoke listener; assert `$acl->isAllowed('Administrator', 'admin.dashboard', 'read') === true` and `$acl->isAllowed('Manager', 'admin.dashboard', 'read') === false`. | | |
| TASK-015 | Add `RegisterAdminRouteMappingsListenerTest`: create a bare `AclBuiltEvent` with empty route mappings; invoke listener; assert `getRouteMappings()` contains the `admin.dashboard` entry. | | |
| TASK-016 | Add `AclBuiltEventTest`: assert `addRouteMapping()` appends correctly; assert constructor-injected route mappings are present in `getRouteMappings()`. | | |

## 3. Alternatives

- **ALT-001**: **Seed-only approach** — add all admin ACL data to `999_seed.sql`. Rejected
  because it couples module-owned data to a central file; every new admin module would
  require editing the seed rather than owning its own ACL definitions.
- **ALT-002**: **Single omnibus listener** — one listener class subscribes to all events and
  handles resources, rules, and route mappings together. Rejected because it violates single
  responsibility; each event hook has a distinct concern and timing requirement.
- **ALT-003**: **New dedicated `RegisterRouteMappingsEvent`** — fire a separate event solely
  for route mapping collection. Not chosen because augmenting the existing `AclBuiltEvent` is
  minimal and sufficient; a new event adds indirection without benefit at this stage.
- **ALT-004**: **Config-file route mappings** (e.g., `config['acl']['route_mappings']`) —
  merge listener-provided mappings from the DI config array in `AclFactory`. Rejected because
  it bypasses the event lifecycle design and creates a second registration path that has no
  ordering guarantee relative to the DB-loaded mappings.

## 4. Dependencies

- **DEP-001**: `laminas/laminas-permissions-acl` — `Laminas\Permissions\Acl\Acl::addResource()`,
  `allow()`, `deny()` — already a direct dependency of `webware-acl`.
- **DEP-002**: `phly/phly-event-dispatcher` — `ListenerProviderAggregateFactory` consumes
  `config['listeners']` — already wired in the application.
- **DEP-003**: `Webware\Acl\Event\ResourcesLoadedEvent` — event class already exists in
  `src/webware-acl/src/Event/`.
- **DEP-004**: `Webware\Acl\Event\RulesLoadedEvent` — already exists.
- **DEP-005**: `Webware\Acl\Event\AclBuiltEvent` — exists; will be modified in Phase 1.

## 5. Files

- **FILE-001**: `src/webware-acl/src/Event/AclBuiltEvent.php` — **modified** (Phase 1: add route mapping registry)
- **FILE-002**: `src/webware-acl/src/AclBuilder.php` — **modified** (Phase 1: read route mappings back from event)
- **FILE-003**: `src/webware-admin/src/Listener/RegisterAdminResourcesListener.php` — **new**
- **FILE-004**: `src/webware-admin/src/Listener/RegisterAdminRulesListener.php` — **new**
- **FILE-005**: `src/webware-admin/src/Listener/RegisterAdminRouteMappingsListener.php` — **new**
- **FILE-006**: `src/webware-admin/src/Container/RegisterAdminResourcesListenerFactory.php` — **new**
- **FILE-007**: `src/webware-admin/src/Container/RegisterAdminRulesListenerFactory.php` — **new**
- **FILE-008**: `src/webware-admin/src/Container/RegisterAdminRouteMappingsListenerFactory.php` — **new**
- **FILE-009**: `src/webware-admin/src/ConfigProvider.php` — **modified** (add factories + listeners key)
- **FILE-010**: `data/schema/999_seed.sql` — **modified** (remove admin.dashboard ACL rows)

## 6. Testing

- **TEST-001**: `RegisterAdminResourcesListenerTest` — verifies `admin.dashboard` resource
  is added to a bare Laminas Acl instance when the listener is invoked.
- **TEST-002**: `RegisterAdminRulesListenerTest` — verifies `Administrator` and `Developer`
  are allowed `admin.dashboard/read`; verifies `Manager` is **not** (inheritance must not
  accidentally grant lower roles access).
- **TEST-003**: `RegisterAdminRouteMappingsListenerTest` — verifies the `admin.dashboard`
  route mapping is added to `AclBuiltEvent::getRouteMappings()`.
- **TEST-004**: `AclBuiltEventTest` — verifies constructor-provided mappings are returned by
  `getRouteMappings()`; verifies `addRouteMapping()` appends without overwriting.
- **TEST-005**: Integration smoke test — boot the DI container, resolve `AclInterface`, call
  `isAllowedRoute()` for a mock request to `admin.dashboard` as `Administrator`; assert
  `true`. Repeat as `Sales`; assert `false`.

## 7. Risks & Assumptions

- **RISK-001**: If the DB seed already has `admin.dashboard` rows and the listener also
  registers them, Laminas ACL will throw on duplicate `addResource()`. Phase 4 (TASK-011)
  must be completed **before or simultaneously** with Phase 3. Mitigation: implement Phase 4
  first in any merge order.
- **RISK-002**: The `AclBuilder` uses a `FileAclCache`. After Phase 1 changes the shape of
  `AclBuiltEvent`, any stale cache file that predates the change is harmless (route mappings
  are re-read from the cache array in `buildFromArrays`, not from the event). No cache
  migration required.
- **RISK-003**: Listener priority ordering — if another module registers a `RulesLoadedEvent`
  listener that depends on `admin.dashboard` existing, it must run after
  `RegisterAdminResourcesListener`. Both default to priority 1, which is sufficient for now
  since no such cross-module dependency currently exists.
- **ASSUMPTION-001**: `Manager` role does **not** inherit access to `admin.dashboard`.
  Access is granted explicitly to `Administrator` and `Developer` only in the listener.
  `Manager` inherits from lower roles and must be explicitly granted admin access elsewhere
  if ever needed.
- **ASSUMPTION-002**: The `ListenerProviderAggregateFactory` consumed by `phly/phly-event-dispatcher`
  accepts the `['listener' => FQCN, 'priority' => N]` shape used in
  `commandbus-event.global.php`. This is the existing working pattern; no change needed.

## 9. Caching Strategy: Event-only vs Write-to-DB

### Background

`AclBuilder::build()` has two paths:
- **Cache hit**: `fetchVersion()` matches → `buildFromArrays($cached)` — zero additional DB queries.
- **Cache miss / stale**: full DB load → `cache->set($data)` → `buildFromArrays($data)`.

`buildFromArrays()` is called on **both** paths and fires all five PSR-14 events every
time. The `FileAclCache` stores only the raw DB arrays; it has no knowledge of
listener-contributed data.

### Approach A — Event-only (chosen for this plan)

Listeners apply resources, rules, and route mappings directly to the in-memory
Laminas `Acl` instance on every `buildFromArrays()` call. The cache is an explicit
**DB-only snapshot**; listener data is re-applied from PHP code on every build.

| Pros | Cons |
|---|---|
| Zero DB coupling — module owns ACL definitions entirely in code | Cache is a partial snapshot of the running ACL, not a complete one |
| No install-time step, no "already registered?" detection | Route mappings from listeners are absent from the cache file |
| Removing a module automatically removes its ACL rules | An admin UI querying the DB for resources/rules will see incomplete data |
| Listeners are stateless, pure-PHP, trivially unit-tested | `buildFromArrays()` runs listener code on every request — even on cache hit |
| Cache invalidation is irrelevant for listener data | |

**Chosen for this plan** because `buildFromArrays()` already runs on every request,
listener code is pure PHP with no I/O, no admin UI yet exists that queries the DB
for ACL data, and the migration path to Approach B is clear once a module
installation mechanism is designed (see §10).

**Architectural constraint to document**: the `FileAclCache` is explicitly a
**DB-state cache only**. Code consuming it must not assume it represents the
complete running ACL.

### Approach B — Write-once to DB on module installation

A registration event (or install command) fires once per module. Listeners write
their resources, rules, and route mappings to the DB. The ACL version counter is
incremented. All subsequent builds flow through the normal DB → cache path.

| Pros | Cons |
|---|---|
| Cache is a complete snapshot — no code-path divergence after first registration | Registration trigger is complex: first-request race, CLI step, or boot-time check |
| DB is single source of truth — admin UI and debug queries see all ACL data | Listeners need `AclRepositoryInterface` injected — DB write coupling, harder to test |
| After first registration, zero event dispatch on normal request path | Module updates that add new resources require re-registration detection |
| Route mappings fully in cache | Orphaned DB rows if a module is removed without an uninstall step |

### Approach C — Hybrid (event-always + augment the cache)

Fire all events, then serialize the complete augmented state (DB + listener data)
back into the cache. Cache stores the merged result.

| Pros | Cons |
|---|---|
| Cache is complete; zero DB reads on cache hit | Requires serializing what listeners added back to raw arrays — non-trivial |
| | `buildFromArrays()` must output augmented data arrays, not just the Laminas instance |

Rejected for now due to implementation complexity. Revisit if admin UI requirements
demand a complete DB-queryable ACL representation.

### Decision

**Approach A** is implemented in this plan. If a `webware/composer-plugin` (§10) is
built to handle install-time DB writes, **Approach B** becomes the natural migration
target — the listener classes created in this plan would be repurposed as install
handlers rather than request-time handlers.

---

## 10. Composer Plugin Feasibility: `webware/composer-plugin`

### What a Composer plugin could do for the webware ecosystem

A dedicated `webware/composer-plugin` package (type: `composer-plugin`) would hook
into Composer's `post-package-install`, `post-package-update`, and
`post-package-uninstall` events and automate tasks that currently require manual
steps when a webware module is added to a project:

- **ACL registration** — write module-declared resources, rules, and route mappings
  to the DB (enabling Approach B above)
- **Schema migration** — apply a module's SQL migration files in order
- **Config stub publishing** — copy `config/autoload/*.global.php.dist` stubs to the
  application's `config/autoload/` directory if not already present
- **Cache invalidation** — delete `data/cache/config-cache.php` and
  `data/cache/acl.cache` after any webware module install/update
- **Asset publishing** — symlink or copy `public/assets/` from the module package

### Precedent already in this project

`laminas/laminas-component-installer` is already a direct dependency. It is itself a
Composer plugin (type `composer-plugin`) that reads `extra.laminas.component` from
installed packages and automatically splices their `ConfigProvider` class names into
`config/config.php`. The `webware/composer-plugin` would follow the same pattern but
target `extra.webware` rather than `extra.laminas`.

### Feasibility assessment

| Concern | Assessment |
|---|---|
| **API stability** | Composer Plugin API is stable at `2.6.0`; `PluginInterface` + `EventSubscriberInterface` are the stable extension points. Low breakage risk. |
| **Package events** | `post-package-install` / `post-package-update` / `post-package-uninstall` provide exactly the right hooks. Each event delivers `$event->getOperation()->getPackage()` to identify the installed package and read its `extra` config. |
| **Bootstrap challenge** | The plugin runs inside the Composer process — the application's DI container is **not available**. DB writes must use raw PDO constructed from the project's config files (e.g., read `config/autoload/*.php` directly and instantiate `PDO`). This is the primary complexity. |
| **First-install ordering** | `post-package-install` fires per-package. The plugin must itself be installed before other webware modules. Declare it as a direct `require` dependency in the root `composer.json`. For path-repository monorepos (current setup), all packages are "installed" in a single pass — ordering must be handled by checking `allow-plugins`. |
| **`allow-plugins` requirement** | Composer 2.2+ requires explicit opt-in in the root `composer.json`: `"config": {"allow-plugins": {"webware/composer-plugin": true}}`. Must be documented for consumers. |
| **Idempotency** | All operations must be safe to re-run on `composer update`. Schema migrations need a migrations table or file-hash check. ACL registration needs an upsert pattern (already used in `999_seed.sql`). |
| **Uninstall** | `post-package-uninstall` should remove the module's ACL rows and route mappings from the DB. Schema rollback is risky; recommend leaving tables in place and only removing ACL data. |
| **Testability** | Plugin logic should be extracted into plain PHP service classes testable without Composer internals. The plugin class itself is thin glue. |
| **Scope creep risk** | Starting with only cache invalidation + config stub publishing is low-risk. DB writes (ACL registration, schema) should be a second iteration once the plugin scaffold is proven. |

### Recommended module manifest shape (`extra.webware` in a module's `composer.json`)

```json
{
  "extra": {
    "webware": {
      "config-provider": "Webware\\Admin\\ConfigProvider",
      "migrations": ["data/schema/001_admin.sql"],
      "acl": {
        "resources": [
          {"resource_id": "admin.dashboard", "label": "Admin Dashboard"}
        ],
        "rules": [
          {"role": "Administrator", "resource": "admin.dashboard", "privilege": "read", "type": "allow"},
          {"role": "Developer",     "resource": "admin.dashboard", "privilege": "read", "type": "allow"}
        ],
        "route_mappings": [
          {"route": "admin.dashboard", "resource": "admin.dashboard", "privilege": "read"}
        ]
      }
    }
  }
}
```

### Relationship to this plan

The listener-based approach (Approach A, Phases 1–5 of this plan) should be
implemented now. If `webware/composer-plugin` is built later, the module ACL data
currently declared in listener PHP classes would be moved to `extra.webware.acl` in
each module's `composer.json`, and the listeners would be retired. The `AclBuiltEvent`
route mapping augmentation (Phase 1 of this plan) would also be retired —
route mappings would come from the DB via the plugin-written rows.

### Verdict

**Feasible and worthwhile**, but should be a separate package and a separate
implementation plan. The bootstrap challenge (no DI container at plugin runtime) is
the main complexity to design around. Suggested package name: `webware/composer-plugin`.
Suggested first milestone: cache invalidation only (no DB writes). Second milestone:
ACL + route mapping DB writes. Third milestone: schema migration runner.

---

## 8. Related Specifications / Further Reading

- [AclBuilder source](../../src/webware-acl/src/AclBuilder.php)
- [FileAclCache](../../src/webware-acl/src/Cache/FileAclCache.php)
- [Existing event classes](../../src/webware-acl/src/Event/)
- [AclFactory](../../src/webware-acl/src/Container/AclFactory.php)
- [Existing listener registration pattern](../../config/autoload/commandbus-event.global.php)
- [webware-admin ConfigProvider](../../src/webware-admin/src/ConfigProvider.php)
- [Dashboard widget system docs](../../src/webware-admin/docs/dashboard-widget-system.md)
- [Composer Plugin API docs](https://getcomposer.org/doc/articles/plugins.md)
- [Composer Script Event names](https://getcomposer.org/doc/articles/scripts.md#event-names)
- [laminas-component-installer](https://github.com/laminas/laminas-component-installer) — existing precedent for a Composer plugin in this stack
