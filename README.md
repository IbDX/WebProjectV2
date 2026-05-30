# Prototype Bank — Management System

A full-featured bank management dashboard built with PHP, MySQL, and vanilla HTML/CSS/JS. Designed for internal staff use, it covers client account management, financial transactions, user access control, and real-time activity monitoring.

---

## Table of Contents

1. [Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [Project Structure](#project-structure)
4. [Database Schema](#database-schema)
5. [Setup & Installation](#setup--installation)
6. [Default Credentials](#default-credentials)
7. [Permission System](#permission-system)
8. [Pages & Features](#pages--features)
   - [Login](#login)
   - [Dashboard](#dashboard)
   - [Clients](#clients)
   - [Transactions](#transactions)
   - [User Management](#user-management)
9. [API Endpoint](#api-endpoint)
10. [Frontend Architecture](#frontend-architecture)
11. [Security Notes](#security-notes)
12. [Known Limitations](#known-limitations)

---

## Overview

**Prototype Bank** is a server-rendered PHP web application that simulates a bank back-office management system. Staff members log in with role-based access and can:

- Manage bank client accounts (add, edit, delete)
- Process financial transactions (deposits, withdrawals, transfers)
- Administer system users and their granular permissions
- Monitor activity via a system log and bank news feed on the dashboard

---

## Tech Stack

| Layer      | Technology                                      |
|------------|-------------------------------------------------|
| Backend    | PHP 7.4+ (procedural, no framework)             |
| Database   | MySQL 5.7 / 8.0 via **MySQLi** (prepared statements) |
| Frontend   | Vanilla HTML5, CSS3, JavaScript (ES6+)          |
| Charts     | Chart.js 4.4 (CDN)                              |
| Fonts      | Huninn — Google Fonts                           |
| Server     | AppServ (Apache + MySQL + PHP bundle for Windows) |

---

## Project Structure

```
WebProjectV2/
│
├── config/
│   ├── db.php              # DB connection, PERM_* constants
│   └── auth_check.php      # Session guard, permission engine, helper functions
│
├── includes/
│   ├── header.php          # Shared <head> + <body> open (CSS, Chart.js)
│   ├── sidebar.php         # Persistent nav sidebar (permission-aware)
│   └── footer.php          # Toast container, main.js, </body></html>
│
├── assets/
│   ├── css/
│   │   └── style.css       # Full design system (variables, components, layout)
│   └── js/
│       └── main.js         # Modals, toasts, tabs, combo boxes, live search
│
├── dashboard.php           # Admin dashboard (stats, charts, log, news)
├── clients.php             # Client account management (CRUD)
├── transactions.php        # Deposit / Withdraw / Transfer
├── users.php               # System user management
├── login.php               # Authentication entry point
├── logout.php              # Session destroy + redirect
├── api_balance.php         # JSON endpoint — quick balance lookup
│
├── schema.sql              # Full DB schema + seed data
└── README.md               # This file
```

---

## Database Schema

### `clients`
Stores bank customer accounts.

| Column           | Type            | Notes                                   |
|------------------|-----------------|-----------------------------------------|
| `account_number` | VARCHAR(20) PK  | Unique identifier (e.g. `ACC-10001`)    |
| `pin_code`       | VARCHAR(255)    | Client PIN (plaintext in prototype)     |
| `full_name`      | VARCHAR(100)    | Client's full name                      |
| `phone`          | VARCHAR(20)     | Optional phone number                   |
| `balance`        | DECIMAL(15,2)   | Current account balance                 |
| `is_deleted`     | TINYINT(1)      | Soft-delete flag (0 = active)           |
| `created_at`     | DATETIME        | Row creation timestamp                  |
| `updated_at`     | DATETIME        | Auto-updated on every change            |

### `users`
System staff accounts (not bank clients).

| Column        | Type          | Notes                                          |
|---------------|---------------|------------------------------------------------|
| `username`    | VARCHAR(50) PK| Unique login name                              |
| `password`    | VARCHAR(255)  | MD5 hash *(prototype only — not production-safe)* |
| `permissions` | INT           | Bitmask of `PERM_*` flags; `-1` = Full Access  |
| `is_deleted`  | TINYINT(1)    | Soft-delete flag                               |
| `created_at`  | DATETIME      | Account creation timestamp                     |

### `transactions`
Immutable ledger of all financial operations.

| Column           | Type            | Notes                                         |
|------------------|-----------------|-----------------------------------------------|
| `id`             | INT AUTO_INC PK | Transaction ID                                |
| `account_number` | VARCHAR(20) FK  | References `clients.account_number`           |
| `type`           | ENUM            | `'Deposit'`, `'Withdraw'`, `'Transfer'`       |
| `amount`         | DECIMAL(15,2)   | Transaction value                             |
| `target_account` | VARCHAR(20)     | Populated for Transfer type only              |
| `timestamp`      | DATETIME        | Auto-set on insert                            |

> **FK constraint:** `ON DELETE RESTRICT, ON UPDATE CASCADE` — a client cannot be deleted while they have transaction records. Deletion code handles this by removing transactions first within a DB transaction.

### `news`
Bank announcements posted by Full Access users.

| Column       | Type          | Notes                         |
|--------------|---------------|-------------------------------|
| `id`         | INT AUTO_INC  | News item ID                  |
| `title`      | VARCHAR(255)  | Announcement headline         |
| `content`    | TEXT          | Body text                     |
| `author`     | VARCHAR(50)   | Username of poster            |
| `is_deleted` | TINYINT(1)    | Soft-delete flag              |
| `created_at` | DATETIME      | Post timestamp                |

---

## Setup & Installation

### Prerequisites
- [AppServ](https://www.appserv.org/) (or any LAMP/WAMP stack with PHP 7.4+ and MySQL 5.7+)
- A web browser

### Steps

1. **Clone / copy** the project folder into your web root:
   ```
   C:\AppServ\www\WebProjectV2\
   ```

2. **Import the database schema:**
   - Open [phpMyAdmin](http://localhost/phpMyAdmin/)
   - Click **Import** → select `schema.sql` → click **Go**
   - This creates the `prototype_bank` database with all tables and seed data

3. **Configure the database connection** in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '12345678');   // ← change if your MySQL root has a different password
   define('DB_NAME', 'prototype_bank');
   define('DB_PORT', 3306);
   ```

4. **Open the application** in your browser:
   ```
   http://localhost/WebProjectV2/
   ```
   You will be redirected to the login page automatically.

---

## Default Credentials

| Username | Password   | Access Level |
|----------|------------|--------------|
| `root`   | `12345678` | ⭐ Full Access (unrestricted) |

> The seed data also inserts 7 sample clients and 8 sample transactions.

---

## Permission System

Access is controlled by a **bitmask integer** stored in `users.permissions`. Each permission is a power of two that can be combined freely.

| Constant            | Value | Description                             |
|---------------------|-------|-----------------------------------------|
| `PERM_FULL`         | `-1`  | Unrestricted — bypasses all checks      |
| `PERM_CLIENT_LIST`  | `1`   | View the clients table                  |
| `PERM_ADD_CLIENT`   | `2`   | Add new client accounts                 |
| `PERM_DEL_CLIENT`   | `4`   | Delete client accounts                  |
| `PERM_UPD_CLIENT`   | `8`   | Edit client accounts                    |
| `PERM_FIND_CLIENT`  | `16`  | Search / filter clients                 |
| `PERM_MANAGE_USERS` | `32`  | Add, edit, and delete system users      |
| `PERM_TRANSACTIONS` | `64`  | Access the Transactions page            |

**Example:** A user with `permissions = 27` has bits 1+2+8+16 set → View + Add + Update + Find Clients.

Permissions are checked in PHP via:
```php
has_permission(PERM_ADD_CLIENT);   // returns bool
require_permission(PERM_TRANSACTIONS);  // redirects to access-denied if false
```

Full Access users (`permissions = -1`) always pass every permission check. They are protected from modification or deletion by any other user.

---

## Pages & Features

### Login
**File:** `login.php`

- Username + password form (POST → PRG)
- Password verified against MD5 hash stored in DB
- On success: `session_regenerate_id(true)` is called, then redirects to `dashboard.php`
- Already-logged-in users are immediately redirected to the dashboard
- Login query filters `is_deleted = 0` — deleted users cannot log in

---

### Dashboard
**File:** `dashboard.php`

The main overview screen. Visible to all authenticated users; some widgets are Full Access only.

| Widget | Description |
|--------|-------------|
| **Stat Cards** | Active clients, total DB rows (clients + users + transactions), total balance across all accounts |
| **Client Registrations Chart** | Bar chart (Chart.js) — new clients per day over the last 7 days |
| **Bank News** | Paginated announcements; Full Access users can post and delete items |
| **System Log** | Paginated transaction log — Full Access only |
| **Permission Distribution** | Progress bars + doughnut chart showing how users are split by permission group |
| **Recently Modified** | Last 6 clients sorted by `updated_at` |
| **Quick Actions** | Shortcut links filtered by the current user's permissions |

**AJAX pagination:** Both the System Log and Bank News widgets use `fetch()` to load new pages without reloading the page. The PHP AJAX endpoint is embedded at the top of `dashboard.php` and responds to `?ajax=log&lp=N` and `?ajax=news&np=N` with JSON.

---

### Clients
**File:** `clients.php`

Full CRUD for bank client accounts.

| Action | Permission Required | Behaviour |
|--------|---------------------|-----------|
| View list | `PERM_CLIENT_LIST` | Table with account number, name, phone, balance, last modified |
| Search | `PERM_FIND_CLIENT` | Server-side GET search across account number, name, phone |
| Add | `PERM_ADD_CLIENT` | Modal form; duplicate account number check before insert |
| Edit | `PERM_UPD_CLIENT` | Pre-filled modal; can update PIN, name, phone, balance |
| Delete | `PERM_DEL_CLIENT` | Confirmation modal; **permanently deletes** the client row and all their transactions inside a DB transaction |

**PRG pattern** is used on all POST actions — a redirect with `?msg=` and `?type=` query params triggers a toast notification on next load.

---

### Transactions
**File:** `transactions.php`

All monetary operations against client accounts.

| Tab | Action | Notes |
|-----|--------|-------|
| ⬆ Deposit | Add funds to an account | Updates `balance + amount` |
| ⬇ Withdraw | Remove funds from an account | Rejected if `balance < amount` |
| ↔ Transfer | Move funds between two accounts | **Atomic** — uses `BEGIN TRANSACTION` / `COMMIT` / `ROLLBACK`; both rows locked with `FOR UPDATE` |

**Searchable account combo box:** All account fields use a custom dropdown that lets staff type to filter by account number or client name. Keyboard navigation (↑ ↓ Enter Escape) is fully supported.

**Quick Balance Lookup:** A sidebar widget lets staff look up any account balance on demand via the `api_balance.php` JSON endpoint without performing a transaction.

Each successful operation is recorded in the `transactions` table and a success toast is shown on redirect.

---

### User Management
**File:** `users.php`

**Requires `PERM_MANAGE_USERS`.**

| Action | Behaviour |
|--------|-----------|
| View list | All non-Full-Access users shown with their permission badges |
| Add user | Creates a new system user; permission bitmask assembled from checkboxes |
| Edit user | Change password (optional) and/or permissions; Full Access users are protected |
| Delete user | **Permanently deletes** the user row from DB; Full Access users cannot be deleted |

**Full Access toggle:** Checking "Full Access" in the add/edit modal disables all individual permission checkboxes and sets `permissions = -1`.

---

## API Endpoint

### `GET api_balance.php?account=ACC-XXXXX`

Returns a JSON object with the account holder's name and current balance.

**Authentication:** Requires an active session. Returns `{"error":"Unauthenticated"}` otherwise.

**Success response:**
```json
{
  "name": "Alice Johnson",
  "balance": "15000.00"
}
```

**Error response:**
```json
{
  "error": "Account not found"
}
```

---

## Frontend Architecture

### CSS — `assets/css/style.css`
A single-file design system using CSS custom properties (variables).

**Design tokens:**
```css
--bg: #F5F0E1          /* Warm cream page background */
--sidebar-bg: #1D3B26  /* Dark moss green sidebar */
--accent: #2D5A3D      /* Primary action colour */
--card-bg: #FFFFFF     /* White card surface */
--dark-card: #1D3B26   /* Dark card (used on transactions page) */
```

Key component classes: `.card`, `.card-dark`, `.btn`, `.form-control`, `.form-control.dark`, `.modal-overlay`, `.toast`, `.acc-combo`, `.tab-list`, `.alert-*`, `.badge-*`

### JavaScript — `assets/js/main.js`
No dependencies — pure vanilla ES6.

| Function / Module | Description |
|-------------------|-------------|
| `initModals()` | Open/close modal overlays; Escape key + backdrop click support |
| `openModal(id)` / `closeModal(id)` | Programmatic modal control |
| `setupEditClient(data)` | Pre-fills the Edit Client modal from PHP-JSON data |
| `setupDeleteClient(acc, name)` | Pre-fills the Delete Client confirmation modal |
| `setupEditUser(data)` | Pre-fills the Edit User modal and syncs permission checkboxes |
| `setupDeleteUser(username)` | Pre-fills the Delete User confirmation modal |
| `showToast(msg, type)` | Renders an animated toast notification |
| `checkUrlToast()` | Reads `?msg=` and `?type=` from URL on page load, fires toast, then cleans URL via `history.replaceState` |
| `initTabs()` | Tab switching for the Transactions page (Deposit / Withdraw / Transfer) |
| `initAccountSearchSelects()` | Searchable combo boxes for all account pickers |
| `initPermissionToggle()` | Syncs Full Access checkbox with individual permission checkboxes |
| `initSearch()` | Client-side live filter on the `#mainTable` tbody |
| `restoreScrollPosition()` | Saves/restores scroll position across navigations via `sessionStorage` |
| `paginateLog(page)` | AJAX pagination for System Log (dashboard) |
| `paginateNews(page)` | AJAX pagination for Bank News (dashboard) |

---

## Security Notes

> This project is labelled as a **prototype**. The following items must be addressed before any production use:

| Issue | Current State | Recommendation |
|-------|--------------|----------------|
| Password hashing | MD5 (no salt) | Use `password_hash()` / `password_verify()` with bcrypt |
| SQL injection | All queries use **prepared statements** | ✅ Safe |
| XSS | All output goes through `e()` = `htmlspecialchars()` | ✅ Safe |
| CSRF | No CSRF tokens on forms | Add `csrf_token` hidden fields + server-side validation |
| Session security | `session_regenerate_id(true)` on login | Consider adding `HttpOnly` / `Secure` cookie flags |
| Client PIN | Stored in plaintext | Hash with bcrypt or remove entirely |

---

## Known Limitations

- **No pagination on Clients or Users tables** — all rows load at once; may be slow with very large datasets.
- **Transaction history on Transactions page** is capped at the last 50 records.
- **MD5 passwords** — suitable for a local prototype only.
- **No HTTPS enforcement** — assumes a local development server.
- **Chart.js and fonts** are loaded from CDN — requires internet access.
- **Responsive layout** collapses correctly down to ~768 px but is primarily designed for desktop use.
