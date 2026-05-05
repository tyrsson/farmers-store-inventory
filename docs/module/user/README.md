# User Module — Documentation Index

This directory documents the user authentication and account management workflows
implemented in `src/User/`.

---

## Contents

| File | Topic |
|------|-------|
| [registration.md](registration.md) | Self-registration form → inactive account → verification email |
| [email-verification.md](email-verification.md) | Token validation, account activation, TTL expiry |
| [resend-verification.md](resend-verification.md) | Requesting a fresh token without revealing account existence |
| [login.md](login.md) | Login / logout via `mezzio-authentication-session` |
| [toast-notifications.md](toast-notifications.md) | `ImsMessenger` flash toast system |

---

## High-Level Flow

```
 ┌──────────────┐   POST /register     ┌─────────────────────┐
 │   Browser    │ ──────────────────>  │ RegistrationMiddleware│
 └──────────────┘                      └────────┬────────────┘
                                                │ SaveUserCommand
                                                ▼
                                       ┌─────────────────────┐
                                       │   SaveUserHandler    │
                                       │  (active=0, token)   │
                                       └────────┬────────────┘
                                                │ PostHandleEvent
                                                ▼
                                       ┌─────────────────────┐
                                       │ SendVerificationEmail│
                                       │     Listener         │
                                       └────────┬────────────┘
                                                │ email sent
                                                │
                                       302 → /login (toast)
                                                │
 ┌──────────────┐   GET /verify-email  ┌────────▼────────────┐
 │   Browser    │ ──────────────────>  │  VerifyEmailHandler  │
 └──────────────┘       /{token}       └────────┬────────────┘
                                                │ active=1
                                                │
                                       302 → /login (toast)
                                                │
 ┌──────────────┐   POST /login        ┌────────▼────────────┐
 │   Browser    │ ──────────────────>  │ AuthenticationMiddlw │
 └──────────────┘                      └────────┬────────────┘
                                                │ UserRepository
                                                │ ::authenticate()
                                                │
                                       302 → / (logged in)
```

---

## Module Configuration Keys

| Key | Location | Purpose |
|-----|----------|---------|
| `user.base_url` | `user.global.php` | Base URL for verification links |
| `user.from_email` | `user.global.php` | Sender address for verification emails |
| `user.from_name` | `user.global.php` | Sender display name |
| `user.verification_token_ttl` | `user.global.php` | Token lifetime in seconds (default `86400`) |
| `authentication.redirect` | `User\ConfigProvider` | Unauthenticated redirect target (`/login`) |
| `authentication.username` | `User\ConfigProvider` | POST field for login identifier (`email`) |
| `authentication.password` | `User\ConfigProvider` | POST field for credential (`password`) |

---

## Known Limitations (v0.1.x)

- Login failure produces no toast — `PhpSession::unauthorizedResponse()` issues
  a plain redirect with no flash.
- No role-based authorization ACL is configured yet.
- `headTitle` in `layout/default.phtml` still reads `'Farmers IMS'`.
