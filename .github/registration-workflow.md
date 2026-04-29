# Registration Workflow — Session State (April 29, 2026)

## What Works
- Registration form submits → `RegistrationMiddleware` → `SaveUserHandler` → UUID7 token generated, user inserted with `active=0`
- `CommandHandlerMiddleware` now forwards via `$handler->handle($result)` (upstream fix merged, pulled in via `--prefer-source`)
- `EmptyPipelineHandler` now returns `CommandResult` passthrough instead of throwing (local vendor fix, needs upstream push — see `docs/planning/upstream-fixes-needed.md`)
- `PostHandleMiddleware` now runs and dispatches `PostHandleEvent`
- `SendVerificationEmailListener` fires, email is sent via Mailpit (confirmed)
- Verification link in email hits `VerifyEmailHandler`, token validated, user activated

## What Is NOT Working
- **No toast notification** shown after email verification redirect to `/login`
- Flash message is set via `$messenger?->success(...)` with `hops: 1, now: false` then redirect

## Open Questions (to investigate)
1. Does the toast system render on **normal page loads** (non-HTMX `302` redirects)?
   - The verify email link is clicked directly in the browser — no `HX-Request` header
   - `HX-Location` response header only applies to HTMX requests
   - `RedirectResponse` fallback is used — does the login page render queued flash messages on a full page load?
2. If toasts only render during HTMX navigations, the verify email flow needs a different UX approach — e.g. render a dedicated "email verified" page instead of redirecting with a flash message.

## Current State of VerifyEmailHandler
- Updated to check `HX-Request` header and return `HtmlResponse('', 200, [HtmxResponseHeader::Location->value => '/login'])` for HTMX, `RedirectResponse('/login')` for plain browser requests
- File: `src/User/src/RequestHandler/VerifyEmailHandler.php`

## Vendor Fixes Made (local only — need upstream push)

### webware/command-bus
1. `src/Middleware/CommandHandlerMiddleware.php` — added `$handler->handle($result)` call after resolving handler (upstream merged at commit `75ce3f3`, but has commented-out old line still present)
2. `src/Handler/EmptyPipelineHandler.php` — added `CommandResultInterface` passthrough check (**NOT yet pushed upstream** — see `docs/planning/upstream-fixes-needed.md`)

### webware/commandbus-event
- `src/Middleware/PostHandleMiddleware.php` — no changes needed; works correctly as-is

## Key Architecture — Command Bus Pipeline
Pipeline order (by priority): Pre(100) → CommandHandler(1) → Post(-100)

- `CommandHandlerMiddleware` — resolves and executes the real handler, forwards `CommandResult` via `$handler->handle($result)` to Next
- `PostHandleMiddleware` — receives `CommandResult` as `$command`, dispatches `PostHandleEvent($command)`, calls `$handler->handle($command)` to continue chain (must NOT be the terminal handler — users can add middleware after it)
- `EmptyPipelineHandler` — receives `CommandResult` at end of chain, returns it directly (local fix)

## Files Created/Modified
- `src/User/src/RequestHandler/VerifyEmailHandler.php` — NEW
- `src/User/src/RequestHandler/Container/VerifyEmailHandlerFactory.php` — NEW
- `src/User/src/Listener/SendVerificationEmailListener.php` — NEW
- `src/User/src/Listener/Container/SendVerificationEmailListenerFactory.php` — NEW
- `src/User/src/RouteProvider.php` — added `GET /verify-email/{token}` → `user.verify-email`
- `src/User/src/ConfigProvider.php` — registered new handler + listener factories
- `src/User/src/CommandHandler/SaveUserHandler.php` — UUID7 token, `active=0`, returns token as result
- `src/User/src/Entity/User.php` — added `verificationToken`, `tokenCreatedAt` nullable fields
- `src/User/src/Repository/UserRepository.php` — `findByVerificationToken()` + `hydrate()` update
- `config/autoload/commandbus-event.global.php` — NEW: wires `SendVerificationEmailListener` to `PostHandleEvent`
- `config/autoload/user.global.php` — NEW: `base_url`, `from_email`, `from_name`, `verification_token_ttl`
- `config/autoload/mailer.local.php` — NEW: SMTP config pointing at `mailpit:1025`
- `data/schema/003_user.sql` — added `verification_token VARCHAR(36) NULL`, `token_created_at DATETIME NULL`, `active DEFAULT 0`
- `docker-compose.yml` — added `mailpit` service (ports 8025/1025)
- `docs/planning/upstream-fixes-needed.md` — NEW: documents `EmptyPipelineHandler` fix needed upstream
