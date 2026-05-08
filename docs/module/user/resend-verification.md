# Resend Verification Email Workflow

## Overview

Users whose token has expired (or who never received the original email) can
request a fresh token at `/resend-verification`. A new UUID v7 token replaces
the old one. The email address is accepted without revealing whether it is
registered (prevents user enumeration). Already-active accounts are silently
redirected to `/login`.

---

## Routes

| Method | Path                   | Route name               | Middleware stack                                          |
|--------|------------------------|--------------------------|----------------------------------------------------------|
| GET    | `/resend-verification` | `user.verify-email.resend` | `DisableBodyMiddleware` → `ResendVerificationHandler`  |
| POST   | `/resend-verification` | `user.verify-email.resend` | `DisableBodyMiddleware` → `ResendVerificationHandler`  |

---

## Workflow Diagram

```
Browser                         Server
  |                                |
  |--- GET /resend-verification -->|
  |<-- 200 resend form ------------|
  |                                |
  |--- POST /resend-verification ->|
  |     {email}                    |
  |                                |
  |               [ResendVerificationHandler]
  |               - Parse email from body
  |               |
  |               [email empty]
  |<-- 200 form + error -----------| ("Please enter a valid email address.")
  |                                |
  |               [look up user by email]
  |               UserRepository::findByEmail()
  |               |
  |               [user found AND active === true]
  |<-- 302 /login -----------------| (silent — account already verified)
  |                                |
  |               [user not found]
  |               (silently skip email send — no enumeration)
  |<-- 200 "check your inbox" -----|
  |                                |
  |               [user found AND active === false]
  |               - Generate new UUID v7 token
  |               - UPDATE user SET
  |                   verification_token = {token},
  |                   token_created_at   = now()
  |               - Build URL: /verify-email/{token}
  |               - Send HTML + plain-text email via Mailer
  |<-- 200 "check your inbox" -----|
```

---

## Key Classes

| Class | Namespace | Responsibility |
|-------|-----------|----------------|
| `ResendVerificationHandler` | `User\RequestHandler` | Issues new token, sends email, always shows "check inbox" |
| `UserRepository` | `User\Repository` | `findByEmail(string $email): ?User` |

---

## Security Notes

- **No user enumeration**: unknown email addresses silently show the same
  "check your inbox" page as known addresses.
- **Active accounts redirected**: users with `active === true` are sent to
  `/login` — they have no need to reverify.
- **Token replacement**: the old token is overwritten atomically in the same
  `UPDATE` call, invalidating any previous link.

---

## Configuration

Same as registration — reads from `user` config key:

```php
'user' => [
    'base_url'   => 'http://localhost:8080',
    'from_email' => 'noreply@farmers-ims.local',
    'from_name'  => 'Farmers IMS',
],
```

The resend handler does **not** use `verification_token_ttl` — it only issues
a new token. The TTL is enforced by `VerifyEmailHandler` when the link is clicked.
