# webware-acl — ACL Integration Skill

> ⚠ **SKILL INTEGRITY — NEVER REMOVE OR SHORTEN**
> Content in this file may only be **added to or updated**. Removing or shortening existing sections is not permitted without explicit user approval. If you are adding new knowledge, append it as a new section.

Load this skill when:
- Integrating a new module with the ACL system (protecting routes, adding resources/rules)
- Writing or reviewing `RegisterXxx*Listener` classes for a component
- Using `AuthorizationMiddleware`, `AclInterface`, or `WriteResult` in any context
- Understanding how the ACL build pipeline works

---

## Package Coordinates

```
Package:   webware/webware-acl
Namespace: Webware\Acl
```

Admin sub-namespace for write-path middleware and handlers:
```
Webware\Acl\Admin\Middleware\
Webware\Acl\Admin\RequestHandler\
Webware\Acl\Admin\WriteResult
```

---

## Core Concepts

### `Privilege` — Canonical privilege identifiers

Non-instantiable class. Always use the constants — never hardcode strings.

```php
use Webware\Acl\Privilege;

Privilege::READ   // 'read'
Privilege::CREATE // 'create'
Privilege::UPDATE // 'update'
Privilege::DELETE // 'delete'
```

### `WriteResult` — Request attribute key enum

Backed string enum. Middleware sets an attribute on the request; handlers read
it. The enum value IS the PSR-7 attribute name key.

```php
use Webware\Acl\Admin\WriteResult;

// Middleware sets it:
$request->withAttribute(WriteResult::Success->value, true);   // bool
$request->withAttribute(WriteResult::Failure->value, 'msg');  // optional

// Handler reads it:
$request->getAttribute(WriteResult::Success->value); // true|false|null
```

Only `WriteResult::Success` is used in practice. Its string value is
`'webware_acl.write_result.success'`.

### `AclInterface` — Three access-check methods

```php
use Webware\Acl\AclInterface;

// Check by role(s) + resource + privilege (string constants)
$acl->isAllowed($roles, 'my.resource', Privilege::READ);

// Check from a PSR-7 request (uses RouteResult to resolve route→resource mapping)
$acl->isAllowedRoute($request, $roles);

// Check by route name without a request object
// Returns true when the route name has NO mapping (unprotected route)
$acl->isAllowedByRouteName('my.route.read', $roles);
```

---

## ACL Build Pipeline

The ACL is built by `AclBuilder` on first request (or after cache invalidation).
It fires PSR-14 events at each phase. Every component that adds resources, rules,
or route mappings does so by listening to these events.

```
AclBuildStartedEvent  → (roles loaded from DB)
ResourcesLoadedEvent  → listeners add component resources
RulesLoadedEvent      → listeners add component rules
AclBuiltEvent         → listeners add route mappings; cache is written after dispatch
```

### Event summary

| Event | `$event` property | Listener adds |
|---|---|---|
| `ResourcesLoadedEvent` | `$event->acl` (`Laminas\Permissions\Acl\Acl`) | `$event->acl->addResource(...)` |
| `RulesLoadedEvent` | `$event->acl` | `$event->acl->allow(...)` / `->deny(...)` |
| `AclBuiltEvent` | `$event->acl` + methods | `$event->addRouteMapping(...)` |

---

## How Every Component Integrates with ACL

Each component must provide **three listener classes** and register them in its
`ConfigProvider`. The pattern is identical across all components.

### 1. `Register{Module}ResourcesListener` — fires on `ResourcesLoadedEvent`

```php
final class RegisterManifestResourcesListener
{
    public function __invoke(ResourcesLoadedEvent $event): void
    {
        $event->acl->addResource('manifest');
    }
}
```

- One `addResource()` call per protected resource string.
- The resource string is the canonical name used in rule lookups — keep it short
  and lowercase (e.g. `'manifest'`, `'admin.acl'`, `'user'`).

### 2. `Register{Module}RulesListener` — fires on `RulesLoadedEvent`

```php
final class RegisterManifestRulesListener
{
    public function __invoke(RulesLoadedEvent $event): void
    {
        $event->acl->allow('Administrator', 'manifest', [
            Privilege::READ,
            Privilege::CREATE,
            Privilege::UPDATE,
            Privilege::DELETE,
        ]);
        $event->acl->allow('Manager', 'manifest', [Privilege::READ, Privilege::CREATE]);
    }
}
```

- Rules added here are **in-memory only** — they supplement the DB-loaded rules.
  Use them for built-in/default grants that should not be manageable via the
  admin UI (e.g. Developer always gets full access).
- End-user configurable grants live in the DB and are loaded automatically.

### 3. `Register{Module}RouteMappingsListener` — fires on `AclBuiltEvent`

```php
final class RegisterManifestRouteMappingsListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        $event->addRouteMapping('manifest.list',         'manifest', Privilege::READ);
        $event->addRouteMapping('manifest.detail',       'manifest', Privilege::READ);
        $event->addRouteMapping('manifest.upload',       'manifest', Privilege::READ);
        $event->addRouteMapping('manifest.upload.store', 'manifest', Privilege::CREATE);
    }
}
```

- **Every protected route must have a mapping.** Routes with no mapping are
  treated as unprotected by `isAllowedByRouteName()`.
- Route name format follows the Mezzio route name given in `RouteProvider`.
- Privilege should reflect the HTTP intent: GET → `READ`, POST → `CREATE`,
  PATCH/PUT → `UPDATE`, DELETE → `DELETE`.

### ConfigProvider registration

```php
public function getListeners(): array
{
    return [
        ResourcesLoadedEvent::class => [
            ['listener' => RegisterManifestResourcesListener::class, 'priority' => 1],
        ],
        RulesLoadedEvent::class => [
            ['listener' => RegisterManifestRulesListener::class, 'priority' => 1],
        ],
        AclBuiltEvent::class => [
            ['listener' => RegisterManifestRouteMappingsListener::class, 'priority' => 1],
        ],
    ];
}
```

Listeners that have no constructor dependencies may be registered as
`'invokables'` in `getDependencies()`. Listeners with dependencies need a
factory and go in `'factories'`.

---

## `AuthorizationMiddleware` — Protecting Routes

`AuthorizationMiddleware` is the ACL gatekeeper. It must be the **first**
middleware in every protected route stack.

```php
// In RouteProvider::registerRoutes()
$routeCollector->get(
    '/manifests',
    $middlewareFactory->prepare([
        AuthorizationMiddleware::class,   // ← always first
        ManifestListHandler::class,
    ]),
    'manifest.list'
);

$routeCollector->post(
    '/manifests/upload',
    $middlewareFactory->prepare([
        AuthorizationMiddleware::class,            // ← always first
        ProcessManifestUploadMiddleware::class,    // ← data processing
        ManifestUploadHandler::class,             // ← render
    ]),
    'manifest.upload.store'
);
```

### Decision table

| Condition | Result |
|---|---|
| No `RouteResult` or routing failure | Pass through (not ACL's concern) |
| `isAllowedRoute()` → true | Delegate to next middleware |
| Unauthenticated (only base role) | Silent redirect to login |
| Authenticated but denied | Warning toast + redirect to home |
| Route name not in mappings | Same as denied |

`AuthorizationMiddleware` reads `SystemMessengerInterface` from the request
attribute and calls `$messenger?->warning(...)` — null-safe, no crash if
messenger is absent.

---

## `AclRepositoryInterface` — Write Methods

All write methods are in `AclRepositoryInterface`. After any write, always call
`incrementVersion()` to invalidate the `FileAclCache`:

```php
$this->aclRepository->saveRole($roleId, $parentPk);
$this->aclRepository->incrementVersion();    // ← required after every write
```

Key write methods:

| Method | Notes |
|---|---|
| `saveRole(string $roleId, int $parentPk): int` | Upsert by `role_id` unique key |
| `deleteRole(int $rolePk): void` | Caller must verify no users assigned |
| `saveResource(string $resourceId, string $label): int` | Upsert by `resource_id` |
| `insertPrivilege(int $resourcePk, string $privilegeId, string $label): int` | Scoped to a resource |
| `deleteResource(int $resourcePk): void` | Cascades: deletes rules + mappings + privileges |
| `saveRule(int $rolePk, int $resourcePk, int $privilegePk, string $type): void` | Upsert — type: `'allow'`\|`'deny'` |
| `updateRuleType(int $id, string $type): void` | PATCH path |
| `deleteRule(int $id): void` | By rule PK |
| `saveRouteMapping(string $routeName, int $resourcePk, int $privilegePk): void` | Upsert |
| `deleteRouteMapping(string $routeName): void` | |
| `incrementVersion(): void` | **Must be called after every write** |

---

## `IdentityMiddleware`

Attaches `SystemMessengerInterface` and the authenticated `UserInterface` to
the request. It runs **before** `AuthorizationMiddleware` in the global pipeline
(not per-route) — it is already present on every request. Do not add it to
individual route stacks.

---

## `FileAclCache`

The ACL is expensive to build (multiple DB queries + event dispatch). `AclBuilder`
caches the result. The cache is invalidated by `incrementVersion()` — the next
request rebuilds and re-caches.

Do not interact with `FileAclCache` directly in component code. Only
`AclRepositoryInterface::incrementVersion()` is the correct invalidation signal.

---

## Listener Directory Layout

```
src/{module}/src/Listener/
    Register{Module}ResourcesListener.php
    Register{Module}RulesListener.php
    Register{Module}RouteMappingsListener.php
```

Factories (if needed) live in:
```
src/{module}/src/Container/
    Register{Module}ResourcesListenerFactory.php
    ...
```

---

## Anti-Patterns

- **Do not** call `$acl->addResource()` or `$acl->allow()` outside of ACL event
  listeners. Resources and rules must be registered through the event pipeline.
- **Do not** call `$aclRepository->incrementVersion()` multiple times per
  request — one call per write operation is sufficient.
- **Do not** add `AuthorizationMiddleware` to the global pipeline — it is
  route-specific. The global pipeline uses `IdentityMiddleware` only.
- **Do not** check `isAllowedRoute()` inside handlers or middleware other than
  `AuthorizationMiddleware`. If a route is reachable, it is already allowed.
- **Do not** hardcode privilege strings (`'read'`, `'create'`). Always use
  `Privilege::READ`, `Privilege::CREATE`, etc.
