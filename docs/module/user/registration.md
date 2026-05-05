# User Registration Workflow

## Overview

New users self-register via the public `/register` form. The account is created
in an **inactive** state. A verification email is sent via the command-bus event
listener. The user must click the link in the email to activate the account
before login is permitted.

---

## Routes

| Method | Path        | Route name            | Middleware stack                                              |
|--------|-------------|-----------------------|--------------------------------------------------------------|
| GET    | `/register` | `user.register`       | `DisableBodyMiddleware` → `RegistrationHandler`              |
| POST   | `/register` | `user.register.post`  | `DisableBodyMiddleware` → `RegistrationMiddleware` → `RegistrationHandler` |

---

## Workflow Diagram

```
Browser                    Server
  |                           |
  |--- GET /register -------->|
  |<-- 200 registration form -|
  |                           |
  |--- POST /register ------->|
  |     {firstName, lastName, |
  |      email, password,     |
  |      storeId}             |
  |                           |
  |              [RegistrationMiddleware]
  |              - Validate required fields
  |              - Validate email format
  |              |
  |              [validation fails]
  |<-- 422 form + errors -----| (re-render, no redirect)
  |                           |
  |              [validation passes]
  |              - Dispatch SaveUserCommand
  |              |
  |              [SaveUserHandler]
  |              - Hash password (password_hash)
  |              - Insert user row (active=0)
  |              - Generate UUID v7 verification token
  |              - Store token + token_created_at
  |              - Return token in CommandResult
  |              |
  |              [command fails]
  |<-- 500 form + error ------| (duplicate email etc.)
  |                           |
  |              [command succeeds]
  |              - Set success flash message (hops=1)
  |              - Pass registration_result on request
  |              |
  |              [SendVerificationEmailListener] (PostHandleEvent)
  |              - Build verification URL: /verify-email/{token}
  |              - Send HTML + plain-text email via Mailer
  |              |
  |              [RegistrationHandler]
  |              - Detect registration_result on request
  |<-- 302 /login -----------|
  |                           |
  |--- GET /login ----------->|
  |<-- 200 login + toast -----| ("Registration successful! Check your email.")
```

---

## Key Classes

| Class | Namespace | Responsibility |
|-------|-----------|----------------|
| `RegistrationMiddleware` | `User\Middleware` | Validates form, dispatches `SaveUserCommand`, sets flash toast |
| `RegistrationHandler` | `User\RequestHandler` | Renders form (GET) or redirects on success (POST) |
| `SaveUserHandler` | `User\CommandHandler` | Inserts user row, hashes password, generates token |
| `SaveUserCommand` | `User\Command` | DTO: firstName, lastName, email, password, storeId |
| `SendVerificationEmailListener` | `User\Listener` | Fires on `PostHandleEvent`, sends verification email |

---

## User DB State After Registration

| Column              | Value                        |
|---------------------|------------------------------|
| `active`            | `0`                          |
| `verification_token`| UUID v7 string               |
| `token_created_at`  | current timestamp            |
| `password_hash`     | `password_hash()` bcrypt     |

---

## Flash Toast

On successful registration a `success` level flash message is stored in the
session with `hops=1`. It surfaces as a Bootstrap toast on the `/login` page
via `ImsMessenger` view helper + `system.messenger.js`.

---

## Configuration

```php
// config/autoload/user.global.php
'user' => [
    'base_url'               => 'http://localhost:8080',
    'from_email'             => 'noreply@farmers-ims.local',
    'from_name'              => 'Farmers IMS',
    'verification_token_ttl' => 86400, // seconds (default 24 hours)
],
```

Override `verification_token_ttl` in a `user.local.php` to shorten expiry for
testing (e.g. `30` seconds).
