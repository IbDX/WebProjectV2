<?php
// ============================================================
//  dashboard.php — Admin Dashboard
//  Prototype Bank
// ============================================================
define('PAGE_TITLE', 'Dashboard');
require_once 'config/db.php';
require_once 'config/auth_check.php';
require_auth();

// ── News Actions (Full Access Only) ──────────────────────────
$msg = ''; $msgType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)$_SESSION['permissions'] === -1) {
    if (isset($_POST['action']) && $_POST['action'] === 'add_news') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title !== '' && $content !== '') {
            $stmt = mysqli_prepare($conn, "INSERT INTO news (title, content, author) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sss', $title, $content, $_SESSION['username']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg = 'News announcement posted.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_news') {
        $newsId = (int)($_POST['news_id'] ?? 0);
        mysqli_query($conn, "UPDATE news SET is_deleted=1 WHERE id=$newsId");
        $msg = 'News announcement deleted.';
    }
}

// ── Stats ────────────────────────────────────────────────────
$r = mysqli_query($conn, "SELECT COUNT(*) FROM clients WHERE is_deleted = 0");
$totalClients = (int)mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE is_deleted = 0");
$totalUsers = (int)mysqli_fetch_row($r)[0];

$r = mysqli_query($conn, "SELECT COUNT(*) FROM transactions");
$totalTxns = (int)mysqli_fetch_row($r)[0];

$totalRows = $totalClients + $totalUsers + $totalTxns;

$r = mysqli_query($conn, "SELECT COALESCE(SUM(balance),0) FROM clients WHERE is_deleted = 0");
$totalBalance = (float)mysqli_fetch_row($r)[0];

// ── Chart: Client registrations per day (last 7 days) ────────
$chartDays  = [];
$chartCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $chartDays[]   = date('M j', strtotime("-{$i} days"));
    $chartCounts[] = 0;
}
$res = mysqli_query($conn,
    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
     FROM clients
     WHERE is_deleted = 0
       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)
     ORDER BY day"
);
while ($row = mysqli_fetch_assoc($res)) {
    $idx = array_search(date('M j', strtotime($row['day'])), $chartDays);
    if ($idx !== false) $chartCounts[$idx] = (int)$row['cnt'];
}
$chartLabels = json_encode($chartDays);
$chartData   = json_encode($chartCounts);

// ── System Log Pagination ───────────────────────────────────
$logPage = max(1, (int)($_GET['lp'] ?? 1));
$logLimit = 4;
$logOffset = ($logPage - 1) * $logLimit;

$r = mysqli_query($conn, "SELECT COUNT(*) FROM transactions");
$totalLogs = (int)mysqli_fetch_row($r)[0];
$logPages = max(1, (int)ceil($totalLogs / $logLimit));

$logRes = mysqli_query($conn,
    "SELECT t.type, t.amount, t.target_account, t.timestamp,
            c.full_name
     FROM transactions t
     LEFT JOIN clients c ON t.account_number = c.account_number
     ORDER BY t.timestamp DESC
     LIMIT $logLimit OFFSET $logOffset"
);
$logItems = [];
while ($row = mysqli_fetch_assoc($logRes)) {
    $logItems[] = $row;
}

// ── Permission distribution ───────────────────────────────────
$permRes = mysqli_query($conn,
    "SELECT 
        CASE 
            WHEN permissions = -1 THEN 'Full Access'
            WHEN permissions = 0 THEN 'No Access'
            ELSE 'Custom'
        END AS perm_group,
        COUNT(*) AS cnt
     FROM users WHERE is_deleted = 0
     GROUP BY perm_group
     ORDER BY cnt DESC"
);
$permDist = [];
while ($row = mysqli_fetch_assoc($permRes)) {
    $permDist[] = $row;
}
$totalU = max(1, $totalUsers);

// ── Bank News Pagination ────────────────────────────────────
$newsPage = max(1, (int)($_GET['np'] ?? 1));
$newsLimit = 3;
$newsOffset = ($newsPage - 1) * $newsLimit;

$r = mysqli_query($conn, "SELECT COUNT(*) FROM news WHERE is_deleted = 0");
$totalNews = (int)mysqli_fetch_row($r)[0];
$newsPages = max(1, (int)ceil($totalNews / $newsLimit));

$newsRes = mysqli_query($conn,
    "SELECT id, title, content, author, created_at 
     FROM news WHERE is_deleted = 0 
     ORDER BY created_at DESC LIMIT $newsLimit OFFSET $newsOffset"
);
$newsItems = [];
while ($row = mysqli_fetch_assoc($newsRes)) {
    $newsItems[] = $row;
}


// ── Recently modified clients (last 5) ───────────────────────
$recentRes = mysqli_query($conn,
    "SELECT account_number, full_name, balance, updated_at
     FROM clients WHERE is_deleted = 0
     ORDER BY updated_at DESC
     LIMIT 6"
);
$recentClients = [];
while ($row = mysqli_fetch_assoc($recentRes)) {
    $recentClients[] = $row;
}

include 'includes/header.php';
?>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Welcome back, <?= e($_SESSION['username']) ?> — <?= date('l, F j, Y') ?></div>
        </div>
        <?php if (has_permission(PERM_ADD_CLIENT)): ?>
        <a href="clients.php?open=add" class="btn btn-primary">+ New Client</a>
        <?php endif; ?>
    </div>

    <div class="page-body">
    <div class="dash-layout">

        <!-- ═══════════ LEFT / MAIN COLUMN ═══════════ -->
        <div style="display:flex;flex-direction:column;gap:18px;min-width:0">

            <!-- Stat Cards Row -->
            <div class="grid g-3">
                <div class="stat-card">
                    <div class="stat-icon green">👥</div>
                    <div class="stat-label">Active Clients</div>
                    <div class="stat-value"><?= number_format($totalClients) ?></div>
                    <div class="stat-sub">Non-deleted accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">🗄</div>
                    <div class="stat-label">Total DB Rows</div>
                    <div class="stat-value"><?= number_format($totalRows) ?></div>
                    <div class="stat-sub">Across all 3 tables</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber">💰</div>
                    <div class="stat-label">Total Balance</div>
                    <div class="stat-value sm"><?= fmt_money($totalBalance) ?></div>
                    <div class="stat-sub">Sum of all accounts</div>
                </div>
            </div>

            <!-- Chart Card (dark green) -->
            <div class="card-dark">
                <div class="chart-card">
                    <div class="chart-title">Client Registrations per Day</div>
                    <div class="chart-subtitle">Last 7 days — new client accounts created</div>
                    <div class="chart-wrap">
                        <canvas id="registrationChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Lower Row: System Log | Permission Dist | Quick Actions -->
            <div class="grid g-3">

                <!-- Left Column: News + System Log -->
                <div style="display:flex;flex-direction:column;gap:18px">
                    
                    <!-- Bank News -->
                    <div class="card">
                        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <span class="card-title">📰 Bank News</span>
                                <span class="text-sm text-muted">Latest announcements</span>
                            </div>
                            <?php if ((int)$_SESSION['permissions'] === -1): ?>
                            <button class="btn btn-ghost btn-sm" onclick="openModal('modalAddNews')">+ Add</button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body-sm" style="padding-top:10px">
                            <?php if ($newsItems): ?>
                                <?php foreach ($newsItems as $news): ?>
                                <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,.05)">
                                    <div style="font-size:13px;font-weight:600;color:var(--text-main);display:flex;justify-content:space-between">
                                        <span><?= e($news['title']) ?></span>
                                        <?php if ((int)$_SESSION['permissions'] === -1): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirmAction('Delete this news?')">
                                            <input type="hidden" name="action" value="delete_news">
                                            <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                                            <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:11px">Delete</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:12px;color:rgba(0, 0, 0, 0.6);margin:4px 0"><?= nl2br(e($news['content'])) ?></div>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= date('M j, Y', strtotime($news['created_at'])) ?> · by <?= e($news['author']) ?></div>
                                </div>
                                <?php endforeach; ?>
                                <?php if ($newsPages > 1): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                                    <a href="?np=<?= max(1, $newsPage - 1) ?>&lp=<?= $logPage ?>" class="btn btn-ghost btn-sm" <?= $newsPage <= 1 ? 'style="visibility:hidden"' : '' ?>>← Prev</a>
                                    <span class="text-sm text-muted">Page <?= $newsPage ?> of <?= $newsPages ?></span>
                                    <a href="?np=<?= min($newsPages, $newsPage + 1) ?>&lp=<?= $logPage ?>" class="btn btn-ghost btn-sm" <?= $newsPage >= $newsPages ? 'style="visibility:hidden"' : '' ?>>Next →</a>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📰</div>
                                    <div class="empty-text">No announcements yet</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- System Log (Full Access Only) -->
                    <?php if ((int)$_SESSION['permissions'] === -1): ?>
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">📋 System Log</span>
                            <span class="text-sm text-muted">Recent transactions</span>
                        </div>
                        <div class="card-body-sm" style="padding-top:10px">
                    <?php if ($logItems): ?>
                        <?php foreach ($logItems as $log): ?>
                        <div class="log-item">
                            <div class="log-dot <?= strtolower($log['type']) ?>"></div>
                            <div class="log-text">
                                <strong><?= e($log['full_name'] ?? 'Unknown') ?></strong>
                                — <?= e($log['type']) ?>
                                <?= fmt_money($log['amount']) ?>
                                <?php if ($log['type'] === 'Transfer' && $log['target_account']): ?>
                                → <?= e($log['target_account']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="log-time"><?= date('M j, H:i', strtotime($log['timestamp'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($logPages > 1): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid var(--border)">
                            <a href="?lp=<?= max(1, $logPage - 1) ?>&np=<?= $newsPage ?>" class="btn btn-ghost btn-sm" <?= $logPage <= 1 ? 'style="visibility:hidden"' : '' ?>>← Prev</a>
                            <span class="text-sm text-muted">Page <?= $logPage ?> of <?= $logPages ?></span>
                            <a href="?lp=<?= min($logPages, $logPage + 1) ?>&np=<?= $newsPage ?>" class="btn btn-ghost btn-sm" <?= $logPage >= $logPages ? 'style="visibility:hidden"' : '' ?>>Next →</a>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <div class="empty-text">No transactions yet</div>
                        </div>
                    <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div><!-- /left col stack -->

                <!-- Permission Distribution -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🛡 Permission Distribution</span>
                        <span class="text-sm text-muted"><?= $totalUsers ?> users</span>
                    </div>
                    <div class="card-body">
                        <?php if ($permDist): ?>
                            <?php foreach ($permDist as $pd): ?>
                            <?php
                                $label = $pd['perm_group'];
                                $pct   = round(($pd['cnt'] / $totalU) * 100);
                            ?>
                            <div class="perm-row">
                                <div class="perm-label"><?= e($label) ?></div>
                                <div class="perm-track">
                                    <div class="perm-fill" style="width:<?= $pct ?>%"></div>
                                </div>
                                <div class="perm-count"><?= $pd['cnt'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">🛡</div>
                                <div class="empty-text">No users found</div>
                            </div>
                        <?php endif; ?>
                        <div class="divider"></div>
                        <canvas id="permChart" height="120"></canvas>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">⚡ Quick Actions</span>
                    </div>
                    <div class="card-body">
                        <?php if (has_permission(PERM_ADD_CLIENT)): ?>
                        <a href="clients.php?open=add" class="qa-item">
                            <div class="qa-icon">➕</div>Add New Client
                        </a>
                        <?php endif; ?>
                        <?php if (has_permission(PERM_CLIENT_LIST)): ?>
                        <a href="clients.php" class="qa-item">
                            <div class="qa-icon">👥</div>View All Clients
                        </a>
                        <?php endif; ?>
                        <?php if (has_permission(PERM_TRANSACTIONS)): ?>
                        <a href="transactions.php" class="qa-item">
                            <div class="qa-icon">💸</div>New Transaction
                        </a>
                        <?php endif; ?>
                        <?php if (has_permission(PERM_MANAGE_USERS)): ?>
                        <a href="users.php" class="qa-item">
                            <div class="qa-icon">🛡</div>Manage Users
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="qa-item" style="color:#DC2626;border-color:#FEE2E2">
                            <div class="qa-icon" style="background:#FEE2E2">🔓</div>Sign Out
                        </a>
                    </div>
                </div>

            </div><!-- /lower row -->

        </div><!-- /left column -->

        <!-- ═══════════ RIGHT COLUMN ═══════════ -->
        <div style="display:flex;flex-direction:column;gap:18px">

            <div class="card" style="flex:1">
                <div class="card-header">
                    <span class="card-title">🕐 Recently Modified</span>
                    <?php if (has_permission(PERM_CLIENT_LIST)): ?>
                    <a href="clients.php" class="btn btn-ghost btn-sm">View all</a>
                    <?php endif; ?>
                </div>
                <div class="card-body-sm" style="padding-top:10px">
                    <?php if ($recentClients): ?>
                        <?php foreach ($recentClients as $rc): ?>
                        <?php $ini = strtoupper(substr($rc['full_name'], 0, 2)); ?>
                        <div class="rc-item">
                            <div class="rc-avatar"><?= e($ini) ?></div>
                            <div style="flex:1;min-width:0">
                                <div class="rc-name truncate"><?= e($rc['full_name']) ?></div>
                                <div class="rc-acc"><?= e($rc['account_number']) ?></div>
                            </div>
                            <div class="rc-balance"><?= fmt_money($rc['balance']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">👥</div>
                            <div class="empty-text">No clients yet</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mini stats -->
            <div class="card">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="stat-label">Total Users</span>
                        <span class="font-bold" style="font-size:18px"><?= $totalUsers ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <span class="stat-label">Transactions</span>
                        <span class="font-bold" style="font-size:18px"><?= number_format($totalTxns) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span class="stat-label">Avg Balance</span>
                        <span class="font-bold" style="font-size:16px;color:var(--accent)">
                            <?= $totalClients > 0 ? fmt_money($totalBalance / $totalClients) : '$0.00' ?>
                        </span>
                    </div>
                </div>
            </div>

        </div><!-- /right column -->

    </div><!-- /dash-layout -->
    </div><!-- /page-body -->
</main>
</div><!-- /app-layout -->

<!-- Chart.js init -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Registration Bar Chart ---
    const barCtx = document.getElementById('registrationChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?= $chartLabels ?>,
                datasets: [{
                    label: 'New Clients',
                    data: <?= $chartData ?>,
                    backgroundColor: 'rgba(74,124,89,0.85)',
                    borderColor:     'rgba(74,124,89,1)',
                    borderWidth: 0,
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,.75)',
                        callbacks: {
                            label: ctx => ' ' + ctx.parsed.y + ' client(s)'
                        }
                    }
                },
                scales: {
                    x: {
                        grid:  { color: 'rgba(255,255,255,.06)' },
                        ticks: { color: 'rgba(255,255,255,.55)', font: { size: 11 } }
                    },
                    y: {
                        grid:  { color: 'rgba(255,255,255,.06)' },
                        ticks: { color: 'rgba(255,255,255,.55)', font: { size: 11 }, stepSize: 1 },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- Permission Pie Chart ---
    const pieCtx = document.getElementById('permChart');
    if (pieCtx) {
        const permData = <?= json_encode(array_map(function($p) { return (int)$p['cnt']; }, $permDist)) ?>;
        const permLabels = <?= json_encode(array_map(function($p) { return $p['perm_group']; }, $permDist)) ?>;
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: permLabels,
                datasets: [{
                    data: permData,
                    backgroundColor: ['#1D3B26','#4A7C59','#8BB89A','#C8DDD0'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, boxWidth: 10, padding: 12 }
                    }
                }
            }
        });
    }
});
</script>

<!-- Add News Modal -->
<div class="modal-overlay" id="modalAddNews">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title">📰 Post Announcement</span>
            <button class="modal-close" data-close-modal="modalAddNews">×</button>
        </div>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action" value="add_news">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control dark" required placeholder="e.g. Scheduled Maintenance">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Content</label>
                    <textarea name="content" class="form-control dark" rows="4" required placeholder="Write your message here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalAddNews">Cancel</button>
                <button type="submit" class="btn btn-primary">Post News</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
