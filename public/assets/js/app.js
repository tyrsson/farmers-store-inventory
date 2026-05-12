/**
 * app.js — v2
 * Minimal shared JS. Bootstrap handles: offcanvas sidebar, collapse,
 * modals, toasts, form-switches. Only non-Bootstrap behaviour lives here.
 */

(function () {
  'use strict';

  // ── Resources accordion toggle ────────────────────────────────────────────
  // Delegated on document — survives every HTMX swap.
  // Manages open/close imperatively so Bootstrap data-api race is avoided.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-ims-resource-toggle]');
    if (!btn) return;
    var targetId = btn.dataset.imsResourceToggle;
    var targetEl = document.getElementById(targetId);
    if (!targetEl) return;

    var isShown = targetEl.classList.contains('show');

    // Close any other open panels in the same accordion first
    var accordion = btn.closest('#resourcesAccordion');
    if (accordion) {
      accordion.querySelectorAll('.accordion-collapse.show').forEach(function (el) {
        if (el !== targetEl) {
          bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide();
          var otherBtn = accordion.querySelector('[data-ims-resource-toggle="' + el.id + '"]');
          if (otherBtn) {
            el.addEventListener('hidden.bs.collapse', function () {
              otherBtn.setAttribute('aria-expanded', 'false');
              otherBtn.classList.add('collapsed');
            }, { once: true });
          }
        }
      });
    }

    var instance = bootstrap.Collapse.getOrCreateInstance(targetEl, { toggle: false });
    if (isShown) {
      instance.hide();
      targetEl.addEventListener('hidden.bs.collapse', function () {
        btn.setAttribute('aria-expanded', 'false');
        btn.classList.add('collapsed');
      }, { once: true });
    } else {
      instance.show();
      targetEl.addEventListener('shown.bs.collapse', function () {
        btn.setAttribute('aria-expanded', 'true');
        btn.classList.remove('collapsed');
      }, { once: true });
    }
  });

  // ── Stores collapse toggle ────────────────────────────────────────────────
  // Delegated on document so it survives every HTMX body swap.
  // Uses Bootstrap imperatively — bypasses data-api to avoid double-toggle
  // races when htmx:afterSettle fires during a collapse animation.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-ims-stores-toggle]');
    if (!btn) return;
    var targetEl = document.getElementById('storesList');
    if (!targetEl) return;
    var instance = bootstrap.Collapse.getOrCreateInstance(targetEl, { toggle: false });
    instance.toggle();
    // Keep aria-expanded in sync for accessibility and CSS hooks
    targetEl.addEventListener('shown.bs.collapse',  function () { btn.setAttribute('aria-expanded', 'true');  }, { once: true });
    targetEl.addEventListener('hidden.bs.collapse', function () { btn.setAttribute('aria-expanded', 'false'); }, { once: true });
  });

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

  // ── ACL hierarchy view — create rule buttons ─────────────────────────────
  // Handles .ims-rule-create buttons in the hierarchy table.
  // If the target row has elevated descendants (roles that will silently gain
  // access via chain walk-up) or redundant descendants (already have explicit
  // rules), the redundancy warning modal is shown first.
  // After confirmation (or if no warning needed), a POST is submitted.

  var _pendingRulePost = null; // { url, role_pk, resource_pk, privilege_pk, type }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ims-rule-create');
    if (!btn) return;

    var row          = btn.closest('tr');
    var table        = btn.closest('table');
    if (!row || !table) return;

    var rolePk       = row.dataset.rolePk;
    var roleId       = row.dataset.roleId;
    var actionType   = btn.dataset.actionType; // 'allow' or 'deny'
    var elevated     = JSON.parse(row.dataset.elevated  || '[]');
    var redundant    = JSON.parse(row.dataset.redundant || '[]');
    var createUrl    = table.dataset.createUrl;
    var resourcePk   = table.dataset.resourcePk;
    var privilegePk  = table.dataset.privilegePk;
    var resource     = table.dataset.resource;
    var privilege    = table.dataset.privilege;

    _pendingRulePost = {
      url:          createUrl,
      role_pk:      rolePk,
      resource_pk:  resourcePk,
      privilege_pk: privilegePk,
      type:         actionType,
    };

    // Only show elevation warning when adding allow (deny doesn't elevate access)
    var hasElevation = actionType === 'allow' && elevated.length > 0;
    var hasRedundant = redundant.length > 0;

    if (hasElevation || hasRedundant) {
      // Populate modal
      var typeEl      = document.getElementById('redundancyRuleType');
      var roleEl      = document.getElementById('redundancyTargetRole');
      var resEl       = document.getElementById('redundancyResource');
      var privEl      = document.getElementById('redundancyPrivilege');
      var elevList    = document.getElementById('redundancyElevatedList');
      var elevSection = document.getElementById('redundancyElevationSection');
      var redList     = document.getElementById('redundancyDescendantList');
      var redSection  = document.getElementById('redundancyRedundantSection');

      if (typeEl)  { typeEl.textContent = actionType; typeEl.className = actionType === 'allow' ? 'text-success fw-bold' : 'text-danger fw-bold'; }
      if (roleEl)  roleEl.textContent  = roleId;
      if (resEl)   resEl.textContent   = resource;
      if (privEl)  privEl.textContent  = privilege;

      if (elevList && elevSection) {
        elevList.innerHTML = '';
        if (hasElevation) {
          elevated.forEach(function (r) {
            var badge = document.createElement('span');
            badge.className = 'badge bg-warning-subtle border border-warning-subtle text-warning-emphasis';
            badge.textContent = r;
            elevList.appendChild(badge);
          });
          elevSection.style.display = '';
        } else {
          elevSection.style.display = 'none';
        }
      }

      if (redList && redSection) {
        redList.innerHTML = '';
        if (hasRedundant) {
          redundant.forEach(function (r) {
            var badge = document.createElement('span');
            badge.className = 'badge bg-body-tertiary border text-secondary';
            badge.textContent = r;
            redList.appendChild(badge);
          });
          redSection.style.display = '';
        } else {
          redSection.style.display = 'none';
        }
      }

      var modal = new bootstrap.Modal(document.getElementById('redundancyWarningModal'));
      modal.show();
    } else {
      _submitRulePost(_pendingRulePost);
      _pendingRulePost = null;
    }
  });

  // Redundancy warning confirm — submit the pending POST
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#redundancyConfirmBtn');
    if (!btn || !_pendingRulePost) return;
    var pending = _pendingRulePost;
    _pendingRulePost = null;
    var modal = bootstrap.Modal.getInstance(document.getElementById('redundancyWarningModal'));
    if (modal) modal.hide();
    _submitRulePost(pending);
  });

  function _submitRulePost(data) {
    // Submit via HTMX programmatic request so hx-boost and swap work correctly
    htmx.ajax('POST', data.url, {
      target: 'main',
      swap:   'innerHTML',
      values: {
        role_pk:      data.role_pk,
        resource_pk:  data.resource_pk,
        privilege_pk: data.privilege_pk,
        type:         data.type,
      },
    });
  }


  // Populate edit/delete modals with data-* attributes from the trigger button.
  // Uses event delegation on the document so it works after HTMX swaps.
  document.addEventListener('show.bs.modal', function (e) {
    var trigger = e.relatedTarget;
    if (!trigger) return;

    // Route-map edit modal
    if (e.target.id === 'editMappingModal') {
      var route     = trigger.dataset.route     || '';
      var resource  = trigger.dataset.resource  || '';
      var privilege = trigger.dataset.privilege || '';
      var routeEl   = document.getElementById('edit_route_name');
      var resEl     = document.getElementById('edit_resource_id');
      var privEl    = document.getElementById('edit_privilege_id');
      if (routeEl) routeEl.value = route;
      if (resEl)   Array.from(resEl.options).forEach(function (o) { o.selected = o.value === resource; });
      if (privEl)  Array.from(privEl.options).forEach(function (o) { o.selected = o.value === privilege; });
    }

    // Route-map delete confirm modal
    if (e.target.id === 'deleteMappingModal') {
      var route = trigger.dataset.route || '';
      var nameEl     = document.getElementById('deleteRouteName');
      var confirmBtn = document.getElementById('deleteMappingConfirmBtn');
      if (nameEl)     nameEl.textContent = route;
      if (confirmBtn) {
        confirmBtn.setAttribute('hx-delete', '/admin/access/routes/' + encodeURIComponent(route));
        htmx.process(confirmBtn);
      }
    }

    // Role edit modal
    if (e.target.id === 'editRoleModal') {
      var roleId    = trigger.dataset.roleId    || '';
      var rolePk    = trigger.dataset.rolePk    || '';
      var parentId  = trigger.dataset.parentId  || '';
      var userCount = parseInt(trigger.dataset.userCount || '0', 10);
      var nameEl  = document.getElementById('edit_role_name');
      var parEl   = document.getElementById('edit_parent_id');
      var delBtn  = document.getElementById('editRoleDeleteBtn');
      if (nameEl) nameEl.value = roleId;
      if (parEl)  Array.from(parEl.options).forEach(function (o) { o.selected = o.value === parentId; });
      if (delBtn) {
        delBtn.disabled = userCount > 0;
        delBtn.title    = userCount > 0 ? userCount + ' users assigned — cannot delete' : '';
        delBtn.setAttribute('hx-delete', '/admin/access/roles/' + rolePk);
        htmx.process(delBtn);
      }
    }

    // Rule delete confirm modal
    if (e.target.id === 'deleteRuleModal') {
      var roleId    = trigger.dataset.roleId    || '';
      var resId     = trigger.dataset.resourceId || '';
      var privId    = trigger.dataset.privilegeId || '';
      var ruleType  = trigger.dataset.ruleType  || '';
      var ruleId    = trigger.dataset.ruleId    || '';
      var descEl    = document.getElementById('deleteRuleDesc');
      var confirmBtn = document.getElementById('deleteRuleConfirmBtn');
      if (descEl)     descEl.innerHTML = '<strong>' + ruleType + '</strong> for <code>' + roleId + '</code> &#x2192; <code>' + resId + '</code> / <code>' + privId + '</code>';
      if (confirmBtn) {
        confirmBtn.setAttribute('hx-delete', '/admin/access/rules/' + ruleId);
        htmx.process(confirmBtn);
      }
    }

    // Add privilege modal — set resource context
    if (e.target.id === 'addPrivilegeModal') {
      var resourcePk    = trigger.dataset.resourcePk    || '';
      var resourceLabel = trigger.dataset.resourceId    || '';
      var pkEl    = document.getElementById('priv_resource_pk');
      var labelEl = document.getElementById('privResourceLabel');
      if (pkEl)    pkEl.value           = resourcePk;
      if (labelEl) labelEl.textContent  = resourceLabel;
    }

    // Resource delete confirm modal
    if (e.target.id === 'deleteResourceModal') {
      var resourcePk = trigger.dataset.resourcePk || '';
      var resourceId = trigger.dataset.resourceId || '';
      var idEl       = document.getElementById('deleteResourceId');
      var confirmBtn = document.getElementById('deleteResourceConfirmBtn');
      if (idEl)       idEl.textContent = resourceId;
      if (confirmBtn) {
        confirmBtn.setAttribute('hx-delete', '/admin/access/resources/' + resourcePk);
        htmx.process(confirmBtn);
      }
    }
  });

  // ── Bootstrap modal cleanup after HTMX swaps ────────────────────────────
  // When HTMX replaces <main> while a modal is open (e.g. hx-target="main"),
  // the modal element is torn out of the DOM before Bootstrap's cleanup code
  // can run.  This leaves a .modal-backdrop div and modal-open on <body>,
  // making the page appear frozen.
  //
  // Two-pronged fix:
  //   1. closeModal — fired by HX-Trigger header from the server on success.
  //      Programmatically hides the modal so Bootstrap can clean up itself.
  //   2. htmx:afterSwap — force-removes any orphaned backdrop that slipped
  //      through (e.g. navigating away via sidebar while a modal is open).

  function cleanModalBackdrop() {
    document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
  }

  // htmx fires custom events from HX-Trigger on the <body>; listen on document.
  document.addEventListener('closeModal', function () {
    // Hide the currently open modal via Bootstrap so it fires hidden.bs.modal.
    // If the DOM was already swapped before this fires, fall back to brute-force.
    var open = document.querySelector('.modal.show');
    if (open) {
      var instance = bootstrap.Modal.getInstance(open);
      if (instance) {
        open.addEventListener('hidden.bs.modal', cleanModalBackdrop, { once: true });
        instance.hide();
        return;
      }
    }
    cleanModalBackdrop();
  });

  document.addEventListener('htmx:afterSwap', cleanModalBackdrop);

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

// Allow HTMX to swap 4xx/5xx responses (e.g. validation errors returning 422)
document.addEventListener('htmx:beforeSwap', function (evt) {
    if (evt.detail.xhr.status >= 400) {
        evt.detail.shouldSwap = true;
        evt.detail.isError    = false;
    }
});
