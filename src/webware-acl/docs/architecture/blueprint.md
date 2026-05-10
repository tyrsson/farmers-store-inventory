---
title: webware-acl — Architecture Blueprint
component_path: src/webware-acl/src
version: 1.0.0
date_created: 2026-05-10
last_updated: 2026-05-10
owner: Joey Smith <jsmith@webinertia.net>
tags: [acl, rbac, mezzio, authorisation, psr-14, laminas]
---

# webware-acl — Architecture Blueprint

## 1. Context (C4 Level 1)

```mermaid
C4Context
    title webware-acl — System Context

    Person(dev, "Developer", "Adds new modules; integrates with ACL via listeners")
    Person(admin, "Administrator / Developer", "Manages roles, resources, rules, route mappings via the Admin UI")

    System(ims, "Host Application", "Mezzio application using webware-acl for route-level authorisation")

    System_Ext(db, "Relational Database", "Stores roles, resources, privileges, rules, route mappings, version counter")
    System_Ext(laminas, "laminas-permissions-acl", "RBAC engine — evaluates allow/deny rules")

    Rel(dev, ims, "Registers listener classes in ConfigProvider")
    Rel(admin, ims, "Manages ACL objects via Admin UI (HTMX)")
    Rel(ims, db, "Reads/writes ACL data")
    Rel(ims, laminas, "Delegates allow/deny evaluation")
```

---

## 2. Container Diagram (C4 Level 2)

```mermaid
C4Container
    title webware-acl — Containers

    Container(pipeline, "Mezzio Middleware Pipeline", "PHP", "Global pipeline; IdentityMiddleware runs here")
    Container(routes, "Route Stacks", "PHP", "Per-route pipelines; AuthorizationMiddleware is first in each protected stack")
    Container(acl, "webware-acl Library", "PHP 8.5", "AclBuilder, Acl, Cache, Repository, Events, Admin UI")
    ContainerDb(db, "Database", "MySQL / MariaDB", "role, acl_resource, acl_privilege, acl_rule, acl_rule_assertion, acl_route_mapping, acl_version")
    ContainerDb(cache, "File Cache", "PHP serialize", "data/cache/acl.cache — raw arrays + version counter")

    Rel(pipeline, acl, "IdentityMiddleware attaches user + messenger to request")
    Rel(routes, acl, "AuthorizationMiddleware checks isAllowedRoute()")
    Rel(acl, db, "AclRepository reads/writes via PhpDb")
    Rel(acl, cache, "FileAclCache reads/writes serialised ACL arrays")
```

---

## 3. Component Diagram (C4 Level 3)

```mermaid
C4Component
    title webware-acl — Internal Components

    Component(builder, "AclBuilder", "PHP class", "Orchestrates DB load, event dispatch, cache read/write; returns hydrated Laminas Acl")
    Component(acli, "Acl", "PHP class", "Wraps Laminas Acl; exposes isAllowed / isAllowedRoute / isAllowedByRouteName")
    Component(cache2, "FileAclCache", "PHP class", "get() / set() / invalidate() on data/cache/acl.cache")
    Component(repo, "AclRepository", "PHP class", "All DB reads (fetch*) and writes (save*, delete*, incrementVersion)")
    Component(events, "PSR-14 Event Pipeline", "EventDispatcherInterface", "Fires 5 build-phase events; listeners extend roles/resources/rules/mappings")
    Component(authmw, "AuthorizationMiddleware", "PSR-15 middleware", "Per-request access check; redirects denied requests")
    Component(identmw, "IdentityMiddleware", "PSR-15 middleware", "Attaches UserInterface + SystemMessengerInterface to every request")
    Component(adminmw, "Admin Write Middleware", "PSR-15 middleware ×5", "ProcessRole/Resource/Rule/RouteMapping/Assertion — HttpMethodProcessorTrait")
    Component(handlers, "Admin Handlers", "PSR-15 handler ×5", "AclOverview/RoleList/ResourceList/RuleManager/RouteMapManager — render only")

    Rel(builder, cache2, "read/write serialised arrays")
    Rel(builder, repo, "fetch* on cache miss; fetchVersion() always")
    Rel(builder, events, "dispatch 5 build events")
    Rel(acli, builder, "constructed with Laminas Acl + routeMappings from builder")
    Rel(authmw, acli, "isAllowedRoute(request, roles)")
    Rel(identmw, authmw, "populates UserInterface attribute consumed by AuthorizationMiddleware")
    Rel(adminmw, repo, "save* / delete* / incrementVersion")
    Rel(adminmw, handlers, "passes WriteResult attribute on request")
    Rel(handlers, repo, "fetch* for rendering")
```

---

## 4. Architectural Layers

```mermaid
flowchart TB
    A["**Admin UI Layer**\nAdmin\\RequestHandler\\* — render only\ntemplates/acl/*.phtml"]
    B["**Write-Path Middleware Layer**\nAdmin\\Middleware\\Process* — HttpMethodProcessorTrait\nSets WriteResult::Success on request attribute"]
    C["**Access Control Layer**\nMiddleware\\AuthorizationMiddleware\nMiddleware\\IdentityMiddleware\nAcl — isAllowed / isAllowedRoute / isAllowedByRouteName"]
    D["**Build & Cache Layer**\nAclBuilder + FileAclCache\nPSR-14 event pipeline — 5 events"]
    E["**Repository Layer**\nAclRepository — implements AclRepositoryInterface\nEntity\\Role, Entity\\Resource, Entity\\Privilege"]

    A --> B --> C --> D --> E
```

**Dependency flow is strictly downward.** Handlers depend on repositories and
the template renderer only. Middleware depends on repositories and the Acl.
The Acl depends on AclBuilder. AclBuilder depends on the repository and cache.
Nothing in the lower layers knows about HTTP requests.

---

## 5. ACL Object Model

```mermaid
classDiagram
    direction TB

    class AclInterface {
        <<interface>>
        +isAllowed(roles, resource, privilege) bool
        +isAllowedRoute(request, roles) bool
        +isAllowedByRouteName(routeName, roles) bool
    }

    class Acl {
        -LaminasAcl acl
        -array routeMappings
        +isAllowed(roles, resource, privilege) bool
        +isAllowedRoute(request, roles) bool
        +isAllowedByRouteName(routeName, roles) bool
    }

    class AclBuilder {
        -AclRepositoryInterface repository
        -AclCacheInterface cache
        -EventDispatcherInterface events
        -array routeMappings
        +build() LaminasAcl
        +getRouteMappings() array
        -buildFromArrays(data) LaminasAcl
        -addRolesInOrder(acl, pkToRoleId, parentMap)
        -buildAssertion(rows) AssertionInterface|null
    }

    class AclCacheInterface {
        <<interface>>
        +get() array|null
        +set(data) void
        +invalidate() void
    }

    class FileAclCache {
        -string filePath
        +get() array|null
        +set(data) void
        +invalidate() void
    }

    class AclRepositoryInterface {
        <<interface>>
        +fetchRoles() array
        +fetchRoleParents() array
        +fetchResources() array
        +fetchPrivileges() array
        +fetchRules() array
        +fetchRuleAssertions() array
        +fetchRouteMappings() array
        +fetchVersion() int
        +saveRole() int
        +saveResource() int
        +saveRule() void
        +deleteRole() void
        +deleteResource() void
        +deleteRule() void
        +incrementVersion() void
    }

    class WriteResult {
        <<enum>>
        Success = "webware_acl.write_result.success"
        Failure = "webware_acl.write_result.failure"
    }

    class Privilege {
        <<final class>>
        READ = "read"
        CREATE = "create"
        UPDATE = "update"
        DELETE = "delete"
    }

    AclInterface <|.. Acl
    AclCacheInterface <|.. FileAclCache
    Acl --> AclBuilder : built by
    AclBuilder --> AclCacheInterface : cache
    AclBuilder --> AclRepositoryInterface : repository
```

---

## 6. Request Lifecycle — Per-Request Flow

```mermaid
sequenceDiagram
    participant Browser
    participant Pipeline as Global Pipeline
    participant IdentMW as IdentityMiddleware
    participant AuthMW as AuthorizationMiddleware
    participant ProcMW as Process* Middleware
    participant Handler as RequestHandler
    participant Acl
    participant Repo as AclRepository
    participant Cache as FileAclCache

    Browser->>Pipeline: HTTP Request

    Pipeline->>IdentMW: process()
    IdentMW->>IdentMW: attach UserInterface + SystemMessenger to request
    IdentMW->>AuthMW: delegate (per-route stack)

    AuthMW->>Acl: isAllowedRoute(request, roles)
    Acl->>Acl: resolve RouteResult → routeName → resource+privilege
    Acl->>Acl: LaminasAcl::isAllowed(role, resource, privilege)

    alt Allowed
        AuthMW->>ProcMW: delegate (write routes only)
        ProcMW->>Repo: save*/delete* + incrementVersion()
        ProcMW->>Handler: request.withAttribute(WriteResult::Success, true)
        Handler->>Browser: HtmlResponse (render)
    else Unauthenticated (base role only)
        AuthMW->>Browser: RedirectResponse → /login
    else Authenticated but denied
        AuthMW->>IdentMW: messenger->warning(...)
        AuthMW->>Browser: RedirectResponse → /home
    end
```

---

## 7. ACL Build & Cache Lifecycle

```mermaid
sequenceDiagram
    participant Factory as AclFactory (DI)
    participant Builder as AclBuilder
    participant Cache as FileAclCache
    participant Repo as AclRepository
    participant Events as EventDispatcher
    participant Listeners

    Factory->>Builder: build()
    Builder->>Repo: fetchVersion()
    Builder->>Cache: get()

    alt Cache hit (versions match)
        Cache-->>Builder: raw data array
        Builder->>Builder: buildFromArrays(cached)
    else Cache miss or stale
        Builder->>Repo: fetchRoles + fetchResources + fetchRules + fetchRouteMappings + ...
        Repo-->>Builder: DB entities
        Builder->>Cache: set(serialised arrays)
        Builder->>Builder: buildFromArrays(fresh data)
    end

    Builder->>Events: dispatch(AclBuildStartedEvent)
    Builder->>Events: dispatch(RolesLoadedEvent)
    Events->>Listeners: RegisterXxxResourcesListener
    Builder->>Events: dispatch(ResourcesLoadedEvent)
    Events->>Listeners: RegisterXxxRulesListener
    Builder->>Events: dispatch(RulesLoadedEvent)
    Builder->>Events: dispatch(AclBuiltEvent with DB routeMappings)
    Events->>Listeners: RegisterXxxRouteMappingsListener → addRouteMapping(...)
    Builder->>Builder: routeMappings = event.getRouteMappings()

    Builder-->>Factory: LaminasAcl instance
    Factory->>Factory: new Acl(laminasAcl, routeMappings)
```

---

## 8. Admin Write Pipeline — Single Write Route

```mermaid
flowchart LR
    A([Browser POST]) --> B[AuthorizationMiddleware]
    B -- allowed --> C[Process* Middleware / HttpMethodProcessorTrait]
    C -- processPost --> D[AclRepository.saveXxx]
    D --> E[AclRepository.incrementVersion]
    E --> F[withAttribute WriteResult::Success = true]
    F --> G[RequestHandler.handle]
    G -- WriteResult::Success === true --> H[withHeader Htmx-Trigger closeModal]
    G --> I([HtmlResponse re-render])
    B -- denied --> J([RedirectResponse])
```

---

## 9. Data Model

```mermaid
erDiagram
    role {
        int id PK
        string role_id UK
    }
    role_parent {
        int role_pk FK
        int parent_pk FK
    }
    acl_resource {
        int id PK
        string resource_id UK
        string label
    }
    acl_privilege {
        int id PK
        int resource_pk FK
        string privilege_id
        string label
    }
    acl_rule {
        int id PK
        int role_pk FK
        int resource_pk FK
        int privilege_pk FK
        enum type "allow|deny"
    }
    acl_rule_assertion {
        int id PK
        int rule_pk FK
        string assertion "FQCN"
        enum mode "all|at_least_one"
        int sort_order
    }
    acl_route_mapping {
        int id PK
        string route_name UK
        int resource_pk FK
        int privilege_pk FK
    }
    acl_version {
        int id PK
        int version
    }

    role ||--o{ role_parent : "has parents"
    role_parent }o--|| role : "parent is"
    acl_resource ||--o{ acl_privilege : "has"
    acl_resource ||--o{ acl_rule : "protects"
    acl_privilege ||--o{ acl_rule : "used in"
    role ||--o{ acl_rule : "subject of"
    acl_rule ||--o{ acl_rule_assertion : "guarded by"
    acl_privilege ||--o{ acl_route_mapping : "mapped by"
    acl_resource ||--o{ acl_route_mapping : "mapped by"
```

---

## 10. Extension Points

| Extension point | How to use |
|---|---|
| Add a new resource | `RegisterXxxResourcesListener` on `ResourcesLoadedEvent` |
| Add built-in rules | `RegisterXxxRulesListener` on `RulesLoadedEvent` |
| Add route mappings | `RegisterXxxRouteMappingsListener` on `AclBuiltEvent` |
| Custom assertion | Implement `Laminas\Permissions\Acl\Assertion\AssertionInterface`; attach to a rule via the Admin UI or `RegisterOwnershipAssertionListener` |
| Alternate cache backend | Implement `AclCacheInterface`; alias in DI config |
| Alternate repository | Implement `AclRepositoryInterface`; alias in DI config |

---

## 11. Architectural Decision Records

### ADR-001 — Use Laminas Permissions ACL as the evaluation engine
**Status**: Accepted  
**Rationale**: Proven RBAC library with hierarchical role inheritance, assertion
support, and `allow`/`deny` semantics. Avoids reimplementing graph traversal and
evaluation logic. Trade-off: tight coupling to Laminas in `AclBuilder`; isolated
behind `AclInterface` for callers.

### ADR-002 — PSR-14 events for the build pipeline
**Status**: Accepted  
**Rationale**: Modules must be able to register ACL data without modifying
`AclBuilder`. PSR-14 events provide a stable contract. Listeners are registered
in each module's `ConfigProvider::getListeners()`. No service locator, no
runtime config merging.

### ADR-003 — File cache with version counter
**Status**: Accepted  
**Rationale**: Zero-infrastructure requirement. PHP `serialize`/`unserialize` on
a local file is adequate for a single-process CLI server or traditional
PHP-FPM deployment. The version counter in the DB (`acl_version` table) provides
a reliable invalidation signal. If the cache file is absent or the version
counter mismatches, a full rebuild runs.

### ADR-004 — Route mappings stored separately from the Laminas Acl
**Status**: Accepted  
**Rationale**: Laminas Acl has no HTTP concept. Route mappings are a thin lookup
table (`route_name → resource_id + privilege_id`) held in the `Acl` wrapper.
This keeps the Laminas layer pure and makes `isAllowedRoute()` a simple two-step
lookup with no Laminas coupling.

### ADR-005 — Middleware processes data; handlers render only
**Status**: Accepted  
**Rationale**: Write operations (`POST`, `PATCH`, `DELETE`) are handled by
`Process*Middleware` classes using `HttpMethodProcessorTrait`. The downstream
`RequestHandler` reads `WriteResult::Success` from the request attribute and
renders. This eliminates method-branching in handlers and makes each class
single-responsibility.

### ADR-006 — Administrators cannot manage their own ACL
**Status**: Accepted  
**Rationale**: `RegisterAclRulesListener` grants ACL management only to the
`Developer` role. Granting `Administrator` write access to the ACL would allow
them to elevate themselves or lock out other admins. This is an immutable rule
not manageable via the Admin UI.
