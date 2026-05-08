# Toast / Flash Notification System

## Overview

User-facing feedback (success after registration, after email verification, etc.)
is delivered as Bootstrap 5 toasts via a custom `ImsMessenger` view helper backed
by `axleus/axleus-message` `SystemMessenger`.

---

## Components

### `ImsMessengerMiddleware`
**Namespace**: `App\Middleware`

Runs after `SessionMiddleware` in the global pipeline. Creates a `SystemMessenger`
bound to the current PHP session, injects it into the `ImsMessenger` view helper,
and forwards it on the request attribute `SystemMessengerInterface::class`.

```
SessionMiddleware
    └── ImsMessengerMiddleware          ← creates SystemMessenger, wires helper
            └── ... routing pipeline
```

### `ImsMessenger` View Helper
**Namespace**: `App\View\Helper`

- Registered as `imsMessenger` in the `HelperPluginManager`.
- Called at the bottom of every template that may receive flash messages:
  `<?= $this->imsMessenger() ?>`
- Renders pending messages as hidden `<div class="ims-pending-toast">` elements
  carrying `data-bs-*` attributes.
- Implements `StatefulHelperInterface` — `resetState()` clears messages and
  nulls the messenger reference at end-of-request.

### `system.messenger.js`
**Path**: `public/assets/js/system.messenger.js`

- Listens on `DOMContentLoaded` and `htmx:afterSettle`.
- `showPendingToasts()` queries all `.ims-pending-toast` elements, moves them
  into `#systemMessage` container, initialises Bootstrap `Toast`, auto-hides
  after 4 000 ms.

### `#systemMessage` Container
**Location**: `src/App/templates/layout/default.phtml`

Fixed-position toast container rendered in the layout. Always present in the
DOM regardless of whether there are messages.

---

## Flash Message Lifecycle

```
Handler / Middleware
  - $messenger->success('...', hops: 1, now: false)
      ← stored in PHP session

        ↓ redirect ↓

Layout render (next request)
  - ImsMessengerMiddleware reads session → SystemMessenger
  - Template calls <?= $this->imsMessenger() ?>
  - Outputs hidden .ims-pending-toast div(s)

        ↓ browser ↓

htmx:afterSettle / DOMContentLoaded
  - showPendingToasts() fires
  - Bootstrap Toast shown for 4 s then hidden
```

---

## Usage in Templates

Call at the **bottom** of any template body that can receive flash toasts:

```php
<?= $this->imsMessenger() ?>
```

For inline validation errors (not flash) use template variables directly
(`$errors` array) — do **not** put inline errors through the messenger.

---

## Adding a New Toast Message

In any middleware or handler that has the `SystemMessengerInterface` attribute:

```php
/** @var SystemMessengerInterface|null $messenger */
$messenger = $request->getAttribute(SystemMessengerInterface::class);

// Flash (survives one redirect)
$messenger?->success('Your message', hops: 1, now: false);

// Immediate (shows on the current response, no redirect needed)
$messenger?->info('Info toast', hops: 1, now: true);
```

Available levels (map to Bootstrap colours):

| Method | Bootstrap colour |
|--------|-----------------|
| `success()` | green  |
| `info()`    | blue   |
| `warning()` | yellow |
| `error()`   | red    |
