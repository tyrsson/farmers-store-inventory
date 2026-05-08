# Overview

The Webware\Acl component will serve as a bridge component between several underlying components. Those being Laminas\Permissions\Acl, Mezzio\Authorization and Mezzio\Authorization\Acl. Currently all of these components are present in /vendor. Webware\Acl may, in the end, replace one or more of these components. Webware\Acl will provide a wrapper around Laminas\Permissions\Acl to provide a mechanism to support a database (mysql via php-db/phpdb-mysql, or more concisely any of the php-db drivers) driven Access Control List for applications built around the Laminas/Mezzio ecosystem. Therefore Webware\Acl MUST comply with the UserInterface constraints of Mezzio\Authentication\UserInterface and Laminas\Permissions\Acl\AclInterface. These will be the two primary interfaces we will be building against.

## Complicating Factors

Due to the way Mezzio decorates its PSR RequestHandlers we will not have direct runtime access to the RequestHandler instance in the pipeline which makes it complicated for Middleware and Request Handlers to implement the Laminas\Permissions\Acl\Resource interfaces so that they can be passed as arguments to the AclInterface::isAllowed method to be queried against to determine is a given user is to be granted access.

To mitigate this design restriction Mezzio\Authorization\Acl enforces a requirement that access be granted solely based on the route name as the known "resource". This is extremely limiting when it comes to building applications that require access control list.

## Mitigating Factors

Mezzio Router component exposes a RouteCollector which can be queried for a compiled list of routes. We will need to provide a mechanism to compare this list with those that have already been saved as "resources" in the db if that is the direction we take.

### Design Consideration

We will need to provide a UI for managing Roles, Resources and Privileges and the assigning of those to users etc. Laminas\Permissions\Acl's design provides for role inheritance. This means that the base role will be "guest" which is a unauthenticated user. This is the "default" role. There is 3 privileges that will be granted to "guest". Those will be read or view, login and register. This provides a means to show/hide the various UI controls/links etc based on a users current authenticated state. The next level roles will inherit from this role but will be denied the login and register privs. This will then limit access for users to create more than a single account (while they are logged in) or be presented with a login option. This approach works well and I have used it in many various systems I have built using laminas/mezzio frameworks.

This module will have its own dashboard template to provide the management UI behind a /admin/access or similar route. We will strive to provide a drag and drop level of interactivity to make the management of this module as simple as possible for users. The module will need to provide a collection of middleware, handlers, templates etc to facilitate the creation of the modules dashboard template. The module, if needed, can also provide a custom layout to facilitate further customization of the template layer but only if absolutely required.

Based on this overview we will create a indepth requirements list and flesh out the rest of the considerations before starting any code creation.

---

## Open Design Questions

### Q1 — Package location and ownership
Is `Webware\Acl` a new standalone Composer package (e.g. `webware/acl`) that will live in its own repo and be pulled in via `vendor/`, or will it live as a `src/Acl/` module inside this application repo (like `src/User/`)?

**Answer:**
It will start as an application level module, but if you check the base architecture I have already setup, just the namespace and directory with its own ConfigProvider you will see I have set it up so that it can be moved to its own repo for consumption by other applications.
---

### Q2 — Resource granularity
The doc notes that route-name-only access control is "extremely limiting." What level of granularity is required? Options:
- a) Route name only (current `mezzio-authorization-acl` approach)
- b) Route name + HTTP method (GET vs POST treated separately)
- c) Route name + arbitrary named privileges (e.g. `user.list` → `view`, `export`)
- d) Full resource/privilege matrix independent of routes (e.g. `Product` resource with `create`, `read`, `update`, `delete` privileges)

**Answer:**
D, but there must be an intersection with route to prevent the loading of application endpoints by direct access.
---

### Q3 — `isAllowed` call site
Where does the ACL check actually fire at request time — a single `AuthorizationMiddleware` in the pipeline that checks the matched route, per-handler logic, or both?

**Answer:**
At minimum both. The check via middleware will be the general safety catch. All CommandBus commands may have an assiociated privilege assigned (by CommandName). The simplest example would be that "Manifest Manager" would expose a get route (access granted by the manifest manager read privilege). The Manifest managers update.manifest route would be granted by the manifest managers update privilege.
---

### Q4 — Dynamic role/resource loading
`Laminas\Permissions\Acl` is designed to be configured once then queried. Since roles and resources come from the DB, when should they be loaded into the ACL instance — on every request, or once at container build time (and optionally cached)?

**Answer:**
Since this will need to be adaptive, ie we will need to be able to add roles and privileges I think it would be best read/construct the tree once and cache it. Then all of the management routines for the ACL could bust that cache and rebuild it anytime something is modified. This would allow us to build the list of route names from the RouteCollector and present them for assigning to roles/resources essentially as the privileges. The complex part of that is that rarely is there just 4 endpoints for the management of data of this complexity so all of this will evolve as we start building it. We are mostly trying to nail down a baseline starting point.
---

### Q5 — `UserInterface` roles
`Mezzio\Authentication\UserInterface::getRoles()` returns an iterable of role strings. Can a user have multiple roles, or always exactly one? Does Laminas role inheritance handle the "effective permissions of multiple roles" case, or is explicit multi-role assignment also needed?

**Answer:**
Laminas\Permissions\Acl handles role inheritance so effectively each user will only ever be assigned a single role in practice. ```php $user['roles'] = ['Administrator'];``` would grant them all privileges to all resources that is assigned to their role and all of the ones Administrator inherits from unless denied by a inherited role and not explicitly granted by Administrator.
---

### Q6 — Management UI interactivity
Is the drag-and-drop management UI intended to be HTMX-driven (consistent with the rest of the application), or is a JS-heavier approach (e.g. Sortable.js) acceptable for this module?

**Answer:**
I have used Sortable.js a long with Htmx in past projects. The htmx docs has an example on how to get them to play nice :).
---

### Q7 — Scope of this planning session
Should we produce a complete requirements/implementation plan document first and then begin code, or plan and implement in the same pass?

**Answer:**
We will build a "complete as possible" requirements/implementation plan first.

---

## Implementation Plan

### Package Identity

| Item | Value |
|------|-------|
| PHP namespace | `Webware\Acl` |
| Package root | `src/webware-acl/src/` |
| Composer autoload | `"Webware\\Acl\\": "src/webware-acl/src/"` |
| Future package name | `webware/acl` |
| Config key | `webware-acl` |

---

### Dependency Contracts

`Webware\Acl` builds against these interfaces — no others:

| Interface | Package | Purpose |
|-----------|---------|---------|
| `Laminas\Permissions\Acl\AclInterface` | `laminas/laminas-permissions-acl` | Core ACL operations |
| `Laminas\Permissions\Acl\Role\RoleInterface` | `laminas/laminas-permissions-acl` | Role value objects |
| `Laminas\Permissions\Acl\Resource\ResourceInterface` | `laminas/laminas-permissions-acl` | Resource value objects |
| `Mezzio\Authentication\UserInterface` | `mezzio/mezzio-authentication` | Read `getRoles()` at check time |

The existing `LaminasAcl`, `LaminasAclFactory`, and `AuthorizationMiddleware` from vendor are **not** used — `Webware\Acl` provides its own middleware that calls `$acl->isAllowed()` directly.

> **Note**: Whether to implement `Mezzio\Authorization\AuthorizationInterface` is **deferred**. We will decide once the core ACL and middleware are working.

---

### Role Hierarchy

```
guest  (unauthenticated)
  └── Sales
  └── Warehouse
        └── Warehouse Supervisor
              └── Credit Manager
              └── DC Warehouse
                    └── Manager
                          └── Administrator
```

- `guest` is a **regular row in the `role` table** — configurable via the management UI like any other role. It is not hardcoded in the application. It just happens to be the right base role for this application.
- Every authenticated role inherits from `guest` but is **denied** the `login` and `register` privileges.
- Role hierarchy is stored in the DB and drives what is loaded into the Laminas `Acl` instance.
- Because unauthenticated requests must still be assigned a role for ACL checks, we need **custom authentication middleware** that assigns the configured base role (default: `guest`) when no authenticated user is present. This cannot be done with the vanilla `mezzio-authentication` adapters — they produce a `DefaultUser` with empty roles on failure.

---

### Resource and Privilege Model

Resources are named logical domains, not routes. A route maps *to* a resource+privilege pair.

**Example resources:** `public`, `user`, `manifest`, `product`, `ticket`, `transfer`, `acl`

**Standard privileges (apply to most resources):**
- `read`
- `create`
- `update`
- `delete`

**Special privileges (on `user` resource):**
- `login`
- `register`

**`guest` default grants:**
- `allow: guest → public → read`
- `allow: guest → user → login`
- `allow: guest → user → register`

**All authenticated roles additionally:**
- `deny: [role] → user → login`
- `deny: [role] → user → register`

---

### Database Schema — New Tables

> **Column naming convention**: String identifier columns used directly by Laminas ACL are named `role_id`, `resource_id`, `privilege_id` (VARCHAR). Integer surrogate PK/FK columns used for relational joins are named `role_pk`, `resource_pk`, `privilege_pk` to avoid collision.

#### `acl_resource`
```sql
CREATE TABLE acl_resource (
    resource_pk  SMALLINT UNSIGNED AUTO_INCREMENT,
    resource_id  VARCHAR(100) NOT NULL,   -- e.g. 'ManifestManager' — passed to Laminas ACL
    label        VARCHAR(100) NOT NULL,   -- display label for UI
    PRIMARY KEY (resource_pk),
    UNIQUE KEY uq_resource_id (resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `acl_privilege`
```sql
CREATE TABLE acl_privilege (
    privilege_pk  SMALLINT UNSIGNED AUTO_INCREMENT,
    resource_pk   SMALLINT UNSIGNED NOT NULL,
    privilege_id  VARCHAR(100) NOT NULL,  -- e.g. 'create' — passed to Laminas ACL
    label         VARCHAR(100) NOT NULL,
    PRIMARY KEY (privilege_pk),
    UNIQUE KEY uq_resource_privilege (resource_pk, privilege_id),
    CONSTRAINT fk_priv_resource FOREIGN KEY (resource_pk) REFERENCES acl_resource (resource_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `acl_role_parent`
Stores the inheritance tree — which role(s) a role inherits from.
```sql
CREATE TABLE acl_role_parent (
    role_pk    TINYINT UNSIGNED NOT NULL,
    parent_pk  TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_pk, parent_pk),
    CONSTRAINT fk_arp_role   FOREIGN KEY (role_pk)   REFERENCES role (id),
    CONSTRAINT fk_arp_parent FOREIGN KEY (parent_pk) REFERENCES role (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `acl_rule`
Unified allow/deny table.
```sql
CREATE TABLE acl_rule (
    id            INT UNSIGNED AUTO_INCREMENT,
    role_pk       TINYINT UNSIGNED NOT NULL,
    resource_pk   SMALLINT UNSIGNED NOT NULL,
    privilege_pk  SMALLINT UNSIGNED NOT NULL,
    type          ENUM('allow','deny') NOT NULL DEFAULT 'allow',
    PRIMARY KEY (id),
    UNIQUE KEY uq_rule (role_pk, resource_pk, privilege_pk),
    CONSTRAINT fk_rule_role      FOREIGN KEY (role_pk)      REFERENCES role          (id),
    CONSTRAINT fk_rule_resource  FOREIGN KEY (resource_pk)  REFERENCES acl_resource  (resource_pk),
    CONSTRAINT fk_rule_privilege FOREIGN KEY (privilege_pk) REFERENCES acl_privilege (privilege_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `acl_route_privilege`
Maps a named Mezzio route to the resource+privilege the ACL must check.
```sql
CREATE TABLE acl_route_privilege (
    id            INT UNSIGNED AUTO_INCREMENT,
    route_name    VARCHAR(200) NOT NULL,
    resource_pk   SMALLINT UNSIGNED NOT NULL,
    privilege_pk  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_route (route_name),
    CONSTRAINT fk_rp_resource  FOREIGN KEY (resource_pk)  REFERENCES acl_resource  (resource_pk),
    CONSTRAINT fk_rp_privilege FOREIGN KEY (privilege_pk) REFERENCES acl_privilege (privilege_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Core Class Map

```
src/webware-acl/src/
├── ConfigProvider.php                    ← already exists
│
├── Acl/
│   ├── AclInterface.php                  ← extends Laminas AclInterface (no extra methods yet)
│   ├── AclMiddleware.php                 ← calls $acl->isAllowed() directly; replaces vendor AuthorizationMiddleware
│   ├── AclMiddlewareFactory.php
│   ├── AclBuilder.php                    ← builds Laminas\Acl from DB data or cache
│   └── AclBuilderFactory.php
│
├── Authentication/
│   ├── DefaultUserFactory.php            ← produces UserInterface with configured base role (e.g. 'guest') for unauthenticated requests
│   └── PhpSessionFactory.php             ← wraps mezzio PhpSession, ensures getRoles() is populated from DB user row
│
├── Cache/
│   ├── AclCacheInterface.php             ← get()/set()/invalidate()
│   └── FileAclCache.php                  ← default implementation (data/cache/)
│
├── Entity/
│   ├── Role.php                          ← implements Laminas RoleInterface
│   ├── Resource.php                      ← implements Laminas ResourceInterface
│   └── Privilege.php                     ← value object (resource_id, privilege string)
│
├── Repository/
│   ├── AclRepositoryInterface.php        ← fetchRoles(), fetchResources(), fetchRules(), fetchRouteMappings()
│   ├── AclRepository.php                 ← phpdb TableGateway implementation
│   └── AclRepositoryFactory.php
│
├── Exception/
│   ├── ExceptionInterface.php
│   ├── InvalidConfigException.php
│   └── RuntimeException.php
│
├── Event/
│   ├── AclBuildStartedEvent.php
│   ├── RolesLoadedEvent.php
│   ├── ResourcesLoadedEvent.php
│   ├── RulesLoadedEvent.php
│   └── AclBuiltEvent.php
│
└── Admin/                                ← management UI (Phase 2)
    ├── RequestHandler/
    │   ├── AclDashboardHandler.php
    │   ├── RoleManagerHandler.php
    │   ├── ResourceManagerHandler.php
    │   ├── PrivilegeManagerHandler.php
    │   └── RouteMapManagerHandler.php
    └── RouteProvider.php
```

---

### `AclMiddleware` Logic

```
process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  1. Get UserInterface from request attribute
     - If absent → use configured base role string (e.g. 'guest')
     - If present → call $user->getRoles(), take first role
  2. Get RouteResult from request attribute
     - If absent or failure → pass through (not our concern)
  3. Look up route_name in acl_route_privilege → get (resource_id, privilege_id)
     - If no mapping found → return 403 (unmapped routes are denied)
  4. Call $this->acl->isAllowed($role, $resourceId, $privilegeId)
     - true  → $handler->handle($request)
     - false → return 403 response
```

Routes with no `acl_route_privilege` row are **denied by default**. The management UI must prominently surface unmapped routes so an admin can assign them.

> **Deferred**: Whether `AclMiddleware` also implements `Mezzio\Authorization\AuthorizationInterface` will be decided once core ACL is working.

---

### AclBuilder Event Dispatch

To allow future plugins and consuming applications to extend the ACL without modifying `AclBuilder`, the builder fires PSR-14 events at key points in the build process. Listeners receive the partially- or fully-built `Acl` instance and may call any `AclInterface` method on it (e.g. `addRole`, `addResource`, `allow`, `deny`).

The event dispatcher used is `Psr\EventDispatcher\EventDispatcherInterface`, implemented by `phly/phly-event-dispatcher` (already in vendor).

#### Proposed Events (in `Webware\Acl\Event\`)

| Event class | Fired when | Typical listener use |
|-------------|------------|----------------------|
| `AclBuildStartedEvent` | Before any DB data is loaded | Register extra roles/resources ahead of DB seed |
| `RolesLoadedEvent` | After all roles + parents are added to `Acl` | Add plugin-provided roles with inheritance |
| `ResourcesLoadedEvent` | After all resources are added to `Acl` | Add plugin-provided resources + privileges |
| `RulesLoadedEvent` | After all allow/deny rules are applied | Add or override plugin-specific rules |
| `AclBuiltEvent` | After the `Acl` is fully built, before caching | Final inspection / mutation before cache write |

Each event carries a reference to the `Acl` instance. Events are **not stoppable** — every listener runs. If a listener needs to signal a build failure it should throw an exception.

#### `AclBuilder` receives `EventDispatcherInterface` as an optional constructor dependency. If no dispatcher is wired the build proceeds without firing events — maintains backwards compatibility when the package is used without an event system.

```php
// Conceptual signature
public function __construct(
    private readonly AclRepositoryInterface $repository,
    private readonly AclCacheInterface $cache,
    private readonly ?EventDispatcherInterface $events = null,
) {}
```

---

### ACL Build + Cache Strategy

`AclBuilder::build()`:
1. Check cache (keyed by a hash of the last-modified timestamp on the acl tables, or a simple `acl_version` value in a config/flag table).
2. Cache hit → deserialize and return the `Laminas\Acl` instance.
3. Cache miss:
   - Load roles + parent relationships from DB → call `$acl->addRole($role, $parents)` in dependency order.
   - Load resources → `$acl->addResource($resource)`.
   - Load rules → `$acl->allow()` / `$acl->deny()` per row.
   - Serialize and write to cache.
4. Return hydrated `Laminas\Acl`.

Cache invalidation: any write operation in the ACL management handlers calls `AclCacheInterface::invalidate()`, which deletes the cache file. The next request triggers a rebuild.

---

### CommandBus Integration

Commands that modify data require a privilege check. Two approaches — decision deferred until CommandBus integration is built:

- **Option A**: A CommandBus middleware that accepts a map of `CommandClass → [resource, privilege]` and checks `$acl->isAllowed()` before dispatching.
- **Option B**: Each command implements a `PrivilegedCommandInterface` with `getRequiredResource()` and `getRequiredPrivilege()` methods; the middleware reads those.

Option B is cleaner for a future standalone package.

---

### Management UI Routes (Phase 2)

All routes under `/admin/access/` protected by `AclMiddleware`.

| Route | Name | Purpose |
|-------|------|---------|
| `GET /admin/access` | `admin.acl.dashboard` | Overview: role tree, resource list, rule count |
| `GET/POST /admin/access/roles` | `admin.acl.roles` | Add/edit/delete roles, drag to reorder inheritance |
| `GET/POST /admin/access/resources` | `admin.acl.resources` | Add/edit/delete resources + privileges |
| `GET/POST /admin/access/rules` | `admin.acl.rules` | Assign allow/deny rules to roles |
| `GET/POST /admin/access/routes` | `admin.acl.routes` | Sync routes from RouteCollector, map to resource+privilege |

Drag-and-drop role ordering uses **Sortable.js** (already acceptable per Q6) integrated with HTMX for server persistence.

---

### Implementation Phases

#### Phase 1 — Database + seed data
- Write schema files: `016_acl_resource.sql`, `017_acl_privilege.sql`, `018_acl_role_parent.sql`, `019_acl_rule.sql`, `020_acl_route_privilege.sql`
- Add `guest` row to existing `002_role.sql` seed (it is a normal role row)
- Add seed rows to `999_seed.sql`: default resources (`public`, `user`), default privileges (`read`, `create`, `update`, `delete`, `login`, `register`), role parent relationships, and baseline allow/deny rules

#### Phase 2 — Entity + Repository layer
- `Role`, `Resource`, `Privilege` entities
- `AclRepositoryInterface` + `AclRepository` (phpdb)
- Unit tests for repository (mock adapter)

#### Phase 3 — Core ACL wiring
- `AclBuilder` + `FileAclCache`
- `AclMiddleware` + factory (calls `isAllowed` directly)
- `Authentication\DefaultUserFactory` — produces `UserInterface` with base role for unauthenticated requests
- Factories for all of the above
- `ConfigProvider` wired
- Integration test: build Acl from seed data, assert `isAllowed` results

#### Phase 4 — Pipeline integration
- Add `AclMiddleware` to protected routes
- Wire `DefaultUserFactory` so unauthenticated requests carry the base role
- Verify `guest` / authenticated role checks work end-to-end on existing routes

#### Phase 5 — Management UI (first component: Route Mapper)
- Start with `RouteMapManagerHandler` — simplest UI, no drag-and-drop needed
- Sync route names from `RouteCollector`, display unmapped routes, allow assignment
- This is the component that makes the system usable without manual SQL

#### Phase 6 — Management UI (Role + Resource managers)
- Role tree UI with Sortable.js drag-and-drop for inheritance ordering
- Resource + privilege CRUD
- Rule assignment matrix (role → resource → allow/deny checkboxes)

#### Phase 7 — CommandBus privilege integration
- Define `PrivilegedCommandInterface`
- Build CommandBus privilege-check middleware
- Register command-to-privilege map in `ConfigProvider`

---

### Open Items / Known Unknowns

- **Cache implementation**: ✅ **Decided.** Cache the raw DB row arrays (not the `Laminas\Acl` object — not safely serializable). A single PHP-serialized file `data/cache/acl.cache` stores:
  ```php
  [
      'version'       => int,   // mirrors acl_version counter
      'roles'         => [...], // raw rows
      'parents'       => [...],
      'resources'     => [...],
      'privileges'    => [...],
      'rules'         => [...], // already-resolved string IDs
      'routeMappings' => [...],
  ]
  ```
  On cache hit, version is compared against the DB counter; if they match, `AclBuilder::buildFromData()` rebuilds the `Acl` in-memory from the arrays with zero DB queries. No `laminas-cache` dependency.
- **Cache key strategy**: ✅ **Decided.** A dedicated `acl_version` single-row table incremented by every ACL management write. The cache file is deleted on any write; the next request rebuilds.
- **Route sync UX**: When new routes are added to the app and have no `acl_route_privilege` row they are silently denied. The dashboard should highlight unmapped routes prominently.
- **`guest` role storage**: `guest` is a real `role` table row, manageable via the UI. `AclBuilder` treats whichever role is configured as the base role identically to all others — no hardcoding.
- **Serialization**: ✅ **Decided.** Resolved by caching raw arrays — see Cache implementation above.
- **i18n / Translation of identifiers**: `role_id`, `resource_id`, and `privilege_id` are currently plain English strings used directly as Laminas ACL identifiers (e.g. `'Administrator'`, `'ManifestManager'`, `'create'`). For applications that need to present these labels in multiple languages, a translation layer will be needed — either a separate `label` column (already present in `acl_resource` and `acl_privilege`) driven by a `Psr\SimpleCache` or `laminas-i18n` translator, or a dedicated `acl_*_translation` table per entity. The ACL engine itself always operates on the canonical English identifier; translation is a UI/display concern only. **Deferred to a future phase.**

---

## Vendor Dependency Tracking

When `Webware\Acl` is extracted to its own repository this section drives the `composer.json` `require` block.

| Package | Constraint | Role |
|---------|------------|------|
| `php` | `^8.2` | Minimum PHP version |
| `laminas/laminas-permissions-acl` | `^2.10` | Core ACL engine (`Acl`, `AclInterface`, `RoleInterface`, `ResourceInterface`) |
| `mezzio/mezzio-authentication` | `^1.8` | `UserInterface`, `DefaultUser` |
| `mezzio/mezzio-router` | `^3.11` | `RouteResult`, `RouteCollectorInterface` |
| `psr/http-message` | `^2.0` | `ServerRequestInterface`, `ResponseInterface` |
| `psr/http-server-middleware` | `^1.0` | `MiddlewareInterface`, `RequestHandlerInterface` |
| `psr/container` | `^2.0` | `ContainerInterface` (factories) |
| `psr/event-dispatcher` | `^1.0` | `EventDispatcherInterface` (plugin extension points in `AclBuilder`) |
| `webware/php-db` | `^1.0` | `TableGateway`, DB adapter (repository layer) |

### `require-dev` (testing only)

| Package | Constraint | Role |
|---------|------------|------|
| `phpunit/phpunit` | `^11.0` | Unit + integration tests |
| `phly/phly-event-dispatcher` | `^1.0` | Concrete dispatcher in tests (already in app vendor) |
| `laminas/laminas-diactoros` | `^3.0` | PSR-7 request/response in tests |

### Deferred / Conditional

| Package | Condition |
|---------|-----------|
| `mezzio/mezzio-authorization` | Only if we implement `AuthorizationInterface` (decision deferred) |
| `laminas/laminas-cache` | Only if we use laminas-cache over plain file serialization (decision deferred to Phase 3) |

> **Keep this table updated** as new dependencies are introduced during implementation.

