# Login / Logout Workflow

## Overview

Authentication uses `mezzio/mezzio-authentication-session` (`PhpSession`
adapter). Credentials are checked against the database. Only **active** accounts
may log in. Sessions are stored in PHP's native session mechanism. Logout
destroys the session.

---

## Routes

| Method | Path      | Route name          | Middleware stack                                                                      |
|--------|-----------|---------------------|--------------------------------------------------------------------------------------|
| GET    | `/login`  | `user.login`        | `DisableBodyMiddleware` → `LoginHandler`                                             |
| POST   | `/login`  | `user.login.post`   | `DisableBodyMiddleware` → `AuthenticationMiddleware` → `LoginHandler`                |
| GET    | `/logout` | `user.logout`       | `AuthenticationMiddleware` → `LogoutHandler`                                         |

---

## Login Workflow Diagram

```
Browser                         Server
  |                                |
  |--- GET /login ---------------->|
  |                                |
  |               [LoginHandler]
  |               - Check UserInterface attribute on request
  |               |
  |               [already logged in]
  |<-- 302 / -----------------------|
  |                                |
  |               [not logged in]
  |<-- 200 login form + any toasts-|
  |                                |
  |--- POST /login --------------->|
  |     {email, password}          |
  |                                |
  |               [AuthenticationMiddleware]
  |               - Calls PhpSession::authenticate()
  |               |
  |               [PhpSession::authenticate()]
  |               - Reads email + password from POST body
  |               - Calls UserRepository::authenticate()
  |               |
  |               [UserRepository::authenticate()]
  |               - SELECT user by email
  |               - Check active === true (bool cast from DB)
  |               - password_verify(password, hash)
  |               |
  |               [auth fails — inactive or wrong password]
  |               - Returns null to PhpSession
  |               - PhpSession calls unauthorizedResponse()
  |<-- 302 /login -----------------| (redirect — no flash, no toast yet)
  |                                |
  |               [auth succeeds]
  |               - Stores UserInterface in session
  |               - Puts UserInterface on request attribute
  |               |
  |               [LoginHandler]
  |               - Detects UserInterface on request attribute
  |<-- 302 / ----------------------|
```

---

## Logout Workflow Diagram

```
Browser                         Server
  |                                |
  |--- GET /logout --------------->|
  |                                |
  |               [AuthenticationMiddleware]
  |               - Validates session; redirects if not authenticated
  |               |
  |               [LogoutHandler]
  |               - Clears UserInterface from session
  |<-- 302 /login -----------------|
```

---

## Key Classes

| Class | Namespace | Responsibility |
|-------|-----------|----------------|
| `LoginHandler` | `User\RequestHandler` | Render login form (GET) or redirect on success (POST) |
| `LogoutHandler` | `User\RequestHandler` | Clear session, redirect to login |
| `PhpSession` | `Mezzio\Authentication\Session` | Adapter: reads POST body, delegates to `UserRepository::authenticate()` |
| `UserRepository` | `User\Repository` | `authenticate(string $credential, string $password): ?UserInterface` |

---

## Authentication Configuration

Registered in `User\ConfigProvider::getAuthenticationConfig()`:

```php
'authentication' => [
    'redirect' => '/login',         // Unauthenticated → redirect here
    'username' => 'email',          // POST field name for the identifier
    'password' => 'password',       // POST field name for the credential
],
```

The `AuthenticationInterface` alias resolves to `PhpSession::class`.

---

## Authentication Logic (`UserRepository::authenticate`)

1. Find user row by `email` column.
2. Verify `$user->active === true` — inactive accounts return `null`.
3. Verify `password_verify($password, $user->passwordHash)`.
4. On success return a `Mezzio\Authentication\DefaultUser` (or equivalent).
5. On any failure return `null`.

---

## Session Storage

Sessions are handled by `mezzio/mezzio-session` with the default PHP session
handler (`laminas/laminas-session`). The authenticated `UserInterface` is
stored under the key configured by `PhpSession`.

---

## Known Limitations (as of v0.1.x)

- **Login failure gives no toast**: `PhpSession::unauthorizedResponse()` issues
  a bare 302 redirect to `/login`. There is no flash message on failed login.
  This is a planned improvement — see the discussion in session notes.
- **No authorization ACL**: any authenticated user can access any authenticated
  route. Role-based access control is not yet configured.
