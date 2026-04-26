'use strict';

/* ============================================================
   Stores accordion — open/close
   ============================================================ */
const storesToggle = document.getElementById('stores-toggle');
const storesList   = document.getElementById('stores-list');

storesToggle?.addEventListener('click', function () {
    const isOpen = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', String(!isOpen));
    storesList?.classList.toggle('open', !isOpen);
});

/* ============================================================
   Store switcher — update active store context
   ============================================================ */
function setActiveStore(storeId, storeCity) {
    // Update sidebar store-info block
    const storeNameEl = document.querySelector('.sidebar-store-name');
    if (storeNameEl) {
        storeNameEl.textContent = `Store #${storeId} \u00b7 ${storeCity}`;
    }

    // Update topbar store label
    const topbarStoreEl = document.querySelector('.topbar-store');
    if (topbarStoreEl) {
        topbarStoreEl.textContent = `Store #${storeId} \u00b7 ${storeCity}`;
    }

    // Update page subtitle if present
    const pageSubtitle = document.querySelector('.page-subtitle');
    if (pageSubtitle) {
        const base = pageSubtitle.textContent.replace(/Store #\S+ .+/, '').trim();
        pageSubtitle.textContent = `${base ? base + ' \u2014 ' : ''}Store #${storeId} ${storeCity}`;
    }

    // Update active state in the store list
    document.querySelectorAll('.store-list-item').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.storeId === storeId);
    });

    // Update accordion toggle text to reflect selected store
    if (storesToggle) {
        for (const node of storesToggle.childNodes) {
            if (node.nodeType === Node.TEXT_NODE) {
                node.textContent = ` #${storeId} \u00b7 ${storeCity} `;
                break;
            }
        }
    }
}

document.querySelectorAll('.store-list-item').forEach(btn => {
    btn.addEventListener('click', function () {
        setActiveStore(this.dataset.storeId, this.dataset.storeCity);
        if (window.innerWidth < 1024) closeSidebar();
    });
});

/* ============================================================
   Sidebar drawer — mobile open/close
   ============================================================ */
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');
const menuBtn        = document.getElementById('menu-btn');
const sidebarCloseBtn = document.getElementById('sidebar-close-btn');

function openSidebar() {
    sidebar?.classList.add('open');
    sidebarOverlay?.classList.add('visible');
    document.body.style.overflow = 'hidden';
    menuBtn?.setAttribute('aria-expanded', 'true');
}

function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOverlay?.classList.remove('visible');
    document.body.style.overflow = '';
    menuBtn?.setAttribute('aria-expanded', 'false');
}

menuBtn?.addEventListener('click', openSidebar);
sidebarCloseBtn?.addEventListener('click', closeSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);

// Close drawer on nav link tap (mobile UX)
sidebar?.querySelectorAll('.sidebar-nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 1024) closeSidebar();
    });
});

/* ============================================================
   Bottom nav — active state
   ============================================================ */
document.querySelectorAll('.bottom-nav-item[data-page]').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.bottom-nav-item').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

/* ============================================================
   Toast dismiss
   ============================================================ */
document.querySelectorAll('.toast-dismiss').forEach(btn => {
    btn.addEventListener('click', function () {
        this.closest('.toast')?.remove();
    });
});

/* Auto-dismiss toasts after 4 s */
document.querySelectorAll('.toast').forEach(toast => {
    setTimeout(() => toast.remove(), 4000);
});

/* ============================================================
   Password visibility toggle
   ============================================================ */
document.querySelectorAll('[data-toggle-pw]').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = this.closest('.input-group')?.querySelector('input');
        const icon  = this.querySelector('i');
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon?.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon?.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
});

/* ============================================================
   Filter chips — single-select toggle
   ============================================================ */
document.querySelectorAll('.filter-chips').forEach(group => {
    group.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            group.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

/* ============================================================
   Progress bars — data-progress -> CSS width
   ============================================================ */
document.querySelectorAll('.manifest-progress-wrap[data-progress]').forEach(wrap => {
    const pct  = parseInt(wrap.dataset.progress, 10);
    const fill = wrap.querySelector('.manifest-progress-fill');
    if (fill && !isNaN(pct)) {
        fill.style.width = pct + '%';
    }
});

/* ============================================================
   Bottom nav — page navigation
   ============================================================ */
const pageMap = {
    dashboard:  'index.html',
    inventory:  'inventory.html',
    manifests:  'manifests.html',
};

document.querySelectorAll('.bottom-nav-item[data-page]').forEach(btn => {
    btn.addEventListener('click', function () {
        const page = this.dataset.page;
        if (pageMap[page]) {
            window.location.href = pageMap[page];
        }
    });
});

/* ============================================================
   Damage detail — status editor toggle
   ============================================================ */
const statusEditBtn   = document.getElementById('status-edit-btn');
const statusActions   = document.getElementById('status-actions');
const statusSaveBtn   = document.getElementById('status-save-btn');
const statusCancelBtn = document.getElementById('status-cancel-btn');
const statusGroup     = document.getElementById('status-toggle-group');

if (statusEditBtn && statusGroup) {
    statusEditBtn.addEventListener('click', function () {
        const editing = statusGroup.classList.toggle('editing');
        this.setAttribute('aria-pressed', String(editing));
        statusActions.hidden = !editing;
        this.innerHTML = editing
            ? '<i class="bi bi-x" aria-hidden="true"></i> Cancel'
            : '<i class="bi bi-pencil" aria-hidden="true"></i> Edit';
    });

    statusGroup.querySelectorAll('.status-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!statusGroup.classList.contains('editing')) return;
            const pressed = this.getAttribute('aria-pressed') === 'true';
            this.setAttribute('aria-pressed', String(!pressed));
            this.classList.toggle('active', !pressed);
        });
    });

    statusSaveBtn?.addEventListener('click', function () {
        statusGroup.classList.remove('editing');
        statusActions.hidden = true;
        statusEditBtn.setAttribute('aria-pressed', 'false');
        statusEditBtn.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Edit';
        // In real app: POST updated statuses via HTMX
    });

    statusCancelBtn?.addEventListener('click', function () {
        statusGroup.classList.remove('editing');
        statusActions.hidden = true;
        statusEditBtn.setAttribute('aria-pressed', 'false');
        statusEditBtn.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Edit';
    });
}

/* ============================================================
   Damage detail — notes editor toggle
   ============================================================ */
const notesEditBtn    = document.getElementById('notes-edit-btn');
const notesDisplay    = document.getElementById('damage-notes-display');
const notesEditor     = document.getElementById('damage-notes-editor');
const notesActions    = document.getElementById('notes-actions');
const notesSaveBtn    = document.getElementById('notes-save-btn');
const notesCancelBtn  = document.getElementById('notes-cancel-btn');

if (notesEditBtn && notesDisplay && notesEditor) {
    notesEditBtn.addEventListener('click', function () {
        notesDisplay.hidden  = true;
        notesEditor.hidden   = false;
        notesActions.hidden  = false;
        notesEditor.focus();
        this.setAttribute('aria-pressed', 'true');
        this.innerHTML = '';
    });

    notesSaveBtn?.addEventListener('click', function () {
        notesDisplay.textContent = notesEditor.value;
        notesDisplay.hidden  = false;
        notesEditor.hidden   = true;
        notesActions.hidden  = true;
        notesEditBtn.setAttribute('aria-pressed', 'false');
        notesEditBtn.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Edit';
    });

    notesCancelBtn?.addEventListener('click', function () {
        notesEditor.value    = notesDisplay.textContent;
        notesDisplay.hidden  = false;
        notesEditor.hidden   = true;
        notesActions.hidden  = true;
        notesEditBtn.setAttribute('aria-pressed', 'false');
        notesEditBtn.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Edit';
    });
}
