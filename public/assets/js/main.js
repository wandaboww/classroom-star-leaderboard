/**
 * Classroom Star — Main JavaScript
 * Sidebar toggle, toast notifications, utility helpers
 */

/* ── Sidebar Toggle ──────────────────────────────────────── */
(function initSidebar() {
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebar-overlay');
    const toggleBtn  = document.getElementById('sidebar-toggle');

    if (!sidebar || !overlay || !toggleBtn) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });
})();

/* ── Toast Notifications ─────────────────────────────────── */
const Toast = {
    container: null,

    init() {
        this.container = document.getElementById('toast-container');
    },

    show(message, type = 'info', duration = 3500) {
        if (!this.container) this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'status');

        const icon = this._icon(type);
        toast.innerHTML = `
            <span style="flex-shrink:0;">${icon}</span>
            <span style="flex:1;">${message}</span>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;padding:2px;opacity:0.7;display:flex;" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    success(msg, dur) { this.show(msg, 'success', dur); },
    error(msg, dur)   { this.show(msg, 'error', dur); },
    info(msg, dur)    { this.show(msg, 'info', dur); },
    gold(msg, dur)    { this.show(msg, 'gold', dur); },

    _icon(type) {
        const icons = {
            success: '✅',
            error:   '❌',
            info:    'ℹ️',
            gold:    '⭐',
        };
        return icons[type] || 'ℹ️';
    },
};

/* ── Confirm Dialog ──────────────────────────────────────── */
function confirmAction(message, onConfirm) {
    if (window.confirm(message)) {
        onConfirm();
    }
}

/* ── API Helper ──────────────────────────────────────────── */
const API = {
    async get(url) {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        return res.json();
    },

    async post(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(data),
        });
        return res.json();
    },

    async postForm(url, formData) {
        const res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        return res.json();
    },
};

/* ── Helpers ─────────────────────────────────────────────── */
function starsDisplay(count) {
    return '⭐'.repeat(Math.min(count, 5)) + (count > 5 ? ` ×${count}` : '');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function initials(name) {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

// CSS animation for fadeout
const style = document.createElement('style');
style.textContent = `@keyframes fadeOut { to { opacity: 0; transform: translateX(40px); } }`;
document.head.appendChild(style);

// Auto-init Toast
document.addEventListener('DOMContentLoaded', () => Toast.init());
