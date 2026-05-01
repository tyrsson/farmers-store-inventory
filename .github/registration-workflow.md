# Registration Workflow — Session State (May 1, 2026)

## What Works
- Registration form submits → `RegistrationMiddleware` → `SaveUserHandler` → UUID7 token generated, user inserted with `active=0`
- `CommandHandlerMiddleware` now forwards via `$handler->handle($result)` (upstream fix merged, pulled in via `--prefer-source`)
- `EmptyPipelineHandler` now returns `CommandResult` passthrough instead of throwing (local vendor fix, needs upstream push — see `docs/planning/upstream-fixes-needed.md`)
- `PostHandleMiddleware` now runs and dispatches `PostHandleEvent`
- `SendVerificationEmailListener` fires, email is sent via Mailpit (confirmed)
- Verification link in email hits `VerifyEmailHandler`, token validated, user activated
- **`VerifyEmailHandler` success path**: issues `302 RedirectResponse('/login')` + `SystemMessenger->success('Email verified! You may now sign in.', hops: 1, now: false)`
- **`LoginHandler` GET**: reads `SystemMessenger->getMessages()` and passes as `flashMessages` to template
- **`login.phtml`**: renders inline Bootstrap alert for each flash message level (dismissible `.alert-{level}`)
- Flash message is visible on full-page 302 redirect (non-HTMX) ✅

## What Is NOT Working / Pending
- **`RegistrationHandler`** still uses `$request->hasHeader(HtmxRequestHeader::Request->value)`. Should use `$request->getAttribute(...)` because `ServerRequestFilter` maps HTMX headers to request attributes. **Not yet changed.**

## Resolved
- ~~No toast notification shown after email verification redirect to `/login`~~ — resolved: 302 + flash + inline Bootstrap alert instead of `HX-Location` / `HX-Trigger` (which only fire on HTMX-intercepted requests, not browser navigations).
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
- `src/User/src/RequestHandler/VerifyEmailHandler.php` — NEW; success path: `302 RedirectResponse('/login')` + `SystemMessenger->success(...)`
- `src/User/src/RequestHandler/Container/VerifyEmailHandlerFactory.php` — NEW
- `src/User/src/Listener/SendVerificationEmailListener.php` — NEW
- `src/User/src/Listener/Container/SendVerificationEmailListenerFactory.php` — NEW
- `src/User/src/RouteProvider.php` — added `GET /verify-email/{token}` → `user.verify-email`
- `src/User/src/ConfigProvider.php` — registered new handler + listener factories
- `src/User/src/CommandHandler/SaveUserHandler.php` — UUID7 token, `active=0`, returns token as result
- `src/User/src/Entity/User.php` — added `verificationToken`, `tokenCreatedAt` nullable fields
- `src/User/src/Repository/UserRepository.php` — `findByVerificationToken()` + `hydrate()` update
- `src/User/src/RequestHandler/LoginHandler.php` — reads `SystemMessenger->getMessages()`, passes as `flashMessages` to template; removed `HX-Trigger` approach
- `src/User/templates/user/login.phtml` — inline Bootstrap dismissible alert block for flash messages
- `config/autoload/commandbus-event.global.php` — NEW: wires `SendVerificationEmailListener` to `PostHandleEvent`
- `config/autoload/user.global.php` — NEW: `base_url`, `from_email`, `from_name`, `verification_token_ttl`
- `config/autoload/mailer.local.php` — NEW: SMTP config pointing at `mailpit:1025`
- `data/schema/003_user.sql` — added `verification_token VARCHAR(36) NULL`, `token_created_at DATETIME NULL`, `active DEFAULT 0`
- `data/schema/015_log.sql` — NEW: `log` table for Monolog DB handler
- `docker-compose.yml` — added `mailpit` service (ports 8025/1025)
- `docs/planning/upstream-fixes-needed.md` — NEW: documents `EmptyPipelineHandler` fix needed upstream
