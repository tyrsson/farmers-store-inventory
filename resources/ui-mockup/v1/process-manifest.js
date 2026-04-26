'use strict';

/* ============================================================
   Process Manifest — scan simulation & interaction
   ============================================================ */

// Products available to scan (unprocessed items from manifest 207-0415)
const SCAN_QUEUE = [
    {
        sku: '189181', ao: 'A006609835', name: 'TWIN BUNKIE FLAT',
        vendor: 'SLEEP INTERNATIONAL', vsn: '1952-03', maj: 'FBE', customer: null,
    },
    {
        sku: '189181', ao: 'A006609836', name: 'TWIN BUNKIE FLAT',
        vendor: 'SLEEP INTERNATIONAL', vsn: '1952-03', maj: 'FBE', customer: 'JAMIE JORDAN',
    },
    {
        sku: '195994', ao: 'A006500504', name: 'TABLE/2 COUNTER CHAIRS DROPLEAF',
        vendor: 'LINON HOME DECOR', vsn: '25C6018BLK VAIL', maj: 'FDN', customer: null,
    },
    {
        sku: '195999', ao: 'A006544572', name: 'KING/QUEEN RAILS',
        vendor: 'CROWN MARK INC', vsn: 'B9235-KQ-RAIL', maj: 'FBR', customer: 'JAMIE JORDAN',
    },
    {
        sku: '195999', ao: 'A006544588', name: 'KING/QUEEN RAILS',
        vendor: 'CROWN MARK INC', vsn: 'B9235-KQ-RAIL', maj: 'FBR', customer: 'KIMBERLY CHAPMAN',
    },
    {
        sku: '196000', ao: 'A006544694', name: 'NIGHTSTAND',
        vendor: 'CROWN MARK INC', vsn: 'B9235-2 CHARLIE BLACK', maj: 'FBR', customer: 'KIMBERLY CHAPMAN',
    },
    {
        sku: '196002', ao: 'A006492347', name: 'KING HEADBOARD/FOOTBOARD',
        vendor: 'CROWN MARK INC', vsn: 'B9235-K-HBFB', maj: 'FBR', customer: null,
    },
];

// State
let scannedCount   = 27;
let totalCount     = 44;
let damagedCount   = 2;
let queueIndex     = 0;
let currentProduct = null;

// DOM refs
const scanInput        = document.getElementById('scan-input');
const scanZone         = document.getElementById('scan-zone');
const scanResult       = document.getElementById('scan-result');
const processCount     = document.getElementById('process-count');
const processBarFill   = document.getElementById('process-bar-fill');
const processedSectionCount = document.getElementById('processed-section-count');
const damageCntLabel   = document.getElementById('damage-count-label');
const processedList    = document.getElementById('processed-list');
const completeBtn      = document.getElementById('complete-manifest-btn');
const completeRemaining = document.querySelector('.complete-manifest-remaining');

const btnGood          = document.getElementById('btn-good');
const btnDamaged       = document.getElementById('btn-damaged');
const damageQuickEntry = document.getElementById('damage-quick-entry');
const confirmBtn       = document.getElementById('confirm-scan');
const rescanBtn        = document.getElementById('rescan-btn');
const dismissBtn       = document.getElementById('scan-result-dismiss');

// Init progress bar
initProgressBar();
scanInput?.focus();

/* ── Scan input ─────────────────────────────────────────── */
scanInput?.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    triggerScan(this.value.trim());
});

function triggerScan(query) {
    // If queue exhausted loop back for demo
    if (queueIndex >= SCAN_QUEUE.length) queueIndex = 0;

    // If user typed something, try to find it; otherwise advance queue
    let product = null;
    if (query) {
        product = SCAN_QUEUE.find(
            p => p.ao.toLowerCase().includes(query.toLowerCase())
              || p.sku.includes(query)
        ) || SCAN_QUEUE[queueIndex];
    } else {
        product = SCAN_QUEUE[queueIndex];
    }

    currentProduct = product;
    queueIndex++;
    showScanResult(product);
    if (scanInput) scanInput.value = '';
}

/* ── Show result card ───────────────────────────────────── */
function showScanResult(product) {
    document.getElementById('result-name').textContent    = product.name;
    document.getElementById('result-sku').textContent     = 'SKU: ' + product.sku;
    document.getElementById('result-ao').textContent      = 'AO#: ' + product.ao;
    document.getElementById('result-vendor').textContent  = product.vendor;
    document.getElementById('result-vsn').textContent     = product.vsn;
    document.getElementById('result-maj').textContent     = 'Maj: ' + product.maj;

    const custEl   = document.getElementById('result-customer');
    const custName = document.getElementById('result-customer-name');
    if (product.customer) {
        custName.textContent = product.customer;
        custEl.hidden = false;
    } else {
        custEl.hidden = true;
    }

    // Reset status toggles
    setStatus('good');

    scanResult.hidden = false;
    scanZone.classList.add('scan-zone-compact');
    confirmBtn?.focus();
}

/* ── Status toggle ──────────────────────────────────────── */
btnGood?.addEventListener('click', () => setStatus('good'));
btnDamaged?.addEventListener('click', () => setStatus('damaged'));

function setStatus(status) {
    const good    = status === 'good';
    btnGood?.classList.toggle('active', good);
    btnDamaged?.classList.toggle('active', !good);
    btnGood?.setAttribute('aria-pressed', String(good));
    btnDamaged?.setAttribute('aria-pressed', String(!good));
    if (damageQuickEntry) damageQuickEntry.hidden = good;
    const textarea = document.getElementById('damage-quick-note');
    if (good && textarea) textarea.value = '';
}

/* ── Confirm & Next ─────────────────────────────────────── */
confirmBtn?.addEventListener('click', confirmScan);

function confirmScan() {
    if (!currentProduct) return;

    const isDamaged = btnDamaged?.getAttribute('aria-pressed') === 'true';
    const note      = document.getElementById('damage-quick-note')?.value.trim() || '';

    // Add to top of processed list
    addToProcessedList(currentProduct, isDamaged, note);

    // Update counters
    scannedCount++;
    if (isDamaged) damagedCount++;

    updateProgress();

    // Reset UI
    scanResult.hidden = true;
    scanZone.classList.remove('scan-zone-compact');
    currentProduct = null;
    scanInput?.focus();
}

/* ── Re-scan / dismiss ──────────────────────────────────── */
rescanBtn?.addEventListener('click', () => {
    scanResult.hidden = true;
    scanZone.classList.remove('scan-zone-compact');
    currentProduct = null;
    scanInput?.focus();
});

dismissBtn?.addEventListener('click', () => {
    scanResult.hidden = true;
    scanZone.classList.remove('scan-zone-compact');
    currentProduct = null;
    scanInput?.focus();
});

/* ── Add processed item to DOM ──────────────────────────── */
function addToProcessedList(product, isDamaged, note) {
    const item = document.createElement('div');
    item.className = 'processed-item' + (isDamaged ? ' processed-item-damaged processed-item-new' : ' processed-item-new');
    item.setAttribute('role', 'listitem');

    const dotClass = isDamaged ? 'processed-item-dot-damaged' : 'processed-item-dot-good';
    const badgeClass = isDamaged ? 'badge-damaged' : 'badge-floor';
    const badgeText  = isDamaged ? 'Damaged' : 'Good';
    const noteHtml   = (isDamaged && note)
        ? `<span class="processed-item-note">${escHtml(note)}</span>`
        : '';

    item.innerHTML = `
        <span class="processed-item-dot ${dotClass}" aria-label="${badgeText}"></span>
        <div class="processed-item-info">
            <span class="processed-item-name">${escHtml(product.name)}</span>
            <span class="processed-item-ids">SKU: ${escHtml(product.sku)} &nbsp;·&nbsp; AO#: ${escHtml(product.ao)}</span>
            ${noteHtml}
        </div>
        <span class="badge ${badgeClass} badge-xs">${badgeText}</span>
    `;

    // Insert before load-more
    const loadMore = document.querySelector('.processed-load-more');
    processedList?.insertBefore(item, loadMore);

    // Remove animation class after it plays
    requestAnimationFrame(() => {
        requestAnimationFrame(() => item.classList.remove('processed-item-new'));
    });
}

/* ── Progress update ────────────────────────────────────── */
function updateProgress() {
    const pct = Math.round((scannedCount / totalCount) * 100);
    const remaining = totalCount - scannedCount;

    if (processCount)       processCount.textContent = `${scannedCount} / ${totalCount} scanned`;
    if (damageCntLabel)     damageCntLabel.textContent = `${damagedCount} damaged`;
    if (processedSectionCount) processedSectionCount.textContent = String(scannedCount);

    if (processBarFill) {
        processBarFill.style.width = pct + '%';
        processBarFill.closest('[role="progressbar"]')?.setAttribute('aria-valuenow', String(pct));
    }

    if (completeRemaining) completeRemaining.textContent = remaining > 0 ? `(${remaining} remaining)` : '';

    if (completeBtn) {
        if (scannedCount >= totalCount) {
            completeBtn.disabled = false;
            completeBtn.removeAttribute('aria-disabled');
        }
    }
}

function initProgressBar() {
    document.querySelectorAll('.manifest-progress-fill[data-progress]').forEach(fill => {
        const pct = parseInt(fill.dataset.progress, 10);
        if (!isNaN(pct)) fill.style.width = pct + '%';
    });
}

/* ── Escape HTML ────────────────────────────────────────── */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Complete manifest ──────────────────────────────────── */
completeBtn?.addEventListener('click', function () {
    if (scannedCount < totalCount) return;
    // In real app: POST completion via HTMX
    this.innerHTML = '<i class="bi bi-check2-circle" aria-hidden="true"></i> Manifest Complete!';
    this.classList.add('btn-success-state');
    this.disabled = true;
    scanInput?.setAttribute('disabled', '');
    document.querySelector('.complete-manifest-hint').textContent =
        'Manifest 207-0415 has been completed and inventory updated.';
});
