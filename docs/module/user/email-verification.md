# Email Verification Workflow

## Overview

After registration a UUID v7 token is stored against the user row. The user
clicks a link in the verification email. The token is validated for existence
and age. On success the account is activated and the user is redirected to
login with a success toast.

---

## Routes

| Method | Path                    | Route name          | Middleware stack                                    |
|--------|-------------------------|---------------------|-----------------------------------------------------|
| GET    | `/verify-email/{token}` | `user.verify-email` | `DisableBodyMiddleware` → `VerifyEmailHandler`      |

---

## Workflow Diagram

```
Browser                      Server
  |                             |
  |--- GET /verify-email/{tok}->|
  |                             |
  |                [VerifyEmailHandler]
  |                - Extract {token} from route attribute
  |                |
  |                [token is empty string]
  |<-- 200 error page ----------| ("Invalid verification link.")
  |                             |
  |                [look up user by token]
  |                UserRepository::findByVerificationToken()
  |                |
  |                [no user found]
  |<-- 200 error page ----------| ("Invalid or already used link.")
  |                             |
  |                [user found — check age]
  |                age = now() - token_created_at
  |                |
  |                [age > verification_token_ttl]
  |<-- 200 expired page --------| (error="link has expired", expired=true)
  |                (page shows "resend" link)
  |                             |
  |                [age within TTL]
  |                - UPDATE user SET active=1,
  |                    verification_token=NULL,
  |                    token_created_at=NULL
  |                - Set success flash message (hops=1)
  |<-- 302 /login -------------|
  |                             |
  |--- GET /login ------------->|
  |<-- 200 login + toast -------| ("Email verified! You may now sign in.")
```

---

## Key Classes

| Class | Namespace | Responsibility |
|-------|-----------|----------------|
| `VerifyEmailHandler` | `User\RequestHandler` | Validates token, activates account, sets toast |
| `UserRepository` | `User\Repository` | `findByVerificationToken(string $token): ?User` |

---

## User DB State After Verification

| Column               | Value  |
|----------------------|--------|
| `active`             | `1`    |
| `verification_token` | `NULL` |
| `token_created_at`   | `NULL` |

---

## Token TTL

Configured via `user.verification_token_ttl` (seconds). Default: `86400` (24 h).

```php
// config/autoload/user.local.php  — testing override
return ['user' => ['verification_token_ttl' => 30]];
```

---

## Error States

| Condition | Template variable | Template |
|-----------|-------------------|----------|
| Token empty / missing | `error` | `user::verify-email` |
| Token not found in DB | `error` | `user::verify-email` |
| Token expired | `error` + `expired = true` | `user::verify-email` |

When `expired === true` the template should render a link to `/resend-verification`.
