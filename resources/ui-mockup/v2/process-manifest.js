/**
 * process-manifest.js — v2
 * Handles the scan workflow for the Process Manifest page.
 * Hardware wedge scanners work natively via keyboard input.
 * Camera scanning uses ZXing-js (placeholder stub for mockup).
 */

(function () {
  'use strict';

  // ── State ──────────────────────────────────────────────────────────────────
  const state = {
    total: 44,
    scanned: 27,
    damaged: 2,
  };

  // ── DOM refs ──────────────────────────────────────────────────────────────
  const aoInput       = document.getElementById('pm-ao');
  const skuInput      = document.getElementById('pm-sku');
  const modelInput    = document.getElementById('pm-model');
  const okBtn         = document.getElementById('pm-ok-btn');
  const damageBtn     = document.getElementById('pm-damage-btn');
  const cameraBtn     = document.getElementById('pm-camera-btn');
  const progressFill  = document.getElementById('pm-progress-fill');
  const progressBar   = document.getElementById('pm-progress-bar');
  const scannedLabel  = document.getElementById('pm-scanned-label');
  const damagedLabel  = document.getElementById('pm-damaged-label');
  const countBadge    = document.getElementById('pm-count-badge');
  const processList   = document.getElementById('pm-processed-list');
  const toastCont     = document.getElementById('pm-toast-container');

  // ── Progress update ───────────────────────────────────────────────────────
  function updateProgress() {
    const pct = Math.round((state.scanned / state.total) * 100);
    progressFill.style.width = pct + '%';
    progressBar.setAttribute('aria-valuenow', pct);
    scannedLabel.textContent = `${state.scanned} of ${state.total} scanned`;
    damagedLabel.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${state.damaged} damaged`;
    countBadge.textContent = state.scanned;
  }

  // ── Toast ─────────────────────────────────────────────────────────────────
  function showToast(message, type) {
    const colour = type === 'damage' ? 'bg-danger' : 'bg-success';
    const icon   = type === 'damage' ? 'bi-exclamation-triangle' : 'bi-check-lg';
    const el = document.createElement('div');
    el.className = `toast align-items-center ${colour} border-0 text-white`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${icon}"></i> ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    toastCont.appendChild(el);
    const toast = new bootstrap.Toast(el, { delay: 3000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  // ── Add processed item to list ────────────────────────────────────────────
  function addProcessedItem(ao, sku, model, isDamaged) {
    const footer = processList.querySelector('.card-footer');
    const item = document.createElement('div');
    item.className = 'ims-product-item';
    if (isDamaged) {
      item.innerHTML = `
        <div class="ims-product-thumb bg-danger bg-opacity-10"><i class="bi bi-exclamation-triangle text-danger"></i></div>
        <div class="flex-grow-1">
          <div class="ims-product-name">${model || 'Unknown Product'}</div>
          <div class="ims-product-ids">SKU: ${sku || '—'} <span>AO#: ${ao}</span></div>
        </div>
        <a href="damage-detail.html" class="badge badge-ims-damaged text-decoration-none">Damaged</a>`;
    } else {
      item.innerHTML = `
        <div class="ims-product-thumb bg-success bg-opacity-10"><i class="bi bi-check-lg text-success"></i></div>
        <div class="flex-grow-1">
          <div class="ims-product-name">${model || 'Unknown Product'}</div>
          <div class="ims-product-ids">SKU: ${sku || '—'} <span>AO#: ${ao}</span></div>
        </div>
        <span class="badge bg-success">OK</span>`;
    }
    processList.insertBefore(item, footer);
  }

  // ── Clear inputs ──────────────────────────────────────────────────────────
  function clearInputs() {
    aoInput.value    = '';
    skuInput.value   = '';
    modelInput.value = '';
    aoInput.focus();
  }

  // ── Process a scan result ─────────────────────────────────────────────────
  function processScan(ao, sku, model, isDamaged) {
    if (!ao.trim()) return;
    state.scanned++;
    if (isDamaged) state.damaged++;
    updateProgress();
    addProcessedItem(ao.trim(), sku.trim(), model.trim(), isDamaged);
    showToast(
      isDamaged
        ? `Damaged: ${ao.trim()}`
        : `Scanned OK: ${ao.trim()}`,
      isDamaged ? 'damage' : 'ok',
    );
    clearInputs();
  }

  // ── Button handlers ───────────────────────────────────────────────────────
  okBtn.addEventListener('click', () => {
    processScan(aoInput.value, skuInput.value, modelInput.value, false);
  });

  damageBtn.addEventListener('click', () => {
    processScan(aoInput.value, skuInput.value, modelInput.value, true);
  });

  // ── Hardware wedge scanner: fires rapid keystrokes ending in Enter ─────────
  // AO# field is pre-focused; barcode scanner sends the barcode then Enter.
  aoInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      // Auto-submit OK if AO# filled (no damage assumed from wedge scan)
      if (aoInput.value.trim()) {
        processScan(aoInput.value, skuInput.value, modelInput.value, false);
      }
    }
  });

  // ── Camera button (ZXing-js stub for mockup) ──────────────────────────────
  cameraBtn.addEventListener('click', () => {
    // In production: initialise ZXing BrowserMultiFormatReader targeting a <video>
    // For the mockup, simulate a successful scan after 1.5 s
    cameraBtn.disabled = true;
    cameraBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scanning…';
    setTimeout(() => {
      // Simulate decoded Code 128B value → AO# pre-fill
      aoInput.value = 'A006523' + Math.floor(Math.random() * 900 + 100);
      skuInput.value = '';
      modelInput.value = '';
      cameraBtn.disabled = false;
      cameraBtn.innerHTML = '<i class="bi bi-camera"></i> Open Camera';
      aoInput.focus();
    }, 1500);
  });

  // ── Init ──────────────────────────────────────────────────────────────────
  updateProgress();
  aoInput.focus();
})();
