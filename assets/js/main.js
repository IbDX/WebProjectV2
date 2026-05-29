/* ============================================================
   Prototype Bank — Main JavaScript
   Handles: Modals | Toasts | Tabs | Permissions | Sidebar
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initModals();
    initTabs();
    initPermissionToggle('permFullAccess',   '.perm-checkbox');
    initPermissionToggle('editPermFullAccess','.edit-perm-cb');
    initAccountSearchSelects();
    initSearch();
    checkUrlToast();
});

/* ============================================================
   SCROLL PRESERVATION
   ============================================================ */
function restoreScrollPosition() {
    const scrollPos = sessionStorage.getItem('scrollPosition');
    if (scrollPos) {
        // Temporarily disable smooth scrolling to snap instantly
        document.documentElement.style.scrollBehavior = 'auto';
        window.scrollTo(0, parseInt(scrollPos, 10));
        document.documentElement.style.scrollBehavior = '';
        sessionStorage.removeItem('scrollPosition');
    }
}
// Run immediately to avoid initial paint jump
restoreScrollPosition();

window.addEventListener('beforeunload', () => {
    sessionStorage.setItem('scrollPosition', window.scrollY);
});

/* ============================================================
   SIDEBAR — highlight active nav item
   ============================================================ */
function initSidebar() {
    const current = window.location.pathname.split('/').pop() || 'dashboard.php';
    document.querySelectorAll('.nav-item[href]').forEach(a => {
        if (a.getAttribute('href').split('/').pop() === current) {
            a.classList.add('active');
        }
    });
}

/* ============================================================
   MODALS
   ============================================================ */
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        const first = el.querySelector('input:not([type=hidden]), select, textarea');
        if (first) first.focus();
    }, 220);
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('show');
    document.body.style.overflow = '';
}

function initModals() {
    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(ov => {
        ov.addEventListener('click', e => {
            if (e.target === ov) closeModal(ov.id);
        });
    });

    // [data-close-modal] buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });

    // Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
        }
    });
}

/* ============================================================
   CLIENT MODAL HELPERS
   ============================================================ */
function setupEditClient(data) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
    set('edit_account_number', data.account_number);
    set('edit_pin_code',       data.pin_code);
    set('edit_full_name',      data.full_name);
    set('edit_phone',          data.phone);
    set('edit_balance',        data.balance);
    // Show account number in modal header
    const hdEl = document.getElementById('editClientAccLabel');
    if (hdEl) hdEl.textContent = data.account_number;
    openModal('modalEditClient');
}

function setupDeleteClient(account, name) {
    const lbl = document.getElementById('delClientName');
    if (lbl) lbl.textContent = name + ' (' + account + ')';
    const inp = document.getElementById('delAccountNumber');
    if (inp) inp.value = account;
    openModal('modalDeleteClient');
}

/* ============================================================
   USER MODAL HELPERS
   ============================================================ */
function setupEditUser(data) {
    const inp = document.getElementById('edit_username_input');
    if (inp) inp.value = data.username;
    const lbl = document.getElementById('editUserLabel');
    if (lbl) lbl.textContent = data.username;

    const fullToggle = document.getElementById('editPermFullAccess');
    const cbs = document.querySelectorAll('.edit-perm-cb');

    const p = parseInt(data.permissions);

    if (p === -1) {
        if (fullToggle) fullToggle.checked = true;
        cbs.forEach(cb => { cb.checked = false; cb.disabled = true; });
    } else {
        if (fullToggle) fullToggle.checked = false;
        cbs.forEach(cb => {
            cb.disabled = false;
            cb.checked = (p & parseInt(cb.value)) !== 0;
        });
    }
    openModal('modalEditUser');
}

function setupDeleteUser(username) {
    const lbl = document.getElementById('delUserLabel');
    if (lbl) lbl.textContent = username;
    const inp = document.getElementById('delUsernameInput');
    if (inp) inp.value = username;
    openModal('modalDeleteUser');
}

/* ============================================================
   TOASTS
   ============================================================ */
function showToast(message, type = 'success', duration = 4200) {
    const c = document.getElementById('toastContainer');
    if (!c) return;
    const icons = { success: '✓', error: '✕', warning: '⚠' };
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML =
        `<span style="font-size:16px;flex-shrink:0;color:${type==='success'?'#16A34A':type==='error'?'#DC2626':'#D97706'}">${icons[type]||'•'}</span>` +
        `<span class="toast-msg">${message}</span>` +
        `<button class="toast-x" onclick="this.parentElement.remove()">×</button>`;
    c.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 350); }, duration);
}

function checkUrlToast() {
    const p = new URLSearchParams(window.location.search);
    const msg = p.get('msg');
    if (!msg) return;
    showToast(decodeURIComponent(msg), p.get('type') || 'success');
    const u = new URL(window.location.href);
    u.searchParams.delete('msg'); u.searchParams.delete('type');
    window.history.replaceState({}, '', u.toString());
}

/* ============================================================
   TABS  (dark card context)
   ============================================================ */
function initTabs() {
    document.querySelectorAll('.tab-list[data-target]').forEach(tl => {
        const group  = tl.dataset.target;
        const btns   = tl.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll(`[data-tab-group="${group}"] .tab-panel`);

        const activate = i => {
            btns.forEach((b,j)   => b.classList.toggle('active', i===j));
            panels.forEach((p,j) => p.style.display = i===j ? 'block' : 'none');
        };

        btns.forEach((btn, i) => btn.addEventListener('click', () => activate(i)));
        activate(0);
    });
}

/* ============================================================
   PERMISSION CHECKBOX TOGGLE
   (Full Access disables individual bit checkboxes)
   ============================================================ */
function initPermissionToggle(toggleId, cbSelector) {
    const toggle = document.getElementById(toggleId);
    if (!toggle) return;
    const cbs = document.querySelectorAll(cbSelector);

    const sync = () => {
        cbs.forEach(cb => {
            cb.disabled = toggle.checked;
            if (toggle.checked) cb.checked = false;
        });
    };

    toggle.addEventListener('change', sync);
    sync(); // initial state
}

/* ============================================================
   LIVE TABLE SEARCH
   ============================================================ */
function initSearch() {
    const inp = document.getElementById('tableSearch');
    const tbl = document.getElementById('mainTable');
    if (!inp || !tbl) return;

    inp.addEventListener('input', () => {
        const q = inp.value.toLowerCase();
        tbl.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

/* ============================================================
   BALANCE LOOKUP (Transactions page)
   ============================================================ */
function fetchBalance(accountInput, displayId) {
    const el   = document.getElementById(accountInput);
    const disp = document.getElementById(displayId);
    if (!el || !disp) return;

    el.addEventListener('blur', () => {
        const acc = el.value.trim();
        if (!acc) { disp.textContent = ''; return; }
        fetch(`api_balance.php?account=${encodeURIComponent(acc)}`)
            .then(r => r.json())
            .then(d => {
                if (d.balance !== undefined) {
                    disp.textContent = '$' + parseFloat(d.balance).toFixed(2);
                    disp.style.color = '#16A34A';
                } else {
                    disp.textContent = 'Account not found';
                    disp.style.color = '#DC2626';
                }
            })
            .catch(() => { disp.textContent = ''; });
    });
}

/* ============================================================
   CONFIRM helpers for inline forms (non-modal)
   ============================================================ */
function confirmAction(message) {
    return window.confirm(message);
}

/* ============================================================
   SEARCHABLE ACCOUNT COMBO  (.acc-combo)
   - Type to live-filter by account number or client name
   - Arrow-key navigation, Enter to select, Escape to close
   - Restores pre-selected value on validation-error re-render
   - Blocks form submit + shows red border if no account chosen
   ============================================================ */
function initAccountSearchSelects() {
    document.querySelectorAll('.acc-combo').forEach(combo => {
        const trigger     = combo.querySelector('.acc-combo-trigger');
        const searchInput = combo.querySelector('.acc-combo-search');
        const hiddenInput = combo.querySelector('input[type="hidden"]');
        const list        = combo.querySelector('.acc-combo-list');
        const allItems    = Array.from(combo.querySelectorAll('.acc-combo-item'));
        let highlighted   = -1;

        // ── Restore pre-selected value (PHP error re-render) ──
        if (hiddenInput.value) {
            const pre = allItems.find(i => i.dataset.value === hiddenInput.value);
            if (pre) {
                searchInput.value = pre.dataset.label;
                pre.classList.add('selected');
            }
        }

        // ── Open on focus ─────────────────────────────────────
        searchInput.addEventListener('focus', () => {
            combo.classList.add('open');
            filter('');
            searchInput.select();
        });

        // ── Live filter on type ───────────────────────────────
        searchInput.addEventListener('input', () => {
            combo.classList.add('open');
            hiddenInput.value = '';
            allItems.forEach(i => i.classList.remove('selected'));
            filter(searchInput.value);
        });

        // ── Click item (mousedown so blur doesn't fire first) ─
        list.addEventListener('mousedown', e => {
            const item = e.target.closest('.acc-combo-item');
            if (!item) return;
            e.preventDefault();
            pick(item);
        });

        // ── Keyboard navigation ───────────────────────────────
        searchInput.addEventListener('keydown', e => {
            const visible = allItems.filter(i => i.style.display !== 'none');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlighted = Math.min(highlighted + 1, visible.length - 1);
                highlight(visible);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlighted = Math.max(highlighted - 1, 0);
                highlight(visible);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlighted >= 0 && visible[highlighted]) pick(visible[highlighted]);
            } else if (e.key === 'Escape') {
                combo.classList.remove('open');
                highlighted = -1;
            }
        });

        // ── Close on blur ─────────────────────────────────────
        searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                combo.classList.remove('open');
                highlighted = -1;
                if (!hiddenInput.value) {
                    searchInput.value = '';
                    filter('');
                } else {
                    const sel = allItems.find(i => i.dataset.value === hiddenInput.value);
                    if (sel) searchInput.value = sel.dataset.label;
                }
            }, 200);
        });

        // ── Form submit guard ─────────────────────────────────
        const form = combo.closest('form');
        if (form) {
            form.addEventListener('submit', e => {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    trigger.classList.add('error');
                    setTimeout(() => trigger.classList.remove('error'), 3000);
                    combo.classList.add('open');
                    filter('');
                    searchInput.focus();
                }
            });
        }

        /* ── Helpers ── */
        function filter(query) {
            const q = query.trim().toLowerCase();
            let count = 0;
            highlighted = -1;
            allItems.forEach(item => {
                const match = !q || item.dataset.label.toLowerCase().includes(q);
                item.style.display = match ? '' : 'none';
                item.classList.remove('highlighted');
                if (match) count++;
            });
            let empty = list.querySelector('.acc-combo-empty');
            if (count === 0) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'acc-combo-empty';
                    empty.textContent = 'No matching clients';
                    list.appendChild(empty);
                }
            } else if (empty) {
                empty.remove();
            }
        }

        function pick(item) {
            hiddenInput.value = item.dataset.value;
            searchInput.value = item.dataset.label;
            allItems.forEach(i => i.classList.remove('selected', 'highlighted'));
            item.classList.add('selected');
            combo.classList.remove('open');
            highlighted = -1;
        }

        function highlight(visible) {
            visible.forEach((item, i) => item.classList.toggle('highlighted', i === highlighted));
            if (visible[highlighted]) visible[highlighted].scrollIntoView({ block: 'nearest' });
        }
    });
}
