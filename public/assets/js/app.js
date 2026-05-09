/**
 * app.js — v2
 * Minimal shared JS. Bootstrap handles: offcanvas sidebar, collapse,
 * modals, toasts, form-switches. Only non-Bootstrap behaviour lives here.
 */

(function () {
  'use strict';

  // ── Bootstrap component init ─────────────────────────────────────────────
  // After every HTMX swap the body template is replaced, destroying Bootstrap's
  // Collapse (and other) instances. Re-running getOrCreateInstance after
  // htmx:afterSettle restores open/close behaviour without touching state.
  function initBootstrapComponents() {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (el) {
      var targetSelector = el.getAttribute('data-bs-target') || el.getAttribute('href');
      if (targetSelector) {
        var targetEl = document.querySelector(targetSelector);
        if (targetEl) {
          bootstrap.Collapse.getOrCreateInstance(targetEl, { toggle: false });
        }
      }
    });
  }

  document.addEventListener('htmx:afterSettle', initBootstrapComponents);

  // ── Store switcher — update active store display text ────────────────────
  // When a store button in the sidebar collapse is clicked, mark it active
  // and update the store label in the topbar / sidebar header.
  function initStoreSwitcher() {
    document.querySelectorAll('.ims-store-item').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.ims-store-item').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  }

  document.addEventListener('htmx:afterSettle', initStoreSwitcher);
  initStoreSwitcher();

  // ── Status toggle buttons (damage-detail page) ───────────────────────────
  // Selecting a status makes that button active and deselects the others.
  const statusBtns = document.querySelectorAll('.ims-status-btn');
  statusBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      statusBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

})();

// code previously used for fixing Tracy debugger during htmx boosted request
(function () {
    let tracyEl = null;

    document.addEventListener('htmx:beforeSwap', function () {
        tracyEl = document.getElementById('tracy-debug');
        if (tracyEl) {
            tracyEl.remove(); // detach before HTMX replaces body innerHTML
        }
    });

    // Use htmx:afterSwap (not afterSettle) to re-attach as early as possible,
    // before Tracy's async _tracy_bar script can fire and call loadAjax().
    document.addEventListener('htmx:afterSwap', function () {
        if (tracyEl) {
            document.body.appendChild(tracyEl);
            tracyEl = null;
        }
    });
})();
