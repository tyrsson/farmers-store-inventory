function systemMessage(level, msg) {
    const template  = document.getElementById('toastTemplate');
    const clone     = template.content.firstElementChild.cloneNode(true);
    clone.classList.add(`text-bg-${level ?? 'info'}`);
    clone.querySelector('.toast-message').textContent = msg;
    const container = document.getElementById('systemMessage');
    const toast     = new bootstrap.Toast(clone, { autohide: true, delay: 4000 });
    clone.addEventListener('hidden.bs.toast', () => clone.remove());
    container.appendChild(clone);
    toast.show();
}

function showPendingToasts() {
    const container = document.getElementById('systemMessage');
    document.querySelectorAll('.ims-pending-toast').forEach(el => {
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { autohide: true, delay: 4000 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    });
    // Remove emptied temporary containers
    document.querySelectorAll('.toast-container:empty').forEach(el => el.remove());
}

// Show server-rendered toasts after HTMX settles
htmx.on('htmx:afterSettle', showPendingToasts);

// Show server-rendered toasts on initial full-page load
document.addEventListener('DOMContentLoaded', showPendingToasts);

// handle the server triggered systemMessage event
htmx.on("systemMessage", evt => systemMessage(evt.detail.level, evt.detail.message));

// Request Error handling
htmx.on("htmx:responseError", evt => systemMessage('danger', evt.detail.xhr.responseText));

