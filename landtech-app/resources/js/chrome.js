// ============================================================
// LandTech 2026 — shared application chrome behaviour
// Sidebar collapse (desktop), navigation drawer (mobile),
// details-based dropdown menus. No framework dependency.
// ============================================================

const body = document.body;
const COLLAPSE_KEY = 'landtech2026.ui.sidebarCollapsed';
const mqDesktop = window.matchMedia('(min-width: 1024px)');

const sidebar = document.getElementById('app-sidebar');
const backdrop = document.getElementById('nav-backdrop');
const collapseToggle = document.getElementById('sidebar-toggle');
const mobileToggle = document.getElementById('mobile-nav-toggle');
const mobileClose = document.getElementById('mobile-nav-close');

function isDesktop() {
    return mqDesktop.matches;
}

// ---- Desktop: collapsible sidebar ---------------------------
function applyCollapsed(collapsed, { persist = true } = {}) {
    if (!sidebar) return;
    body.classList.toggle('sidebar-collapsed', collapsed);
    if (collapseToggle) {
        collapseToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }
    if (persist) {
        try {
            localStorage.setItem(COLLAPSE_KEY, collapsed ? '1' : '0');
        } catch {
            /* storage unavailable (private mode) — collapse still works for this page */
        }
    }
}

if (sidebar && collapseToggle) {
    let stored = null;
    try {
        stored = localStorage.getItem(COLLAPSE_KEY);
    } catch {
        stored = null;
    }
    applyCollapsed(stored === '1', { persist: false });

    collapseToggle.addEventListener('click', () => {
        applyCollapsed(!body.classList.contains('sidebar-collapsed'));
    });

    // Re-entering desktop keeps the stored preference; entering mobile always
    // clears the collapsed width so labels are readable inside the drawer.
    mqDesktop.addEventListener('change', (e) => {
        if (e.matches) {
            let storedNow = '0';
            try {
                storedNow = localStorage.getItem(COLLAPSE_KEY) ?? '0';
            } catch {
                storedNow = '0';
            }
            applyCollapsed(storedNow === '1', { persist: false });
            closeDrawer({ restoreFocus: false });
        } else {
            applyCollapsed(false, { persist: false });
        }
    });
}

// ---- Mobile: navigation drawer ------------------------------
function drawerIsOpen() {
    return body.classList.contains('nav-open');
}

function openDrawer() {
    body.classList.add('nav-open');
    if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'true');
    if (sidebar) {
        const focusable = sidebar.querySelector('a, button, select, [tabindex]:not([tabindex="-1"])');
        if (focusable) focusable.focus({ preventScroll: true });
    }
}

function closeDrawer({ restoreFocus = true } = {}) {
    if (!drawerIsOpen()) return;
    body.classList.remove('nav-open');
    if (mobileToggle) {
        mobileToggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus) mobileToggle.focus({ preventScroll: true });
    }
}

if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        if (drawerIsOpen()) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });
}

if (mobileClose) {
    mobileClose.addEventListener('click', () => closeDrawer());
}

if (backdrop) {
    backdrop.addEventListener('click', () => closeDrawer());
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawerIsOpen()) {
        closeDrawer();
    }
});

// ---- Dropdown menus (<details>) ------------------------------
const menus = Array.from(document.querySelectorAll('details[data-menu]'));

function closeMenus(except = null) {
    menus.forEach((menu) => {
        if (menu !== except) menu.open = false;
    });
}

menus.forEach((menu) => {
    menu.addEventListener('toggle', () => {
        if (menu.open) closeMenus(menu);
    });
});

document.addEventListener('click', (e) => {
    if (!menus.some((menu) => menu.contains(e.target))) closeMenus();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const open = menus.find((menu) => menu.open);
        if (open) {
            open.open = false;
            const summary = open.querySelector('summary');
            if (summary) summary.focus();
        }
    }
});
