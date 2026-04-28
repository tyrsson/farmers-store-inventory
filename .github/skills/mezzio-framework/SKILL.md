---
name: "mezzio-framework"
description: "Load when working with Mezzio framework internals: ConfigProvider, RouteProvider, middleware pipeline, authentication, authorization, template rendering, or any framework integration pattern. Progressive skill — updated as deeper understanding is gained."
argument-hint: "<what you are working on — e.g. 'new module ConfigProvider', 'route middleware', 'authentication flow'>"
---

## ConfigProvider Pattern

Every module has a `ConfigProvider` class. The project owner is an expert Mezzio user — follow these patterns exactly.

```php
final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            // module-specific top-level keys...
        ];
    }
}
```

- `getDependencies()` returns `['factories' => [...], 'delegators' => [...]]`
- `getTemplates()` returns `['paths' => ['namespace' => [__DIR__ . '/../templates/namespace']]]`
- Only include keys that are relevant — do not add empty sections
- Third-party integrations (e.g. command bus) that merge config at the top-level key should be added at the `__invoke()` level, not inside `getDependencies()`

## Factory Co-location Convention

Factories live in a `Container/` subdirectory co-located with the class they instantiate:

```
src/ModuleName/src/
  RequestHandler/
    FooHandler.php
    Container/
      FooHandlerFactory.php         ← namespace: ModuleName\RequestHandler\Container
  CommandHandler/
    SaveFooHandler.php
    Container/
      SaveFooHandlerFactory.php     ← namespace: ModuleName\CommandHandler\Container
  SubLayer/
    RequestHandler/
      BarHandler.php
      Container/
        BarHandlerFactory.php       ← namespace: ModuleName\SubLayer\RequestHandler\Container
```

Root-level module services (e.g. `RouteProvider`) use the root `Container/` directory:
```
src/ModuleName/src/
  Container/
    RouteProviderFactory.php        ← namespace: ModuleName\Container
```

## RouteProvider Pattern

Routes are registered via a `RouteProvider` class injected into the pipeline, not directly in `config/routes.php`.

```php
final class RouteProvider implements RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void {
        $routeCollector->get('/', [..., DashboardHandler::class], 'home');

        // Same path, different name + method — valid, no duplicate error
        $routeCollector->get('/login', [..., LoginHandler::class], 'module.login');
        $routeCollector->post('/login', [..., AuthenticationMiddleware::class, LoginHandler::class], 'module.login.post');

        $routeCollector->route('/resource[/{id:\d+}]', [..., ResourceHandler::class], ['GET', 'POST'], 'module.resource');
    }
}
```

**Route uniqueness rules** (Mezzio/FastRoute):
- Route **names** must be globally unique
- Route **paths** may be reused across registrations as long as the HTTP methods do not overlap
- Duplicate detection is triggered by name collision OR method overlap on the same path — not by path alone

**Pipeline middleware vs route middleware**: `SessionMiddleware` is piped globally in `pipeline.php` — it must **not** be added to route definitions. `AuthenticationMiddleware` is applied per-route to all protected routes. The login routes (`user.login`, `user.login.post`) must **not** include `AuthenticationMiddleware` — adding it there causes an infinite redirect loop since unauthenticated requests redirect to `/login` which is itself protected.

Route names referenced in templates via `$this->url('route.name')`.

## Template Namespace Registration

Registered in `ConfigProvider::getTemplates()`:

```php
private function getTemplates(): array
{
    return [
        'paths' => [
            'module-namespace' => [__DIR__ . '/../templates/module-namespace'],
        ],
    ];
}
```

Rendered as `$this->template->render('module-namespace::template-name')`.

## 3-Layer Rendering Stack

See the `htmx-mezzio` skill for full details on the rendering stack, variable propagation, layout disable, and conditional chrome suppression.

## Authentication

Uses `mezzio/mezzio-authentication` (session adapter: `mezzio/mezzio-authentication-session`).

- `LoginMiddleware` handles POST — `LoginHandler` is never reached on POST
- `LoginHandler::handle()` is GET only — renders the login form
- `AuthenticationMiddleware` is added to protected routes in `RouteProvider`
- Redirect-on-unauthenticated behaviour is configured in `config/autoload/`

## Authorization

Uses `mezzio/mezzio-authorization` + `mezzio/mezzio-authorization-acl`.

Config key: `mezzio-authorization-acl` with `roles`, `resources` (= route names), `allow` rules.

```php
'mezzio-authorization-acl' => [
    'roles'     => ['guest' => [], 'user' => ['guest'], 'admin' => ['user']],
    'resources' => ['home', 'module.login', 'module.logout', ...],
    'allow'     => ['guest' => ['module.login'], 'user' => ['home', ...], 'admin' => ['admin.*']],
],
```

`AuthorizationInterface::class => LaminasAcl::class` alias required in DI config.

## Key Framework Packages

| Package | Purpose |
|---|---|
| `mezzio/mezzio-authentication` | Session-based authentication |
| `mezzio/mezzio-authorization` | ACL-based authorization |
| `mezzio/mezzio-authorization-acl` | Laminas ACL driver |
| `mezzio/mezzio-valinor` | Value object input mapping via CuyZ/Valinor |
| `laminas/laminas-view` | Template renderer (used with Htmx module's custom renderer) |
