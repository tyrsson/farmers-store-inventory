# webware-navigation

ACL-aware navigation trees for Mezzio, driven by `Route::setOptions()`.

## Documentation

| Document | Contents |
|---|---|
| [Overview](docs/overview.md) | Goals, quick start, route options reference |
| [Architecture](docs/architecture.md) | Design decisions and rationale |
| [Component Reference](docs/component-reference.md) | API docs for every class |
| [Extending](docs/extending.md) | Custom renderers, planned RendererPluginManager |
| [Testing](docs/testing.md) | Unit test examples and guidance |

## Installation

Registered as a path-repository package in `composer.json`. The PSR-4 namespace
`Webware\Navigation\` maps to `src/webware-navigation/src/`.

Add to `config/config.php`:

```php
Webware\Navigation\ConfigProvider::class,
```

The middleware is self-registering via `ConfigProvider`. No manual pipeline edit
is required beyond the entry in `config/config.php`.

## Namespace

```
Package:   webware/navigation
Namespace: Webware\Navigation\
```
