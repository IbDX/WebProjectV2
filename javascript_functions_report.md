# JavaScript Functions Report — Prototype Bank Project

> A complete, beginner-friendly guide to every JavaScript function defined in this project.
> By the end of this document you will understand **what** each function does, **how** it works step by step, **what Web APIs it uses**, and **where** it is called from.

---

## 📌 What is JavaScript doing in this project?

The PHP files build the HTML structure and send data from the database. JavaScript then runs in the **browser** after the page loads to handle everything the user sees and interacts with:

- Opening and closing popup modals
- Showing notification toasts
- Switching between tabs
- Live-searching a table without a page reload
- The searchable account dropdown (combo box)
- Remembering the scroll position across page navigations
- Checking and clearing URL parameters after showing a message

All of the shared JavaScript lives in one file:
**[assets/js/main.js](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js)**

One additional function (`doLookup`) lives inline inside **[transactions.php](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L446-L462)**.

---

## 🗂️ Functions Index

| # | Function | File | Purpose |
|---|---|---|---|
| 1 | [`DOMContentLoaded` listener](#1-domcontentloaded-bootstrap-listener) | main.js:6 | Bootstrap — runs all init functions when page is ready |
| 2 | [`restoreScrollPosition()`](#2-restorescrollposition) | main.js:20 | Snap the page back to where the user was before navigation |
| 3 | [`beforeunload` listener](#3-beforeunload-listener) | main.js:33 | Save scroll position just before leaving the page |
| 4 | [`initSidebar()`](#4-initsidebar) | main.js:40 | Highlight the active navigation link in the sidebar |
| 5 | [`openModal(id)`](#5-openmodalid) | main.js:52 | Show a modal dialog by its ID |
| 6 | [`closeModal(id)`](#6-closemodalid) | main.js:63 | Hide a modal dialog by its ID |
| 7 | [`initModals()`](#7-initmodals) | main.js:70 | Wire up all modal close triggers (backdrop, button, Escape) |
| 8 | [`setupEditClient(data)`](#8-setupeditclientdata) | main.js:94 | Populate and open the Edit Client modal |
| 9 | [`setupDeleteClient(account, name)`](#9-setupdeleteclientaccount-name) | main.js:107 | Populate and open the Delete Client confirmation modal |
| 10 | [`setupEditUser(data)`](#10-setupedituserdata) | main.js:118 | Populate and open the Edit User modal with permission checkboxes |
| 11 | [`setupDeleteUser(username)`](#11-setupdeleteuserusername) | main.js:142 | Populate and open the Delete User confirmation modal |
| 12 | [`showToast(message, type, duration)`](#12-showtoastmessage-type-duration) | main.js:153 | Display a temporary notification banner |
| 13 | [`checkUrlToast()`](#13-checkurltoast) | main.js:168 | Read `?msg=` from the URL and show it as a toast, then clean the URL |
| 14 | [`initTabs()`](#14-inittabs) | main.js:181 | Turn tab button groups into working tab panels |
| 15 | [`initPermissionToggle(toggleId, cbSelector)`](#15-initpermissiontoggletoggleid-cbselector) | main.js:201 | Disable individual permission checkboxes when "Full Access" is checked |
| 16 | [`initSearch()`](#16-initsearch) | main.js:220 | Filter table rows live as the user types |
| 17 | [`fetchBalance(accountInput, displayId)`](#17-fetchbalanceaccountinput-displayid) | main.js:236 | Fetch account balance from the server when user leaves an input |
| 18 | [`confirmAction(message)`](#18-confirmactionmessage) | main.js:262 | Show a browser confirm dialog and return the result |
| 19 | [`initAccountSearchSelects()`](#19-initaccountsearchselects) | main.js:273 | Build fully interactive searchable account dropdowns |
| 20 | [`doLookup()`](#20-dolookup) | transactions.php:446 | Fetch and display an account balance via the "Look Up" button |
| — | [Inner helper: `filter(query)`](#inner-helper-filterquery) | main.js:365 | Filter visible items inside a combo dropdown |
| — | [Inner helper: `pick(item)`](#inner-helper-pickitem) | main.js:388 | Select an item in the combo and update the hidden input |
| — | [Inner helper: `highlight(visible)`](#inner-helper-highlightvisible) | main.js:397 | Move keyboard highlight to the correct list item |

---

## 1. `DOMContentLoaded` Bootstrap Listener

### 📖 What is it?
This is **not a function you define** — it is a special browser event. When the browser finishes parsing the HTML (but before images and stylesheets fully load), it fires the `DOMContentLoaded` event. We listen for it and run all our initialization functions at exactly the right moment — when the elements are in the page but before the user can interact with anything.

### 📂 Location
[main.js — Lines 6–15](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L6-L15)
```javascript
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initModals();
    initTabs();
    initPermissionToggle('permFullAccess',    '.perm-checkbox');
    initPermissionToggle('editPermFullAccess', '.edit-perm-cb');
    initAccountSearchSelects();
    initSearch();
    checkUrlToast();
});
```

### 💡 Why not just put code directly in a `<script>` tag at the top?
If JavaScript tries to access a DOM element before the browser has parsed it, it gets `null`. By waiting for `DOMContentLoaded`, we guarantee every element exists before we touch it.

### 🌐 Web API Used
- `document.addEventListener(event, callback)` — attaches a function to run when a specific event fires on an element.

---

## 2. `restoreScrollPosition()`

### 📖 What is it?
When a PHP form is submitted (for example adding a client), the browser does a full page reload. Without this function, the page would jump back to the very top, disorienting the user. This function reads the saved scroll position from `sessionStorage` and instantly moves the page back to where the user was.

### 📂 Location
[main.js — Lines 20–31](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L20-L31)
```javascript
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
// Run immediately — before DOMContentLoaded to avoid paint jump
restoreScrollPosition();
```

### 🔄 Step-by-step
1. **`sessionStorage.getItem('scrollPosition')`** — reads the saved scroll Y value (in pixels from top). Returns `null` if nothing was saved.
2. **`if (scrollPos)`** — only proceed if a value exists.
3. **`scrollBehavior = 'auto'`** — temporarily turns off the CSS smooth scroll animation so the snap is instantaneous.
4. **`window.scrollTo(0, parseInt(scrollPos, 10))`** — jumps to X=0, Y=savedValue pixels.
5. **`scrollBehavior = ''`** — restores smooth scrolling for normal user interactions.
6. **`sessionStorage.removeItem('scrollPosition')`** — cleans up so the value isn't reused on the next visit.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `sessionStorage.getItem(key)` | Reads a value stored per browser tab (cleared when tab closes) |
| `window.scrollTo(x, y)` | Scrolls the page to exact pixel coordinates |
| `sessionStorage.removeItem(key)` | Deletes a stored value |
| `document.documentElement.style` | Access the `<html>` element's inline styles |
| `parseInt(string, radix)` | Converts a string like `"432"` to the integer `432` (base 10) |

### 💡 Why call it immediately (not inside DOMContentLoaded)?
It is called at line 31, before `DOMContentLoaded` fires, so the scroll snap happens at the very first paint — preventing a visible jump from top to middle.

---

## 3. `beforeunload` Listener

### 📖 What is it?
This is an event listener (not a named function) that fires **just before the user leaves the page** — whether by clicking a link, submitting a form, or closing the tab. Its job is to save the current scroll position so `restoreScrollPosition()` can retrieve it after the reload.

### 📂 Location
[main.js — Lines 33–35](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L33-L35)
```javascript
window.addEventListener('beforeunload', () => {
    sessionStorage.setItem('scrollPosition', window.scrollY);
});
```

### 🔄 Step-by-step
1. `window.scrollY` — reads the current vertical scroll position in pixels.
2. `sessionStorage.setItem('scrollPosition', ...)` — saves that number as a string under the key `'scrollPosition'`.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `window.addEventListener('beforeunload', fn)` | Runs `fn` just before the page unloads |
| `window.scrollY` | Read-only property: pixels scrolled from the top of the page |
| `sessionStorage.setItem(key, value)` | Saves a string value per-tab, persists across page reloads in the same tab |

---

## 4. `initSidebar()`

### 📖 What is it?
The sidebar shows navigation links to every page (Dashboard, Clients, Transactions, Users). This function adds an `active` CSS class to the link that matches the **current page**, so the correct item is visually highlighted.

### 📂 Location
[main.js — Lines 40–47](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L40-L47)
```javascript
function initSidebar() {
    const current = window.location.pathname.split('/').pop() || 'dashboard.php';
    document.querySelectorAll('.nav-item[href]').forEach(a => {
        if (a.getAttribute('href').split('/').pop() === current) {
            a.classList.add('active');
        }
    });
}
```

### 🔄 Step-by-step
1. **`window.location.pathname`** — gets the URL path, e.g. `/WebProjectV2-1/clients.php`.
2. **`.split('/').pop()`** — splits by `/` into an array and takes the last part: `"clients.php"`.
3. **`|| 'dashboard.php'`** — if the filename is empty (root URL), default to dashboard.
4. **`querySelectorAll('.nav-item[href]')`** — selects every `<a>` with class `.nav-item` that has an `href` attribute.
5. **`.forEach(a => ...)`** — loops over each link.
6. **`a.getAttribute('href').split('/').pop()`** — extracts the filename from the link's href.
7. **`classList.add('active')`** — if it matches the current page, add the active CSS class.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `window.location.pathname` | The path portion of the current URL |
| `String.split(separator)` | Splits a string into an array of parts |
| `Array.pop()` | Returns and removes the last element of an array |
| `document.querySelectorAll(selector)` | Returns all elements matching a CSS selector |
| `NodeList.forEach(callback)` | Loops over all matched elements |
| `element.getAttribute(name)` | Reads the value of an HTML attribute |
| `element.classList.add(className)` | Adds a CSS class to an element |

---

## 5. `openModal(id)`

### 📖 What is it?
Shows a modal dialog (popup) by its HTML `id`. It adds a CSS class to make it visible, locks the page scroll so the background doesn't move, and automatically focuses the first input inside the modal after the animation plays.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `id` | string | The `id` attribute of the `.modal-overlay` element to show |

### 📂 Location
[main.js — Lines 52–61](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L52-L61)
```javascript
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;                          // Safety: do nothing if not found
    el.classList.add('show');                 // CSS transition triggers
    document.body.style.overflow = 'hidden';  // Lock background scroll
    setTimeout(() => {
        const first = el.querySelector('input:not([type=hidden]), select, textarea');
        if (first) first.focus();             // Auto-focus first field
    }, 220);                                  // Wait for animation (220ms)
}
```

### 🔄 Step-by-step
1. **`getElementById(id)`** — finds the modal element. If not found, exits immediately.
2. **`classList.add('show')`** — the CSS `.modal-overlay.show` rule makes it visible with a fade animation.
3. **`body.style.overflow = 'hidden'`** — prevents the page behind from scrolling while the modal is open.
4. **`setTimeout(() => ..., 220)`** — waits 220ms for the CSS animation to finish before focusing.
5. **`querySelector('input:not([type=hidden]), select, textarea')`** — finds the first visible input field inside the modal.
6. **`first.focus()`** — moves keyboard focus to that field so the user can type immediately.

### 📞 Called from
- `setupEditClient()`, `setupDeleteClient()`, `setupEditUser()`, `setupDeleteUser()`
- Inline PHP `onclick="openModal('modalAddClient')"` buttons

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `document.getElementById(id)` | Finds a single element by its `id` attribute |
| `element.classList.add(cls)` | Adds a CSS class |
| `element.style.overflow` | Sets the CSS `overflow` property inline |
| `setTimeout(fn, ms)` | Calls `fn` after `ms` milliseconds |
| `element.querySelector(selector)` | Finds the first matching child element |
| `element.focus()` | Moves keyboard focus to the element |

---

## 6. `closeModal(id)`

### 📖 What is it?
The opposite of `openModal()`. Hides a modal and restores the page scroll.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `id` | string | The `id` of the modal to close |

### 📂 Location
[main.js — Lines 63–68](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L63-L68)
```javascript
function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('show');      // CSS fade-out animation triggers
    document.body.style.overflow = '';  // Unlock background scroll
}
```

### 🔄 Step-by-step
1. Finds the element — exits if not found.
2. **`classList.remove('show')`** — removes the visible class, triggering the CSS fade-out.
3. **`body.style.overflow = ''`** — removes the inline overflow style, restoring normal scrolling.

### 📞 Called from
- `initModals()` — backdrop click, `[data-close-modal]` buttons, Escape key
- `setupEditClient`, `setupDeleteClient`, `setupEditUser`, `setupDeleteUser` (indirectly through `openModal` which doesn't close)

---

## 7. `initModals()`

### 📖 What is it?
Sets up **three ways to close any modal** across the entire page:
1. Clicking the dark backdrop behind the modal
2. Clicking any button with the `data-close-modal` HTML attribute
3. Pressing the `Escape` key on the keyboard

This function is called once on page load and wires up every modal automatically.

### 📂 Location
[main.js — Lines 70–89](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L70-L89)
```javascript
function initModals() {
    // 1. Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(ov => {
        ov.addEventListener('click', e => {
            if (e.target === ov) closeModal(ov.id); // Only if clicked the overlay itself
        });
    });

    // 2. [data-close-modal] attribute buttons (Cancel, X buttons)
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
    });

    // 3. Escape key — closes all open modals
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
        }
    });
}
```

### 🔄 How the backdrop click works
```
User clicks anywhere
    ↓
e.target = the actual element clicked
    ↓
e.target === ov?
    ├── YES (clicked the dark backdrop) → closeModal()
    └── NO  (clicked the white modal box) → do nothing
```

This is called **event delegation** — one listener handles clicks from many places.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `element.addEventListener('click', fn)` | Calls `fn` when element is clicked |
| `event.target` | The exact element the user actually clicked |
| `element.dataset.closeModal` | Reads the `data-close-modal="..."` HTML attribute value |
| `document.addEventListener('keydown', fn)` | Listens for any key press anywhere on the page |
| `event.key` | The name of the key pressed (e.g. `"Escape"`, `"Enter"`) |

---

## 8. `setupEditClient(data)`

### 📖 What is it?
When the user clicks the ✏️ edit button on a client row, PHP outputs that client's data as a JSON object directly into the `onclick` attribute. This function receives that object, fills in all the form fields in the Edit Client modal, and then opens it.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `data` | object | A JSON object with keys: `account_number`, `pin_code`, `full_name`, `phone`, `balance` |

### 📂 Location
[main.js — Lines 94–105](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L94-L105)
```javascript
function setupEditClient(data) {
    // Arrow function shorthand: find element by id, set its value
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val ?? '';  // ?? '' means: use '' if val is null/undefined
    };
    set('edit_account_number', data.account_number);
    set('edit_pin_code',       data.pin_code);
    set('edit_full_name',      data.full_name);
    set('edit_phone',          data.phone);
    set('edit_balance',        data.balance);

    // Put account number in the modal header title
    const hdEl = document.getElementById('editClientAccLabel');
    if (hdEl) hdEl.textContent = data.account_number;

    openModal('modalEditClient');
}
```

### 🔄 How the PHP passes data to this function
In `clients.php`, the edit button looks like:
```php
onclick='setupEditClient(<?= json_encode([
    "account_number" => $c["account_number"],
    "full_name"      => $c["full_name"],
    ...
]) ?>)'
```
PHP converts the array to JSON (`{"account_number":"ACC-001","full_name":"John"}`) and JavaScript receives it as a live object.

### 💡 The `??` (Nullish Coalescing) Operator
```javascript
val ?? ''
// Returns val if val is NOT null or undefined
// Returns '' if val IS null or undefined
// Prevents "undefined" or "null" from appearing in input fields
```

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `element.value = x` | Sets the value of an input field |
| `element.textContent = x` | Sets the visible text of any element |

---

## 9. `setupDeleteClient(account, name)`

### 📖 What is it?
When the user clicks the 🗑️ delete button on a client, this function fills in the Delete Confirmation modal with the client's name and account number, then opens it. This lets the user see exactly what they are about to delete before confirming.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `account` | string | The account number of the client to delete |
| `name` | string | The full name of the client to delete |

### 📂 Location
[main.js — Lines 107–113](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L107-L113)
```javascript
function setupDeleteClient(account, name) {
    const lbl = document.getElementById('delClientName');
    if (lbl) lbl.textContent = name + ' (' + account + ')';
    // e.g. "John Smith (ACC-20001)"

    const inp = document.getElementById('delAccountNumber');
    if (inp) inp.value = account; // Hidden input → sent with the form on submit

    openModal('modalDeleteClient');
}
```

### 💡 Why a hidden input?
The delete action is performed by a PHP form POST. The hidden `<input type="hidden" id="delAccountNumber">` holds the account number value. When the "Delete Client" button is clicked, the form submits with that hidden value — PHP receives it in `$_POST['account_number']`.

---

## 10. `setupEditUser(data)`

### 📖 What is it?
More complex than `setupEditClient`. Besides filling in the username field, it also configures the permission checkboxes. If the user has Full Access (`permissions = -1`), all individual checkboxes are checked and disabled. Otherwise, each checkbox is checked/unchecked based on the bitmask value.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `data` | object | Object with keys: `username`, `permissions` (integer bitmask) |

### 📂 Location
[main.js — Lines 118–140](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L118-L140)
```javascript
function setupEditUser(data) {
    const inp = document.getElementById('edit_username_input');
    if (inp) inp.value = data.username;

    const lbl = document.getElementById('editUserLabel');
    if (lbl) lbl.textContent = data.username;

    const fullToggle = document.getElementById('editPermFullAccess');
    const cbs = document.querySelectorAll('.edit-perm-cb');

    const p = parseInt(data.permissions);  // Convert to integer

    if (p === -1) {
        // Full Access: tick the Full toggle, disable & uncheck all bits
        if (fullToggle) fullToggle.checked = true;
        cbs.forEach(cb => { cb.checked = false; cb.disabled = true; });
    } else {
        // Custom permissions: use bitwise AND to check each bit
        if (fullToggle) fullToggle.checked = false;
        cbs.forEach(cb => {
            cb.disabled = false;
            cb.checked = (p & parseInt(cb.value)) !== 0;
            //               ↑ Bitwise AND: if this bit is set in p, check the box
        });
    }
    openModal('modalEditUser');
}
```

### 💡 Understanding the Bitwise AND check
```javascript
p = 33  // binary: 100001  (PERM_CLIENT_LIST=1 + PERM_MANAGE_USERS=32)

cb.value = "1"   → p & 1  = 1   (non-zero) → checked = true  ✅
cb.value = "2"   → p & 2  = 0   (zero)     → checked = false ❌
cb.value = "32"  → p & 32 = 32  (non-zero) → checked = true  ✅
cb.value = "64"  → p & 64 = 0   (zero)     → checked = false ❌
```
This is how the permission system stores multiple yes/no flags in a single integer.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `parseInt(value)` | Converts a string to an integer |
| `checkbox.checked` | Boolean property: whether the checkbox is ticked |
| `checkbox.disabled` | Boolean property: whether the checkbox is greyed out |
| `&` (Bitwise AND) | Tests if a specific bit is set in an integer |

---

## 11. `setupDeleteUser(username)`

### 📖 What is it?
Fills in the Delete User confirmation modal with the username and opens it.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `username` | string | The username of the user to delete |

### 📂 Location
[main.js — Lines 142–148](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L142-L148)
```javascript
function setupDeleteUser(username) {
    const lbl = document.getElementById('delUserLabel');
    if (lbl) lbl.textContent = username; // Show username in the dialog

    const inp = document.getElementById('delUsernameInput');
    if (inp) inp.value = username; // Store in hidden input for form POST

    openModal('modalDeleteUser');
}
```

---

## 12. `showToast(message, type, duration)`

### 📖 What is it?
Creates and displays a temporary **notification banner** (called a "toast") at the bottom of the screen. It fades in, stays visible for a few seconds, then fades out and removes itself. The user can also close it manually with the × button.

### 📋 Parameters
| Parameter | Type | Default | Description |
|---|---|---|---|
| `message` | string | *(required)* | The text to display |
| `type` | string | `'success'` | Visual style: `'success'` (green), `'error'` (red), `'warning'` (amber) |
| `duration` | number | `4200` | How long to show it in milliseconds (4.2 seconds) |

### 📂 Location
[main.js — Lines 153–166](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L153-L166)
```javascript
function showToast(message, type = 'success', duration = 4200) {
    const c = document.getElementById('toastContainer');
    if (!c) return;

    const icons = { success: '✓', error: '✕', warning: '⚠' };

    // Create the toast element
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML =
        `<span style="color:...">${icons[type] || '•'}</span>` +
        `<span class="toast-msg">${message}</span>` +
        `<button class="toast-x" onclick="this.parentElement.remove()">×</button>`;

    c.appendChild(t);

    // Trigger fade-in on next two animation frames
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));

    // Auto-remove after duration
    setTimeout(() => {
        t.classList.remove('show');           // Fade out
        setTimeout(() => t.remove(), 350);    // Remove from DOM after fade
    }, duration);
}
```

### 🔄 Step-by-step
1. Find `#toastContainer` — the invisible container div in the layout.
2. Create a new `<div>` with the correct CSS class and icon.
3. **`appendChild(t)`** — add it to the page (it starts invisible via CSS).
4. **`requestAnimationFrame()`** — double rAF trick: waits for 2 browser paint cycles so the CSS transition has time to register. Without this, `classList.add('show')` would happen before CSS sees the element and no animation would play.
5. **`setTimeout(..., duration)`** — after `duration` ms, removes `'show'` class (fade-out starts).
6. **`setTimeout(..., 350)`** — after the 350ms fade-out animation, removes the element from the DOM entirely.

### 💡 The Double `requestAnimationFrame` trick explained
```javascript
// Without it:
c.appendChild(t);
t.classList.add('show'); // Element exists + show class added in same frame
// CSS transition doesn't fire because the element had no previous state

// With it:
c.appendChild(t);
requestAnimationFrame(() =>      // Frame 1: browser sees the element
    requestAnimationFrame(() =>  // Frame 2: now add class → transition fires ✅
        t.classList.add('show')
    )
);
```

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `document.createElement(tag)` | Creates a new HTML element in memory |
| `element.className = x` | Sets all CSS classes at once |
| `element.innerHTML = x` | Sets the HTML content of an element |
| `element.appendChild(child)` | Inserts a child element at the end |
| `requestAnimationFrame(fn)` | Calls `fn` before the browser's next repaint |
| `setTimeout(fn, ms)` | Calls `fn` after `ms` milliseconds |
| `element.remove()` | Removes an element from the DOM entirely |

---

## 13. `checkUrlToast()`

### 📖 What is it?
After a PHP form submission (add client, delete user, etc.), PHP redirects to the same page with `?msg=Success message&type=success` in the URL. This function reads those URL parameters, shows the message as a toast notification, then **cleans the URL** so the parameters disappear from the address bar.

### 📂 Location
[main.js — Lines 168–176](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L168-L176)
```javascript
function checkUrlToast() {
    const p = new URLSearchParams(window.location.search);
    const msg = p.get('msg');
    if (!msg) return;  // No message in URL → do nothing

    showToast(decodeURIComponent(msg), p.get('type') || 'success');

    // Clean the URL (remove ?msg=... without reloading)
    const u = new URL(window.location.href);
    u.searchParams.delete('msg');
    u.searchParams.delete('type');
    window.history.replaceState({}, '', u.toString());
}
```

### 🔄 Step-by-step
1. **`new URLSearchParams(window.location.search)`** — parses the query string. For `?msg=Client+added&type=success` it creates an object you can query.
2. **`p.get('msg')`** — extracts the `msg` value. Returns `null` if not present.
3. **`decodeURIComponent(msg)`** — converts URL-encoded characters back: `Client%20added` → `Client added`.
4. **`showToast()`** — displays the message.
5. **`new URL(window.location.href)`** — parses the full current URL.
6. **`u.searchParams.delete('msg')`** — removes `msg` from the URL object.
7. **`window.history.replaceState({}, '', u.toString())`** — updates the browser's address bar **without reloading the page**. The URL becomes clean.

### 💡 Why clean the URL?
If the user refreshes the page with `?msg=...` still in the URL, the toast would appear again. Cleaning it prevents that.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `new URLSearchParams(search)` | Parses a query string into key-value pairs |
| `params.get(key)` | Gets the value of a URL parameter by name |
| `decodeURIComponent(str)` | Decodes percent-encoded URL characters |
| `new URL(href)` | Parses a full URL into parts |
| `url.searchParams.delete(key)` | Removes a parameter from a URL object |
| `window.history.replaceState(state, title, url)` | Updates the address bar URL without navigating |

---

## 14. `initTabs()`

### 📖 What is it?
Turns groups of tab buttons into a working tab panel system. When a tab button is clicked, it becomes active and only its corresponding panel is shown — all others are hidden.

### 📂 Location
[main.js — Lines 181–195](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L181-L195)
```javascript
function initTabs() {
    document.querySelectorAll('.tab-list[data-target]').forEach(tl => {
        const group  = tl.dataset.target;  // e.g. "txnTabs"
        const btns   = tl.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll(`[data-tab-group="${group}"] .tab-panel`);

        const activate = i => {
            btns.forEach((b, j)   => b.classList.toggle('active', i === j));
            panels.forEach((p, j) => p.style.display = i === j ? 'block' : 'none');
        };

        btns.forEach((btn, i) => btn.addEventListener('click', () => activate(i)));
        activate(0);  // Show the first tab by default
    });
}
```

### 🔄 How the HTML and JS connect
```html
<!-- Buttons -->
<div class="tab-list" data-target="txnTabs">
    <button class="tab-btn">⬆ Deposit</button>   <!-- index 0 -->
    <button class="tab-btn">⬇ Withdraw</button>  <!-- index 1 -->
    <button class="tab-btn">↔ Transfer</button>  <!-- index 2 -->
</div>

<!-- Panels (matched by group name) -->
<div data-tab-group="txnTabs">
    <div class="tab-panel">Deposit form</div>    <!-- index 0 -->
    <div class="tab-panel">Withdraw form</div>   <!-- index 1 -->
    <div class="tab-panel">Transfer form</div>   <!-- index 2 -->
</div>
```

When button at index `i` is clicked, `activate(i)` runs:
- Button `i` gets `class="active"` (others lose it)
- Panel `i` gets `display: block` (others get `display: none`)

### 💡 `classList.toggle(class, condition)`
```javascript
b.classList.toggle('active', i === j);
// If i===j is TRUE  → adds 'active' class
// If i===j is FALSE → removes 'active' class
```
This is cleaner than using `add`/`remove` separately.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `element.dataset.target` | Reads the `data-target="..."` HTML attribute |
| `classList.toggle(cls, bool)` | Adds the class if bool is true, removes if false |
| `element.style.display` | Controls visibility inline (overrides CSS) |
| Template literal `` `[data-tab-group="${group}"]` `` | Builds a CSS selector string dynamically |

---

## 15. `initPermissionToggle(toggleId, cbSelector)`

### 📖 What is it?
Controls the relationship between the "Full Access ⭐" master checkbox and the individual permission checkboxes. When Full Access is checked, all individual checkboxes become disabled (grayed out) and unchecked. When it is unchecked, they become editable again.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `toggleId` | string | The `id` of the Full Access checkbox element |
| `cbSelector` | string | A CSS selector targeting the individual permission checkboxes |

### 📂 Location
[main.js — Lines 201–215](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L201-L215)
```javascript
function initPermissionToggle(toggleId, cbSelector) {
    const toggle = document.getElementById(toggleId);
    if (!toggle) return;  // This modal might not exist on this page
    const cbs = document.querySelectorAll(cbSelector);

    const sync = () => {
        cbs.forEach(cb => {
            cb.disabled = toggle.checked;  // Disable if Full Access is on
            if (toggle.checked) cb.checked = false;  // Uncheck them too
        });
    };

    toggle.addEventListener('change', sync);  // Run on every toggle change
    sync();  // Run once on page load to set initial state
}
```

### 🔄 Called twice — once per modal
```javascript
// In DOMContentLoaded:
initPermissionToggle('permFullAccess',    '.perm-checkbox');   // Add User modal
initPermissionToggle('editPermFullAccess','.edit-perm-cb');    // Edit User modal
```
Each call independently manages one set of checkboxes.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `checkbox.checked` | `true` if ticked, `false` if not |
| `checkbox.disabled` | `true` → grayed out and not submittable |
| `element.addEventListener('change', fn)` | Fires `fn` when a checkbox is toggled |

---

## 16. `initSearch()`

### 📖 What is it?
Makes the search input above the clients and users tables work as a **live filter** — as the user types, rows that don't match the search term are instantly hidden. No page reload or server request — everything happens in the browser.

### 📂 Location
[main.js — Lines 220–231](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L220-L231)
```javascript
function initSearch() {
    const inp = document.getElementById('tableSearch');
    const tbl = document.getElementById('mainTable');
    if (!inp || !tbl) return;  // Exit if elements don't exist on this page

    inp.addEventListener('input', () => {
        const q = inp.value.toLowerCase();  // Search term, case-insensitive
        tbl.querySelectorAll('tbody tr').forEach(row => {
            const match = row.textContent.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
            //                   ↑ '' restores default display, 'none' hides
        });
    });
}
```

### 🔄 How it works
1. Every time the user types a character, the `'input'` event fires.
2. The current text is lowercased for case-insensitive comparison.
3. Every `<tr>` in `<tbody>` is checked: does its **full text content** contain the search term?
4. Non-matching rows get `display: none` — they vanish instantly.
5. Matching rows keep `display: ''` (the CSS default).

### 💡 `row.textContent`
This reads **all the visible text** in the row, across all cells. So searching "ACC-001" matches the account number column, "John Smith" matches the name column — all from one property.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `element.addEventListener('input', fn)` | Fires `fn` on every keystroke in an input |
| `String.toLowerCase()` | Converts text to lowercase for comparison |
| `String.includes(substring)` | Returns `true` if the string contains the substring |
| `element.textContent` | All text content of an element and its children |
| `element.style.display` | Shows or hides an element |

---

## 17. `fetchBalance(accountInput, displayId)`

### 📖 What is it?
Attaches a blur listener to an account number input field. When the user leaves the field (blur = losing focus), it silently fetches the account's balance from the server via `api_balance.php` and displays it next to the field.

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `accountInput` | string | The `id` of the account number `<input>` |
| `displayId` | string | The `id` of the element where the balance will be shown |

### 📂 Location
[main.js — Lines 236–257](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L236-L257)
```javascript
function fetchBalance(accountInput, displayId) {
    const el   = document.getElementById(accountInput);
    const disp = document.getElementById(displayId);
    if (!el || !disp) return;

    el.addEventListener('blur', () => {
        const acc = el.value.trim();
        if (!acc) { disp.textContent = ''; return; }

        fetch(`api_balance.php?account=${encodeURIComponent(acc)}`)
            .then(r => r.json())     // Parse response as JSON
            .then(d => {
                if (d.balance !== undefined) {
                    disp.textContent = '$' + parseFloat(d.balance).toFixed(2);
                    disp.style.color = '#16A34A'; // Green
                } else {
                    disp.textContent = 'Account not found';
                    disp.style.color = '#DC2626'; // Red
                }
            })
            .catch(() => { disp.textContent = ''; });
    });
}
```

### 🔄 The `fetch()` chain explained
```
fetch(url)          → Returns a Promise<Response>
  .then(r => r.json()) → Parses JSON body → Returns Promise<data>
  .then(d => {...})    → Receives the data object
  .catch(() => {...})  → Handles network errors
```
`fetch` is **asynchronous** — it doesn't pause JavaScript. The `.then()` callbacks run later when the server responds.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `element.addEventListener('blur', fn)` | Fires when element loses focus |
| `String.trim()` | Removes whitespace from start and end |
| `encodeURIComponent(str)` | Makes a string safe to put in a URL |
| `fetch(url)` | Makes an HTTP request (GET by default) |
| `Promise.then(fn)` | Callback for when an async operation succeeds |
| `Promise.catch(fn)` | Callback for when an async operation fails |
| `response.json()` | Parses response body as JSON |
| `parseFloat(str)` | Converts a string to a decimal number |
| `Number.toFixed(n)` | Formats a number to `n` decimal places |

---

## 18. `confirmAction(message)`

### 📖 What is it?
A very simple wrapper around the browser's built-in `window.confirm()` dialog. It shows a native browser popup with a message, an OK button, and a Cancel button, and returns `true` (OK) or `false` (Cancel).

### 📋 Parameters
| Parameter | Type | Description |
|---|---|---|
| `message` | string | The question to show the user |

### 📤 Return Value
- `true` if the user clicked OK
- `false` if the user clicked Cancel

### 📂 Location
[main.js — Lines 262–264](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L262-L264)
```javascript
function confirmAction(message) {
    return window.confirm(message);
}
```

### 📞 Called from
In `dashboard.php`, on the delete news form:
```html
<form onsubmit="return confirmAction('Delete this news?')">
```
If `confirmAction` returns `false`, `return false` on `onsubmit` blocks the form from submitting.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `window.confirm(message)` | Shows a native browser popup. Blocks all JS until dismissed. Returns `true`/`false`. |

---

## 19. `initAccountSearchSelects()`

### 📖 What is it?
The most complex function in the project. It transforms every `.acc-combo` element into a **fully featured searchable dropdown**. This replaces a standard `<select>` with a custom control that lets users type to filter a potentially long list of accounts.

### Features built by this function
- ✅ Type to live-filter by account number or client name
- ✅ Click to select an item
- ✅ Arrow Up / Arrow Down keyboard navigation
- ✅ Enter to select the highlighted item
- ✅ Escape to close the list
- ✅ Blur (click away) to close
- ✅ Form submit blocked if no account selected
- ✅ Restores pre-selected value after PHP validation error re-renders

### 📂 Location
[main.js — Lines 273–402](file:///opt/lampp/htdocs/WebProjectV2-1/assets/js/main.js#L273-L402)

### 🔄 Initialization — what elements it works with
```javascript
document.querySelectorAll('.acc-combo').forEach(combo => {
    const trigger     = combo.querySelector('.acc-combo-trigger');  // Outer wrapper
    const searchInput = combo.querySelector('.acc-combo-search');   // Visible text input
    const hiddenInput = combo.querySelector('input[type="hidden"]');// Stores real value
    const list        = combo.querySelector('.acc-combo-list');     // The dropdown list
    const allItems    = Array.from(combo.querySelectorAll('.acc-combo-item'));
    let highlighted   = -1; // Index of keyboard-highlighted item (-1 = none)
```

### 🔄 Event listeners set up

| Event | Element | Action |
|---|---|---|
| `focus` | searchInput | Open dropdown, show all items, select all text |
| `input` | searchInput | Filter the list as user types, clear hidden value |
| `mousedown` | list | Pick the clicked item (mousedown fires before blur) |
| `keydown` | searchInput | Arrow navigation, Enter to pick, Escape to close |
| `blur` | searchInput | Close dropdown after 200ms delay |
| `submit` | parent form | Block submission if no account chosen |

### 💡 Why `mousedown` instead of `click` for list items?
When the user clicks a list item, two events fire in order:
1. `mousedown` on the list item
2. `blur` on the search input (because focus left it)

If we used `click`, the `blur` handler would fire first and close the list before `click` fired — the click would be missed. Using `mousedown` (which fires before `blur`) captures the selection first.

The `blur` handler uses `setTimeout(..., 200)` to delay closing, giving `mousedown` time to process.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `Array.from(nodeList)` | Converts a NodeList to a real Array (enables `.find()`, `.filter()`) |
| `array.find(fn)` | Returns the first element where `fn` returns true |
| `array.filter(fn)` | Returns all elements where `fn` returns true |
| `element.select()` | Selects all text in an input field |
| `event.target.closest(selector)` | Finds the nearest ancestor matching a CSS selector |
| `event.preventDefault()` | Stops the default browser action for this event |
| `String.toLowerCase().includes()` | Case-insensitive text search |
| `element.scrollIntoView({block:'nearest'})` | Scrolls so the element is visible in its container |
| `Math.min(a, b)` | Returns the smaller of two numbers |
| `Math.max(a, b)` | Returns the larger of two numbers |

---

### Inner Helper: `filter(query)`

**Defined inside `initAccountSearchSelects()`** — only accessible within the combo's own scope.

### 📖 What is it?
Filters the visible items in the dropdown based on a search string. Shows items whose `data-label` contains the query, hides the rest. Also manages the "No matching clients" empty message.

```javascript
function filter(query) {
    const q = query.trim().toLowerCase();
    let count = 0;
    highlighted = -1;  // Reset keyboard selection
    allItems.forEach(item => {
        const match = !q || item.dataset.label.toLowerCase().includes(q);
        item.style.display = match ? '' : 'none';
        item.classList.remove('highlighted');
        if (match) count++;
    });
    // Manage the "No matching clients" message
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
```

**`!q`** means: if the query is empty, show all items (no filtering needed).

---

### Inner Helper: `pick(item)`

**Defined inside `initAccountSearchSelects()`**.

### 📖 What is it?
Selects an item from the dropdown — sets the hidden input value (what gets sent to PHP), updates the visible text input to show the selected label, marks the item as selected, and closes the dropdown.

```javascript
function pick(item) {
    hiddenInput.value = item.dataset.value;  // Real value for PHP (account number)
    searchInput.value = item.dataset.label;  // Display text for the user
    allItems.forEach(i => i.classList.remove('selected', 'highlighted'));
    item.classList.add('selected');
    combo.classList.remove('open');
    highlighted = -1;
}
```

---

### Inner Helper: `highlight(visible)`

**Defined inside `initAccountSearchSelects()`**.

### 📖 What is it?
Visually highlights (keyboard-focuses) the item at the `highlighted` index. Called when the user presses Arrow Up or Arrow Down. Also scrolls the highlighted item into view if the list is scrollable.

```javascript
function highlight(visible) {
    visible.forEach((item, i) => item.classList.toggle('highlighted', i === highlighted));
    if (visible[highlighted]) visible[highlighted].scrollIntoView({ block: 'nearest' });
}
```

---

## 20. `doLookup()`

### 📖 What is it?
Handles the "Look Up" button on the Transactions page. Reads the account number from the `#lookupAcc` input, calls `api_balance.php`, and displays the result (name + balance) or an error message directly in the page.

### 📂 Location
[transactions.php — Lines 446–462](file:///opt/lampp/htdocs/WebProjectV2-1/transactions.php#L446-L462)

```javascript
function doLookup() {
    const acc = document.getElementById('lookupAcc').value.trim();
    const res = document.getElementById('lookupResult');
    if (!acc) { res.textContent = ''; return; }

    res.textContent = 'Looking up…';
    res.style.color = 'rgba(255,255,255,.5)'; // Gray while loading

    fetch('api_balance.php?account=' + encodeURIComponent(acc))
        .then(r => r.json())
        .then(d => {
            if (d.error) {
                res.textContent = '✕ ' + d.error;
                res.style.color = '#FCA5A5'; // Red
            } else {
                res.textContent = '✓ ' + d.name + ' — $' + parseFloat(d.balance).toFixed(2);
                res.style.color = '#4ADE80'; // Green
            }
        })
        .catch(() => {
            res.textContent = 'Lookup failed.';
            res.style.color = '#FCA5A5';
        });
}
```

This function is also triggered by the Enter key:
```javascript
document.getElementById('lookupAcc')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); doLookup(); }
});
```
The `?.` is **optional chaining** — if `#lookupAcc` doesn't exist on this page, it does nothing instead of throwing an error.

### 🌐 Web APIs Used
| API | What it does |
|---|---|
| `fetch(url)` | Makes a GET request to the server |
| `response.json()` | Parses JSON response |
| `encodeURIComponent()` | URL-encodes the account number |
| `parseFloat().toFixed(2)` | Formats balance to 2 decimal places |
| `element?.addEventListener()` | Optional chaining: only runs if element exists |

---

## 🗺️ Complete Function Call Graph

```
DOMContentLoaded fires
    │
    ├── initSidebar()         → highlights active nav link
    ├── initModals()          → wires backdrop/button/Escape → closeModal()
    ├── initTabs()            → wires tab buttons → activate()
    ├── initPermissionToggle() ×2 → wires Full Access checkbox → sync()
    ├── initAccountSearchSelects() → wires every .acc-combo
    │       └── inner: filter() / pick() / highlight()
    ├── initSearch()          → wires #tableSearch → filters table rows
    └── checkUrlToast()       → reads ?msg= → showToast() → cleans URL

beforeunload fires (page leaving)
    └── saves window.scrollY → sessionStorage

restoreScrollPosition() (runs immediately on script load)
    └── reads sessionStorage → window.scrollTo()

User clicks edit button (clients/users page)
    └── setupEditClient(data) / setupEditUser(data) → openModal()

User clicks delete button
    └── setupDeleteClient() / setupDeleteUser() → openModal()

User clicks × or Cancel or Escape or backdrop
    └── closeModal()

User clicks "Look Up" or presses Enter in #lookupAcc
    └── doLookup() → fetch(api_balance.php) → shows result

PHP redirect with ?msg= in URL
    └── checkUrlToast() → showToast() → auto-removes after 4.2s
```

---

## 📊 Web APIs Quick Reference

| API | Functions that use it |
|---|---|
| `document.getElementById()` | openModal, closeModal, setupEdit*, setupDelete*, showToast, initSearch, initPermissionToggle, doLookup |
| `document.querySelectorAll()` | initSidebar, initModals, initTabs, initPermissionToggle, initSearch, initAccountSearchSelects |
| `element.classList.add/remove/toggle()` | Almost every function |
| `element.style.*` | openModal (overflow), initTabs (display), initSearch (display), showToast (color), initAccountSearchSelects (display) |
| `element.addEventListener()` | initModals, initTabs, initPermissionToggle, initSearch, fetchBalance, initAccountSearchSelects |
| `setTimeout()` | openModal (focus delay), showToast (auto-remove), initAccountSearchSelects (blur delay) |
| `fetch() + .then() + .catch()` | fetchBalance, doLookup |
| `sessionStorage` | restoreScrollPosition, beforeunload listener |
| `window.scrollTo()` | restoreScrollPosition |
| `window.history.replaceState()` | checkUrlToast |
| `new URLSearchParams()` | checkUrlToast |
| `document.createElement()` | showToast, filter (empty message) |
| `requestAnimationFrame()` | showToast (double rAF fade-in trick) |
| `element.scrollIntoView()` | highlight (keyboard navigation) |

---

*Report generated for Prototype Bank — WebProjectV2 | JavaScript Edition*
