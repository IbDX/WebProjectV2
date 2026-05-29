<?php
// ============================================================
//  config/auth_check.php — Session Guard & Permission Engine
//  Prototype Bank
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure PERM constants are available
if (!defined('PERM_FULL')) {
    require_once __DIR__ . '/db.php';
}

// ============================================================
//  XSS helper
// ============================================================
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ============================================================
//  Ensure the visitor is authenticated.
//  Redirects to login.php when no active session exists.
// ============================================================
function require_auth(): void {
    if (empty($_SESSION['username'])) {
        header('Location: login.php');
        exit;
    }
}

// ============================================================
//  Check if the active session holds a specific permission.
//
//  @param int $perm  One of the PERM_* constants
//  @return bool
// ============================================================
function has_permission(int $perm): bool {
    $p = (int)($_SESSION['permissions'] ?? 0);
    if ($p === PERM_FULL) return true;      // -1  full access bypasses all checks
    return ($p & $perm) !== 0;
}

// ============================================================
//  Enforce a permission; render an "Access Denied" page
//  inside the standard layout and halt execution on failure.
//
//  @param int $perm
// ============================================================
function require_permission(int $perm): void {
    require_auth();
    if (!has_permission($perm)) {
        // Render full-layout denial page then terminate
        if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Access Denied');
        include __DIR__ . '/../includes/header.php';
        echo '<div class="app-layout">';
        include __DIR__ . '/../includes/sidebar.php';
        echo '<main class="main-content"><div class="page-body">';
        echo '<div class="access-denied">';
        echo '<div class="access-denied-icon">🔒</div>';
        echo '<h2>Access Denied</h2>';
        echo '<p>Access Denied, Please Contact Admin.</p>';
        echo '<a href="dashboard.php" class="btn btn-primary" style="margin-top:8px">← Back to Dashboard</a>';
        echo '</div></div></main></div>';
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// ============================================================
//  Returns HTML badge(s) representing a permission bitmask.
// ============================================================
function perm_labels(int $perm): string {
    if ($perm === PERM_FULL) {
        return '<span class="badge badge-full">⭐ Full Access</span>';
    }
    if ($perm === 0) {
        return '<span class="badge badge-muted">No Access</span>';
    }
    $map = [
        PERM_CLIENT_LIST  => 'View Clients',
        PERM_ADD_CLIENT   => 'Add Client',
        PERM_DEL_CLIENT   => 'Delete Client',
        PERM_UPD_CLIENT   => 'Update Client',
        PERM_FIND_CLIENT  => 'Find Client',
        PERM_MANAGE_USERS => 'Manage Users',
        PERM_TRANSACTIONS => 'Transactions',
    ];
    $out = [];
    foreach ($map as $bit => $label) {
        if ($perm & $bit) {
            $out[] = '<span class="badge badge-muted">' . $label . '</span>';
        }
    }
    return implode(' ', $out);
}

// ============================================================
//  Format money with locale-style separators.
// ============================================================
function fmt_money(float $n): string {
    return '$' . number_format($n, 2);
}
