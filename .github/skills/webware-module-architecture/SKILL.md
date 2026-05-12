---
name: "webware-module-architecture"
description: "Load when creating or modifying any handler, middleware, route, or module in the webware component ecosystem or any host application built from webware components. Covers the strict separation between data-processing middleware and rendering handlers, the HttpMethodProcessorTrait pattern, WriteResult, and RouteProvider pipeline wiring."
argument-hint: "<what you are creating — e.g. 'upload handler', 'process middleware for manifest', 'route wiring for POST endpoint'>"
---

## Core Principle: Strict Middleware / Handler Separation

**Middleware processes data. Handlers render responses. Never mix them.**

This is a hard architectural rule across every webware component and every host application module built on top of them. A RequestHandler that branches on `$request->getMethod()` to perform a write is a pattern violation.

### The Pipeline Model

Every write endpoint follows this pipeline shape:

```
[AuthorizationMiddleware] → [BodyParamsMiddleware?] → [Process{Action}Middleware] → [{Entity}Handler]
```

| Layer | Responsibility |
|---|---|
| `AuthorizationMiddleware` | ACL gate — always first |
| `BodyParamsMiddleware` | Decode request body (JSON / form) — include on POST/PATCH/PUT only |
| `Process{Action}Middleware` | Validate + persist data; set request attribute with result; call `$handler->handle($request)` |
| `{Entity}Handler` | Read from repository + request attributes; render template or JSON |

The handler **never** touches `$request->getParsedBody()`, `$request->getUploadedFiles()`, or any write operation. It reads what the middleware already resolved from the request attributes.

---

## HttpMethodProcessorTrait

`Webware\Core\HttpMethodProcessorTrait` is the standard way to implement a `MiddlewareInterface` that needs to branch on HTTP method without `if/elseif` chains.

```php
use Webware\Core\HttpMethodProcessorTrait;

final class ProcessManifestUploadMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private readonly ManifestRepositoryInterface $manifests,
        private readonly ManifestCsvParser $parser,
    ) {}

    // Override only the verbs this middleware handles.
    // Unhandled verbs (GET etc.) fall through to $handler->handle($request) automatically.

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // validate, parse, persist…
        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }
}
```

Default implementations for `processGet`, `processPost`, `processPatch`, `processDelete` all call `$handler->handle($request)` — only override the methods you need.

**Do not implement `process()` directly** — the trait provides it and dispatches to the verb method.

---

## WriteResult Enum

`Webware\Acl\Admin\WriteResult` is the standard contract for passing write outcome from middleware to handler via a request attribute.

```php
// Middleware sets it:
return $handler->handle(
    $request->withAttribute(WriteResult::Success->value, $success)
);

// Handler reads it:
if ($request->getAttribute(WriteResult::Success->value) === true) {
    // e.g. emit HTMX trigger to close modal
    $response = $response->withHeader(Header::Trigger->value, json_encode(['closeModal' => null]));
}
```

Cases:
- `WriteResult::Success` — write completed without error
- `WriteResult::Failure` — write was attempted but failed

---

## RouteProvider Pipeline Wiring

Write endpoints require middleware in the stack **before** the handler:

```php
// GET — no write middleware needed
$routeCollector->get(
    '/manifests/upload',
    $middlewareFactory->prepare([
        AuthorizationMiddleware::class,
        ManifestUploadHandler::class,      // handler only — renders the form
    ]),
    'manifest.upload'
);

// POST — middleware processes the upload, handler renders the result
$routeCollector->post(
    '/manifests/upload',
    $middlewareFactory->prepare([
        AuthorizationMiddleware::class,
        ProcessManifestUploadMiddleware::class,   // does the work
        ManifestUploadHandler::class,             // renders based on WriteResult
    ]),
    'manifest.upload.store'
);
```

**`BodyParamsMiddleware`** (`Mezzio\Helper\BodyParams\BodyParamsMiddleware`) must be included on POST/PATCH/PUT routes when the body is JSON. For `multipart/form-data` (file uploads) it is not needed — PHP populates `$_POST` and `$_FILES` automatically, which Diactoros exposes via `getParsedBody()` and `getUploadedFiles()`.

---

## Naming Conventions

| Purpose | Class name pattern | Namespace pattern |
|---|---|---|
| Data-processing middleware | `Process{Action}Middleware` | `{Module}\Middleware\` |
| Middleware factory | `Process{Action}MiddlewareFactory` | `{Module}\Middleware\Container\` |
| Read-only / render handler | `{Entity}Handler` or `{Action}Handler` | `{Module}\RequestHandler\` |
| Handler factory | `{Action}HandlerFactory` | `{Module}\RequestHandler\Container\` |

The `Process` prefix on middleware is mandatory — it signals that the class performs a write operation and must appear before the handler in the pipeline.

---

## What Belongs Where — Quick Reference

| Task | Goes in |
|---|---|
| Parse uploaded CSV file | `ProcessManifestUploadMiddleware::processPost()` |
| Validate file type / size | `ProcessManifestUploadMiddleware::processPost()` |
| Call `$repository->insertFromCsv()` | `ProcessManifestUploadMiddleware::processPost()` |
| Render the upload form (GET) | `ManifestUploadHandler::handle()` |
| Render success / error page (POST result) | `ManifestUploadHandler::handle()` using `WriteResult` attribute |
| Redirect after success | Either middleware (if handler is not needed) or handler reading `WriteResult` |
| `$request->getParsedBody()` | **Middleware only** |
| `$request->getUploadedFiles()` | **Middleware only** |
| `$template->render(...)` | **Handler only** |
| `$repository->findAll(...)` (reads) | Handler or middleware — prefer handler for read-only data needed for rendering |

---

## SystemMessenger — User Feedback from Middleware

Middleware must not render HTML. To provide user feedback from a write operation, use `SystemMessengerInterface` (from `axleus/axleus-message`):

```php
/** @var SystemMessengerInterface|null $messenger */
$messenger = $request->getAttribute(SystemMessengerInterface::class);
$messenger?->success('Manifest imported successfully.');
$messenger?->error('Could not parse the uploaded file.');
```

The messenger is null-safe — always use `?->`. Toast messages appear in the layout container and survive HTMX navigation.

---

## Module Directory Layout

```
src/{module}/src/
    ConfigProvider.php
    RouteProvider.php
    Entity/
    Repository/
        {Entity}RepositoryInterface.php
        {Entity}Repository.php
        {Entity}RepositoryFactory.php         ← in same directory as repository
    Middleware/
        Process{Action}Middleware.php
        Container/
            Process{Action}MiddlewareFactory.php
    RequestHandler/
        {Entity}ListHandler.php
        {Entity}DetailHandler.php
        {Action}Handler.php
        Container/
            {Handler}Factory.php
    Listener/
        Register{Module}ResourcesListener.php
        Register{Module}RulesListener.php
        Register{Module}RouteMappingsListener.php
        Register{Module}WidgetListener.php    ← if module contributes a dashboard widget
    Widget/
        {Module}DashboardWidget.php           ← if applicable
    Container/
        RouteProviderFactory.php
        Register{Module}*ListenerFactory.php
```

---

## Anti-Patterns — Never Do These

- `if ($request->getMethod() === 'POST')` inside a `RequestHandlerInterface` — move the POST logic to a middleware
- Calling `$repository->insert(...)` or any write inside a handler
- Calling `$template->render(...)` inside a middleware
- Skipping `Process*Middleware` and doing everything in the handler because "it's simpler"
- Using `MiddlewareInterface` for read-only operations that belong in a handler
