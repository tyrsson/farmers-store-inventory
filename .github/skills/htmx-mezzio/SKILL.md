---
name: "htmx-mezzio"
description: "ALWAYS load this skill when creating or modifying any handler, page, partial, template, or HTMX interaction in this project. The custom 3-layer rendering stack (layout / body / page) is non-obvious and violations silently break HTMX navigation, JS deduplication, and the toast system."
argument-hint: "<what to create — e.g. 'new inventory page', 'product detail handler', 'HTMX partial for cart updates', 'side nav link'>"
---

## HTMX Swap Target — Critical Architecture Note

HTMX **does not swap `<body>`**. The swap target is `<main class="main-content">` inside `src/Htmx/templates/body/default.phtml`.

The rendering stack has three layers:
1. **Page template** (`app::{page-name}`) — handler content, renders into `$this->content`
2. **Body template** (`src/Htmx/templates/body/default.phtml`) — side nav, mobile nav, `<main class="main-content"><?= $this->content ?></main>`, footer. This is what HTMX swaps in on boosted navigation.
3. **Layout template** (`src/App/templates/layout/default.phtml`) — the full HTML document with `<head>`, all CDN assets, the `<script>` block, and the toast container. This is rendered **only once** on the initial full-page load.

On HTMX requests, `DetectAjaxRequestMiddleware` sets `layout = false`, skipping Layer 3. Only the body partial (Layer 2) is returned and swapped into `<main>`.

**Consequences for JS and UI elements:**
- The layout `<script>` block and all its event listeners (`htmx.on`, `document.addEventListener`) execute **once** and persist for the session — no duplication.
- The toast container (`#systemMessage`) lives in the layout outside `<main>` and is never swapped out.
- Do not add JS to `body/default.phtml` — it will re-execute on every navigation and cause duplicate listeners.
