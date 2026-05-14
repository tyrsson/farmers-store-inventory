# Route-Based ACL with `RouteResource` — Implementation Plan

**Branch:** `acl-ownership-assertion-aggregates`
**Planned:** 2026-05-14
**Status:** Ready to execute

---

## Architectural Summary

| Before | After |
|---|---|
| Route name → lookup `acl_route_mapping` → `{resource_id, privilege_id}` → `isAllowed(roles, string, string)` | Route name IS the resource ID. HTTP method IS the privilege. `isAllowed(roles, RouteResource, privilege)` |
| Unmapped route → deny | Unregistered route → allow (opt-in protection model) |
| Developer manually types resource ID string | Developer clicks "Protect" on a route in the UI |
| `$routeMappings` injected into `Acl` | `$paramMap` injected into `Acl` |

The `acl_route_mapping` table and related code (`RouteMapManagerHandler`, `ProcessRouteMappingMiddleware`, etc.) are **superseded but not deleted** in this plan — deprecate in place, clean up in a separate PR.

---

## Key Design Decisions

- **Route name = resource ID.** No separate mapping step.
- **HTTP method → privilege** (static map: `GET=read`, `POST=create`, `PUT/PATCH=update`, `DELETE=delete`).
- **Opt-in protection model.** Unregistered routes pass through — `isAllowedRoute()` returns `true`. Routes are open until explicitly protected.
- **`RouteResource`** is the HTTP-layer bridge between a Mezzio `RouteResult` and the Laminas ACL resource contract.
- **Three-level `ownerId` resolution** (per-route options → global config → convention) gives established applications freedom to map existing param names without changing routes.
- **`StoreOwnedResourceAssertion`** receives `RouteResource` as the `$resource` argument — `getOwnerId()` provides ownership data at the HTTP layer.
- **`AuthorizableCommandInterface`** and command-level ACL are preserved as ecosystem infrastructure but deferred for this application.

---

## File List

| # | File | Action |
|---|---|---|
| 1 | `src/webware-acl/src/Http/RouteResource.php` | CREATE |
| 2 | `src/webware-acl/src/Acl.php` | MODIFY |
| 3 | `src/webware-acl/src/Container/AclFactory.php` | MODIFY |
| 4 | `src/webware-acl/src/Admin/Command/ProtectRouteCommand.php` | CREATE |
| 5 | `src/webware-acl/src/Admin/CommandHandler/ProtectRouteHandler.php` | CREATE |
| 6 | `src/webware-acl/src/Admin/CommandHandler/Container/ProtectRouteHandlerFactory.php` | CREATE |
| 7 | `src/webware-acl/src/Admin/Middleware/ProcessProtectRouteMiddleware.php` | CREATE |
| 8 | `src/webware-acl/src/Admin/Middleware/Container/ProcessProtectRouteMiddlewareFactory.php` | CREATE |
| 9 | `src/webware-acl/src/Admin/RequestHandler/ResourceListHandler.php` | MODIFY |
| 10 | `src/webware-acl/src/Admin/RequestHandler/Container/ResourceListHandlerFactory.php` | MODIFY |
| 11 | `src/webware-acl/src/RouteProvider.php` | MODIFY — add protect route |
| 12 | `src/webware-acl/src/ConfigProvider.php` | MODIFY — new factories, command map, config key |
| 13 | `src/webware-acl/templates/acl/admin-resources.phtml` | MODIFY — Unprotected Routes section |
| 14 | `.github/skills/webware-acl-ownership-command/SKILL.md` | MODIFY |

---

## File 1 — `src/webware-acl/src/Http/RouteResource.php` (CREATE)

```php
<?php

declare(strict_types=1);

namespace Webware\Acl\Http;

use Ims\Store\Acl\StoreOwnedResourceInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\PrivilegeInterface;
use Webware\Acl\RoleProviderInterface;
use Webware\UserManager\UserInterface;

/**
 * Bridges a Mezzio RouteResult into a Laminas ACL resource.
 *
 * Resource ID  = matched route name
 * Privilege    = HTTP method mapped to create/read/update/delete
 * Role         = authenticated UserInterface from request attribute
 * OwnerId      = resolved from route param → query string → request attribute
 *                using three-level config: per-route options > global param map > convention
 *
 * @note StoreOwnedResourceInterface creates a dependency on ims-store. When extracting
 *       webware-acl as a standalone ecosystem package, move the interface or provide
 *       a webware-acl-store bridge package.
 */
final class RouteResource implements
    ResourceInterface,
    RoleProviderInterface,
    PrivilegeInterface,
    StoreOwnedResourceInterface
{
    private const array METHOD_PRIVILEGE_MAP = [
        'GET'    => PrivilegeInterface::READ,
        'POST'   => PrivilegeInterface::CREATE,
        'PUT'    => PrivilegeInterface::UPDATE,
        'PATCH'  => PrivilegeInterface::UPDATE,
        'DELETE' => PrivilegeInterface::DELETE,
    ];

    public function __construct(
        private readonly RouteResult $routeResult,
        private readonly ServerRequestInterface $request,
        private readonly array $paramMap = [],
    ) {}

    public function getResourceId(): string
    {
        return $this->routeResult->getMatchedRouteName();
    }

    public function getPrivilegeId(): string
    {
        return self::METHOD_PRIVILEGE_MAP[$this->request->getMethod()]
            ?? PrivilegeInterface::READ;
    }

    public function getRole(): RoleInterface
    {
        return $this->request->getAttribute(UserInterface::class);
    }

    public function getOwnerId(): int
    {
        $routeName = $this->getResourceId();

        // 1. Per-route options array (most specific)
        $routeOptions = $this->routeResult->getMatchedRoute()->getOptions();
        $paramName    = $routeOptions['acl']['ownerId']
            // 2. Global route_param_map config (app-level fallback)
            ?? $this->paramMap[$routeName]['ownerId']
            // 3. Convention
            ?? 'ownerId';

        return (int) (
            $this->routeResult->getMatchedParam($paramName)
            ?? $this->request->getQueryParams()[$paramName]
            ?? $this->request->getAttribute($paramName)
            ?? 0
        );
    }
}
```

---

## File 2 — `src/webware-acl/src/Acl.php` (MODIFY)

**Constructor:** replace `$routeMappings` with `$paramMap`.

```php
public function __construct(
    private readonly LaminasAclInterface $acl,
    private readonly array $paramMap = [],  // replaces $routeMappings
) {}
```

**`isAllowedRoute()`:** use `hasResource()` + `RouteResource`. Unregistered route → `true`.

```php
#[Override]
public function isAllowedRoute(
    ServerRequestInterface $request,
    array|RoleInterface|string|null $roles = null,
): bool {
    $routeResult = $request->getAttribute(RouteResult::class);

    if (! ($routeResult instanceof RouteResult) || $routeResult->isFailure()) {
        return true;
    }

    $routeName = $routeResult->getMatchedRouteName();

    // Not opted in as a resource — allow through (opt-in model)
    if (! $this->acl->hasResource($routeName)) {
        return true;
    }

    $routeResource = new RouteResource($routeResult, $request, $this->paramMap);

    return $this->isAllowed($roles, $routeResource, $routeResource->getPrivilegeId());
}
```

**`isAllowedByRouteName()`:** use `hasResource()` + `null` privilege (any). Used by navigation — shows link if role has any privilege on the resource.

```php
#[Override]
public function isAllowedByRouteName(
    string $routeName,
    array|RoleInterface|string|null $roles = null,
): bool {
    // Not opted in = not protected = allow
    if (! $this->acl->hasResource($routeName)) {
        return true;
    }

    // null privilege = "any" — correct for navigation visibility checks
    return $this->isAllowed($roles, $routeName, null);
}
```

Add import: `use Webware\Acl\Http\RouteResource;`

---

## File 3 — `src/webware-acl/src/Container/AclFactory.php` (MODIFY)

```php
public function __invoke(ContainerInterface $container): Acl
{
    $aclBuilder = $container->get(\Webware\Acl\AclBuilder::class);
    $laminas    = $aclBuilder->build();
    $paramMap   = $container->get('config')['webware-acl']['route_param_map'] ?? [];

    return new Acl(
        acl:      $laminas,
        paramMap: $paramMap,
    );
}
```

---

## File 4 — `src/webware-acl/src/Admin/Command/ProtectRouteCommand.php` (CREATE)

Follows existing `NamedCommandInterface` + `NamedCommandTrait` pattern (same as `SaveResourceCommand`).

```php
<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Command;

use Webware\CommandBus\Command\NamedCommandInterface;
use Webware\CommandBus\Command\NamedCommandTrait;

final readonly class ProtectRouteCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    /**
     * @param string[] $allowedMethods  e.g. ['GET', 'POST']
     */
    public function __construct(
        public string $routeName,
        public array  $allowedMethods,
    ) {}
}
```

---

## File 5 — `src/webware-acl/src/Admin/CommandHandler/ProtectRouteHandler.php` (CREATE)

Inserts one `acl_resource` row and one `acl_privilege` row per mapped HTTP method. Deduplication is the repository's responsibility — safe to re-run.

```php
<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\CommandHandler;

use Webware\Acl\Admin\Command\ProtectRouteCommand;
use Webware\Acl\PrivilegeInterface;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandHandlerInterface;
use Webware\CommandBus\Command\CommandInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;

use function array_filter;
use function array_map;
use function array_unique;
use function assert;

final class ProtectRouteHandler implements CommandHandlerInterface
{
    private const array METHOD_PRIVILEGE_MAP = [
        'GET'    => PrivilegeInterface::READ,
        'POST'   => PrivilegeInterface::CREATE,
        'PUT'    => PrivilegeInterface::UPDATE,
        'PATCH'  => PrivilegeInterface::UPDATE,
        'DELETE' => PrivilegeInterface::DELETE,
    ];

    public function __construct(private readonly AclRepositoryInterface $aclRepository) {}

    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof ProtectRouteCommand);

        // Insert resource row (route name as both resourceId and label)
        $this->aclRepository->saveResource($command->routeName, $command->routeName);

        // Derive unique privileges from allowed methods and insert
        $privileges = array_unique(array_filter(array_map(
            static fn(string $method): string => self::METHOD_PRIVILEGE_MAP[$method] ?? '',
            $command->allowedMethods,
        )));

        foreach ($privileges as $privilege) {
            $this->aclRepository->savePrivilege($command->routeName, $privilege);
        }

        return new CommandResult($command, CommandStatus::Success, null);
    }
}
```

> **Pre-condition:** Verify `AclRepositoryInterface` declares `saveResource(string $resourceId, string $label): void` and `savePrivilege(string $resourceId, string $privilegeId): void`. Add methods if missing. Also check whether the existing `SaveResourceHandler` invalidates the ACL cache — replicate that pattern here.

---

## File 6 — `ProtectRouteHandlerFactory.php` (CREATE)

Standard single-dependency factory:

```php
<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\CommandHandler\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\CommandHandler\ProtectRouteHandler;
use Webware\Acl\Repository\AclRepositoryInterface;

final class ProtectRouteHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProtectRouteHandler
    {
        return new ProtectRouteHandler($container->get(AclRepositoryInterface::class));
    }
}
```

---

## File 7 — `ProcessProtectRouteMiddleware.php` (CREATE)

Follows `HttpMethodProcessorTrait` pattern. POST only — reads `routeName` from parsed body, resolves allowed methods from `RouteCollectorInterface`.

```php
<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware;

use Axleus\Message\SystemMessengerInterface;
use Mezzio\Router\RouteCollectorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\ProtectRouteCommand;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;
use Webware\Core\HttpMethodProcessorTrait;

final class ProcessProtectRouteMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RouteCollectorInterface $routeCollector,
    ) {}

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $body      = $request->getParsedBody();
        $routeName = (string) ($body['routeName'] ?? '');
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        // Resolve allowed methods from the registered route definition
        $allowedMethods = ['GET'];
        foreach ($this->routeCollector->getRoutes() as $route) {
            if ($route->getName() === $routeName) {
                $allowedMethods = $route->getAllowedMethods() ?? ['GET'];
                break;
            }
        }

        $result = $this->commandBus->handle(
            new ProtectRouteCommand($routeName, $allowedMethods)
        );

        if ($result->getStatus() === CommandStatus::Success) {
            $messenger?->success("Route '{$routeName}' is now protected.", hops: 1);
        } else {
            $messenger?->error("Failed to protect route '{$routeName}'.", hops: 1);
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
```

---

## File 8 — `ProcessProtectRouteMiddlewareFactory.php` (CREATE)

```php
<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Mezzio\Router\RouteCollectorInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessProtectRouteMiddleware;
use Webware\CommandBus\CommandBusInterface;

final class ProcessProtectRouteMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessProtectRouteMiddleware
    {
        return new ProcessProtectRouteMiddleware(
            $container->get(CommandBusInterface::class),
            $container->get(RouteCollectorInterface::class),
        );
    }
}
```

---

## File 9 — `ResourceListHandler.php` (MODIFY)

Inject `RouteCollectorInterface`. Compute `$unprotected` = registered route names not yet in `acl_resource`.

**New constructor:**
```php
public function __construct(
    private readonly AclRepositoryInterface $aclRepository,
    private readonly TemplateRendererInterface $template,
    private readonly RouteCollectorInterface $routeCollector,
) {}
```

**New logic in `handle()`** (add before `$response = new HtmlResponse(...)`):

```php
// Build set of already-registered resource IDs
$registeredIds = [];
foreach ($resources as $resource) {
    $registeredIds[$resource->resourceId] = true;
}

// Unprotected = registered routes not yet opted in as ACL resources
// Value is the route's allowed methods array for display
$unprotected = [];
foreach ($this->routeCollector->getRoutes() as $route) {
    $name = $route->getName();
    if ($name !== null && $name !== '' && ! isset($registeredIds[$name])) {
        $unprotected[$name] = $route->getAllowedMethods() ?? ['GET'];
    }
}
```

**Updated `render()` call** — add `'unprotected' => $unprotected` to the view data array.

---

## File 10 — `ResourceListHandlerFactory.php` (MODIFY)

```php
public function __invoke(ContainerInterface $container): ResourceListHandler
{
    return new ResourceListHandler(
        $container->get(AclRepositoryInterface::class),
        $container->get(TemplateRendererInterface::class),
        $container->get(RouteCollectorInterface::class),
    );
}
```

Add import: `use Mezzio\Router\RouteCollectorInterface;`

---

## File 11 — `RouteProvider.php` (MODIFY)

Add POST route for the protect endpoint. Place it with the resource routes:

```php
$routeCollector->post(
    '/admin/access/resources/protect',
    $middlewareFactory->prepare([
        AuthorizationMiddleware::class,
        BodyParamsMiddleware::class,
        ProcessProtectRouteMiddleware::class,
        ResourceListHandler::class,
    ]),
    'admin.acl.resources.protect'
);
```

Add import: `use Webware\Acl\Admin\Middleware\ProcessProtectRouteMiddleware;`

---

## File 12 — `ConfigProvider.php` (MODIFY)

**`getDependencies()` factories block** — add:
```php
ProtectRouteHandler::class           => ProtectRouteHandlerFactory::class,
ProcessProtectRouteMiddleware::class => ProcessProtectRouteMiddlewareFactory::class,
```

**`getBusConfig()` command map** — add:
```php
ProtectRouteCommand::class => ProtectRouteHandler::class,
```

**`__invoke()`** — add default config key to the returned array:
```php
'webware-acl' => $this->getAclConfig(),
```

**New method:**
```php
public function getAclConfig(): array
{
    return [
        // Per-route ACL param name overrides.
        // Key: route name. Value: map of ACL param name => route/query param name.
        // Example: 'manifest.create' => ['ownerId' => 'store']
        'route_param_map' => [],
    ];
}
```

Add all new imports at the top of `ConfigProvider.php`.

---

## File 13 — `admin-resources.phtml` (MODIFY)

**Add the following section immediately before the `<!-- Resources accordion -->` comment.** Follow all existing CSS class conventions — no inline styles.

```php
<?php if ($this->unprotected !== []): ?>
<div class="card mb-4">
    <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="bi bi-shield-exclamation text-warning"></i>
        <span class="small fw-semibold">Unprotected Routes</span>
        <span class="badge bg-warning-subtle border border-warning-subtle text-warning-emphasis ms-1">
            <?= count($this->unprotected) ?>
        </span>
        <span class="text-secondary small ms-auto">Routes not yet opted in to ACL protection — currently open to all</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-warning">
                <tr>
                    <th>Route Name</th>
                    <th style="width:22%">Methods</th>
                    <th style="width:12%"></th>
                </tr>
            </thead>
            <tbody id="ims-unprotected-routes">
                <?php foreach ($this->unprotected as $routeName => $methods): ?>
                    <tr id="ims-unprotected-<?= $this->escapeHtmlAttr(preg_replace('/[^a-z0-9]+/', '-', strtolower($routeName))) ?>">
                        <td><code class="small"><?= $this->escapeHtml($routeName) ?></code></td>
                        <td>
                            <?php foreach ($methods as $method): ?>
                                <span class="badge bg-secondary-subtle border text-secondary-emphasis ims-badge-xs">
                                    <?= $this->escapeHtml($method) ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <td class="text-end">
                            <button
                                class="btn btn-sm btn-warning py-0 px-2"
                                hx-post="<?= $this->url('admin.acl.resources.protect') ?>"
                                hx-vals='{"routeName": "<?= $this->escapeHtmlAttr($routeName) ?>"}'
                                hx-target="closest tr"
                                hx-swap="outerHTML swap:300ms"
                            >
                                <i class="bi bi-shield-check me-1"></i>Protect
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
```

> **HTMX note:** `hx-target="closest tr"` + `hx-swap="outerHTML"` removes the row inline on success. The `ResourceListHandler` response after a successful protect returns the full page body — reconsider: if the response is always the full page, use `hx-target="#ims-unprotected-routes"` to refresh only the tbody, or use `HX-Trigger: protectSuccess` + a listener that reloads the section. Review `htmx-mezzio` skill for the correct partial-refresh pattern before finalising.

---

## File 14 — `webware-acl-ownership-command` SKILL (MODIFY)

Update to reflect:

1. **Required Interfaces table** — add `AuthorizableCommandInterface` row, note it supersedes `CommandInterface + RoleProviderInterface` for command-level ACL (ecosystem use, deferred for this app).
2. **Canonical Command Shape** — add a second example showing `RouteResource` as the HTTP-layer alternative; note that store-scoped assertions work at both layers.
3. **Add section:** "Route-based ACL (primary mechanism for this application)" describing the `RouteResource` opt-in flow.
4. **Preserve all existing content** — append only, do not remove.

---

## Pre-Execution Checklist

Read these before writing any code:

1. **`AclRepositoryInterface`** — verify `saveResource(string $resourceId, string $label): void` and `savePrivilege(string $resourceId, string $privilegeId): void` exist. Add if missing.
2. **`htmx-mezzio` skill** — confirm partial-refresh pattern for the Protect button response.
3. **`webware-module-architecture` skill** — confirm `ProcessProtectRouteMiddleware` + handler pipeline wiring.
4. **`webware-coding-standard` skill** — apply to all new PHP files.
5. **`Route::getAllowedMethods()`** — verify method name on Mezzio's `Route` object (not `RouteResult`). Check `vendor/mezzio/mezzio-router/src/Route.php`.
6. **`StoreOwnedResourceInterface` in `RouteResource`** — confirm `ims-store` is a declared dependency of `webware-acl` in `composer.json`. If not, either add it or create a `ProprietaryInterface` stub in `webware-acl`.
7. **ACL cache invalidation** — read `SaveResourceHandler` to find the cache-clear pattern. Replicate in `ProtectRouteHandler`.
8. **Behavior change confirmation** — `isAllowedRoute()` now returns `true` for unregistered routes (was `false`). All existing routes in the app that are NOT in `acl_resource` will become open. Run the app after the change and verify admin routes are still protected (they are registered as resources).

---

## Deferred (not in this plan)

- `SaveManifestCommand` → `CreateManifestCommand` / `UpdateManifestCommand` split
- `#[Authorizable]` attribute + `AuthorizableCommandTrait`
- `ResourceProviderInterface` / `CommandMapResourceProvider`
- Deprecation/removal of `acl_route_mapping` table and `RouteMapManagerHandler`
- Fix duplicate section in `webware-coding-standard/SKILL.md`
