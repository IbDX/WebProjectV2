<?php
// ============================================================
//  includes/sidebar.php — Persistent Navigation Sidebar
//  Permission-aware: hides nav items the user can't access.
// ============================================================
if (!function_exists('has_permission')) {
    require_once __DIR__ . '/../config/auth_check.php';
}

$page     = basename($_SERVER['PHP_SELF']);
$uPerms   = (int)($_SESSION['permissions'] ?? 0);
$isFull   = ($uPerms === PERM_FULL);
$uname    = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($uname, 0, 2));
?>
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-icon">PB</div>
        <div>
            <div class="logo-text">Prototype Bank</div>
            <div class="logo-sub">Management System</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <span class="nav-label">Main</span>

        <!-- Dashboard — always visible -->
        <a href="dashboard.php" class="nav-item <?= $page === 'dashboard.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                <rect x="14" y="14" width="7" height="7" rx="1.5"/>
            </svg>
            Dashboard
        </a>

        <!-- Clients -->
        <?php if ($isFull || ($uPerms & PERM_CLIENT_LIST)): ?>
        <a href="clients.php" class="nav-item <?= $page === 'clients.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Clients
        </a>
        <?php endif; ?>

        <!-- Transactions -->
        <?php if ($isFull || ($uPerms & PERM_TRANSACTIONS)): ?>
        <a href="transactions.php" class="nav-item <?= $page === 'transactions.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            Transactions
        </a>
        <?php endif; ?>

        <!-- Users (admin) -->
        <?php if ($isFull || ($uPerms & PERM_MANAGE_USERS)): ?>
        <span class="nav-label">Administration</span>
        <a href="users.php" class="nav-item <?= $page === 'users.php' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            User Management
        </a>
        <?php endif; ?>
    </nav>

    <!-- User strip + logout -->
    <div class="sidebar-footer">
        <div class="user-strip">
            <div class="user-avatar"><?= e($initials) ?></div>
            <div style="flex:1;min-width:0">
                <div class="user-name"><?= e($uname) ?></div>
                <div class="user-role"><?= $isFull ? '⭐ Full Access' : 'Limited Access' ?></div>
            </div>
        </div>
        <a href="logout.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign Out
        </a>
    </div>

</aside>
