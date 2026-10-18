// ============================================================
// LandTech 2026 — shared UI helpers (toast + confirm dialog).
// All user-facing strings are passed in by callers, already
// translated through window.__t.
// ============================================================

const TOAST_ICONS = {
    success:
        '<svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
    error:
        '<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>',
    info: '<svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>',
};

function region() {
    return document.getElementById('toast-region');
}

/**
 * Format a timestamp using the active UI locale.
 * @param {string|number|Date} ts
 * @returns {string}
 */
export function fmtTime(ts) {
    const locale = document.documentElement.lang || 'en';
    try {
        return new Date(ts).toLocaleString(locale === 'bn' ? 'bn-BD' : 'en-GB', {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    } catch {
        return new Date(ts).toLocaleString();
    }
}

/**
 * Show a transient status message.
 * @param {string} message already-translated text
 * @param {'success'|'error'|'info'} type
 * @param {{timeout?: number}} [options]
 */
export function toast(message, type = 'success', { timeout = 4200 } = {}) {
    const host = region();
    if (!host) {
        return;
    }

    const el = document.createElement('div');
    el.className = `toast toast-${TOAST_ICONS[type] ? type : 'info'}`;
    el.innerHTML = (TOAST_ICONS[type] || TOAST_ICONS.info) + '<span class="min-w-0 break-words"></span>';
    el.querySelector('span').textContent = message;
    host.appendChild(el);

    const remove = () => {
        el.remove();
    };

    if (timeout > 0) {
        window.setTimeout(remove, timeout);
    }

    el.addEventListener('click', remove);
}

/**
 * Accessible confirmation dialog built on <dialog>.
 * Falls back to window.confirm when <dialog> is unavailable.
 *
 * @param {{title: string, message: string, confirmLabel: string, cancelLabel: string, danger?: boolean}} options
 * @returns {Promise<boolean>}
 */
export function uiConfirm({ title, message, confirmLabel, cancelLabel, danger = false }) {
    const dialog = document.getElementById('ui-dialog');

    if (!dialog || typeof dialog.showModal !== 'function') {
        return Promise.resolve(window.confirm(message));
    }

    dialog.innerHTML = `
        <form method="dialog" class="p-5">
            <h2 id="ui-dialog-title" class="text-base font-semibold text-slate-900"></h2>
            <p id="ui-dialog-message" class="mt-2 text-sm leading-relaxed text-slate-600"></p>
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button value="cancel" class="btn-ghost btn-sm" data-dialog-cancel></button>
                <button value="confirm" class="btn-sm ${danger ? 'btn-danger' : 'btn-primary'}" data-dialog-confirm></button>
            </div>
        </form>`;

    dialog.setAttribute('aria-labelledby', 'ui-dialog-title');
    dialog.querySelector('#ui-dialog-title').textContent = title;
    dialog.querySelector('#ui-dialog-message').textContent = message;
    dialog.querySelector('[data-dialog-cancel]').textContent = cancelLabel;
    dialog.querySelector('[data-dialog-confirm]').textContent = confirmLabel;

    return new Promise((resolve) => {
        const onClose = () => {
            dialog.removeEventListener('close', onClose);
            resolve(dialog.returnValue === 'confirm');
        };
        dialog.addEventListener('close', onClose);
        dialog.returnValue = '';
        dialog.showModal();
    });
}
