<?php
// ============================================================
//  clients.php — Client Management Module
//  Prototype Bank
// ============================================================
define('PAGE_TITLE', 'Clients');
require_once 'config/db.php';
require_once 'config/auth_check.php';
require_permission(PERM_CLIENT_LIST);

$msg  = '';
$type = 'success';

// ── POST Handler (PRG Pattern) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── ADD CLIENT ──────────────────────────────────────────
    if ($action === 'add' && has_permission(PERM_ADD_CLIENT)) {
        $acc  = trim($_POST['account_number'] ?? '');
        $pin  = trim($_POST['pin_code']       ?? '');
        $name = trim($_POST['full_name']      ?? '');
        $ph   = trim($_POST['phone']          ?? '');
        $bal  = (float)($_POST['balance']     ?? 0);

        if (!$acc || !$pin || !$name) {
            $msg = 'Account number, PIN, and full name are required.'; $type = 'error';
        } else {
            // Duplicate check
            $chk = mysqli_prepare($conn, "SELECT 1 FROM clients WHERE account_number = ?");
            mysqli_stmt_bind_param($chk, 's', $acc);
            mysqli_stmt_execute($chk);
            $chkRes = mysqli_stmt_get_result($chk);
            $exists = (bool)mysqli_fetch_row($chkRes);
            mysqli_stmt_close($chk);

            if ($exists) {
                $msg = "Account number \"$acc\" already exists."; $type = 'error';
            } else {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO clients (account_number, pin_code, full_name, phone, balance)
                     VALUES (?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, 'ssssd', $acc, $pin, $name, $ph, $bal);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $msg = "Client \"$name\" added successfully.";
            }
        }
        header("Location: clients.php?msg=" . urlencode($msg) . "&type=$type");
        exit;
    }

    // ── UPDATE CLIENT ───────────────────────────────────────
    if ($action === 'edit' && has_permission(PERM_UPD_CLIENT)) {
        $acc  = trim($_POST['account_number'] ?? '');
        $pin  = trim($_POST['pin_code']       ?? '');
        $name = trim($_POST['full_name']      ?? '');
        $ph   = trim($_POST['phone']          ?? '');
        $bal  = (float)($_POST['balance']     ?? 0);

        if (!$acc || !$name) {
            $msg = 'Missing required fields.'; $type = 'error';
        } else {
            $stmt = mysqli_prepare($conn,
                "UPDATE clients SET pin_code=?, full_name=?, phone=?, balance=?
                 WHERE account_number=? AND is_deleted=0"
            );
            mysqli_stmt_bind_param($stmt, 'sssds', $pin, $name, $ph, $bal, $acc);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            $msg = $affected > 0 ? "Client \"$acc\" updated." : "No changes made.";
        }
        header("Location: clients.php?msg=" . urlencode($msg) . "&type=$type");
        exit;
    }

    // ── DELETE CLIENT (permanent) ──────────────────────────
    if ($action === 'delete' && has_permission(PERM_DEL_CLIENT)) {
        $acc = trim($_POST['account_number'] ?? '');
        if ($acc) {
            mysqli_begin_transaction($conn);
            try {
                // Verify the client exists before attempting deletion
                $chk = mysqli_prepare($conn, "SELECT full_name FROM clients WHERE account_number = ? LIMIT 1");
                mysqli_stmt_bind_param($chk, 's', $acc);
                mysqli_stmt_execute($chk);
                $chkRes = mysqli_stmt_get_result($chk);
                $clientRow = mysqli_fetch_assoc($chkRes);
                mysqli_stmt_close($chk);

                if (!$clientRow) {
                    throw new Exception('Client not found.');
                }

                // Delete associated transactions first (FK: transactions → clients ON DELETE RESTRICT)
                $delTxn = mysqli_prepare($conn, "DELETE FROM transactions WHERE account_number = ?");
                mysqli_stmt_bind_param($delTxn, 's', $acc);
                mysqli_stmt_execute($delTxn);
                mysqli_stmt_close($delTxn);

                // Now permanently delete the client row
                $delClient = mysqli_prepare($conn, "DELETE FROM clients WHERE account_number = ?");
                mysqli_stmt_bind_param($delClient, 's', $acc);
                mysqli_stmt_execute($delClient);
                mysqli_stmt_close($delClient);

                mysqli_commit($conn);
                $msg = "Client \"$acc\" and all associated transactions have been permanently deleted.";
            } catch (Exception $ex) {
                mysqli_rollback($conn);
                $msg = 'Deletion failed: ' . $ex->getMessage();
                $type = 'error';
            }
        } else {
            $msg = 'Invalid account.'; $type = 'error';
        }
        header("Location: clients.php?msg=" . urlencode($msg) . "&type=$type");
        exit;
    }
}

// ── Fetch clients ────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$openAdd = isset($_GET['open']) && $_GET['open'] === 'add';

$sql = "SELECT account_number, pin_code, full_name, phone, balance, updated_at
        FROM clients WHERE is_deleted = 0";
if ($search !== '') {
    $safe = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (account_number LIKE '%$safe%' OR full_name LIKE '%$safe%' OR phone LIKE '%$safe%')";
}
$sql .= " ORDER BY updated_at DESC";

$clientsRes = mysqli_query($conn, $sql);
$clients = [];
while ($row = mysqli_fetch_assoc($clientsRes)) {
    $clients[] = $row;
}

include 'includes/header.php';
?>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <div class="page-title">Clients</div>
            <div class="page-subtitle"><?= number_format(count($clients)) ?> active account<?= count($clients) !== 1 ? 's' : '' ?></div>
        </div>
        <?php if (has_permission(PERM_ADD_CLIENT)): ?>
        <button class="btn btn-primary" onclick="openModal('modalAddClient')">+ Add Client</button>
        <?php endif; ?>
    </div>

    <div class="page-body">

        <!-- Search & filter bar -->
        <div class="page-actions">
            <?php if (has_permission(PERM_FIND_CLIENT)): ?>
            <form method="GET" action="clients.php" style="flex:1;max-width:360px">
                <div class="search-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="q" id="tableSearch" placeholder="Search by name, account, or phone…"
                           value="<?= e($search) ?>">
                    <?php if ($search): ?>
                    <a href="clients.php" style="color:var(--text-muted);font-size:18px;line-height:1;text-decoration:none">×</a>
                    <?php endif; ?>
                </div>
            </form>
            <?php else: ?>
            <div></div>
            <?php endif; ?>
            <span class="text-muted text-sm"><?= date('F j, Y') ?></span>
        </div>

        <!-- Clients Table -->
        <div class="card">
            <div class="table-wrap">
                <table id="mainTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Account Number</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Balance</th>
                            <th>Last Modified</th>
                            <?php if (has_permission(PERM_UPD_CLIENT) || has_permission(PERM_DEL_CLIENT)): ?>
                            <th style="text-align:center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($clients): ?>
                        <?php foreach ($clients as $i => $c): ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $i + 1 ?></td>
                            <td class="font-mono"><?= e($c['account_number']) ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="rc-avatar" style="width:30px;height:30px;font-size:11px">
                                        <?= strtoupper(substr($c['full_name'], 0, 2)) ?>
                                    </div>
                                    <strong><?= e($c['full_name']) ?></strong>
                                </div>
                            </td>
                            <td><?= e($c['phone']) ?></td>
                            <td>
                                <span class="money-pos"><?= fmt_money($c['balance']) ?></span>
                            </td>
                            <td class="text-muted text-sm"><?= date('M j, Y', strtotime($c['updated_at'])) ?></td>
                            <?php if (has_permission(PERM_UPD_CLIENT) || has_permission(PERM_DEL_CLIENT)): ?>
                            <td style="text-align:center;white-space:nowrap">
                                <?php if (has_permission(PERM_UPD_CLIENT)): ?>
                                <button class="btn btn-ghost btn-icon"
                                    title="Edit"
                                    onclick='setupEditClient(<?= json_encode([
                                        "account_number" => $c["account_number"],
                                        "pin_code"       => $c["pin_code"],
                                        "full_name"      => $c["full_name"],
                                        "phone"          => $c["phone"],
                                        "balance"        => $c["balance"],
                                    ]) ?>)'>✏️</button>
                                <?php endif; ?>
                                <?php if (has_permission(PERM_DEL_CLIENT)): ?>
                                <button class="btn btn-ghost btn-icon"
                                    title="Delete"
                                    onclick="setupDeleteClient('<?= e(addslashes($c["account_number"])) ?>','<?= e(addslashes($c["full_name"])) ?>')">🗑️</button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">👥</div>
                                    <div class="empty-text">
                                        <?= $search ? "No clients match \"$search\"." : 'No clients yet. Add one to get started.' ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /page-body -->
</main>
</div><!-- /app-layout -->

<!-- ══════════ MODAL: Add Client ══════════ -->
<?php if (has_permission(PERM_ADD_CLIENT)): ?>
<div class="modal-overlay" id="modalAddClient">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title">➕ Add New Client</span>
            <button class="modal-close" data-close-modal="modalAddClient">×</button>
        </div>
        <form method="POST" action="clients.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_account_number">Account Number <span style="color:red">*</span></label>
                        <input type="text" id="add_account_number" name="account_number" class="form-control"
                               placeholder="e.g. ACC-20001" required maxlength="20">
                        <div class="form-hint">Must be unique — will be used as primary key.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_pin_code">PIN Code <span style="color:red">*</span></label>
                        <input type="text" id="add_pin_code" name="pin_code" class="form-control"
                               placeholder="4-digit PIN" required maxlength="10">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_full_name">Full Name <span style="color:red">*</span></label>
                    <input type="text" id="add_full_name" name="full_name" class="form-control"
                           placeholder="Client's full name" required maxlength="100">
                </div>
                <div class="form-row">
                    <div class="form-group mb-0">
                        <label class="form-label" for="add_phone">Phone Number</label>
                        <input type="text" id="add_phone" name="phone" class="form-control"
                               placeholder="+1-555-0000" maxlength="20">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="add_balance">Initial Balance ($)</label>
                        <input type="number" id="add_balance" name="balance" class="form-control"
                               placeholder="0.00" min="0" step="0.01" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalAddClient">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Client</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ══════════ MODAL: Edit Client ══════════ -->
<?php if (has_permission(PERM_UPD_CLIENT)): ?>
<div class="modal-overlay" id="modalEditClient">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title">✏️ Edit Client — <span id="editClientAccLabel" style="color:var(--accent)"></span></span>
            <button class="modal-close" data-close-modal="modalEditClient">×</button>
        </div>
        <form method="POST" action="clients.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="account_number" id="edit_account_number">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_pin_code">PIN Code</label>
                    <input type="text" id="edit_pin_code" name="pin_code" class="form-control" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_full_name">Full Name <span style="color:red">*</span></label>
                    <input type="text" id="edit_full_name" name="full_name" class="form-control" required maxlength="100">
                </div>
                <div class="form-row">
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_phone">Phone Number</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control" maxlength="20">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_balance">Balance ($)</label>
                        <input type="number" id="edit_balance" name="balance" class="form-control" min="0" step="0.01">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalEditClient">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Client</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ══════════ MODAL: Delete Confirm ══════════ -->
<?php if (has_permission(PERM_DEL_CLIENT)): ?>
<div class="modal-overlay" id="modalDeleteClient">
    <div class="modal modal-sm">
        <div class="modal-header">
            <span class="modal-title">🗑️ Delete Client</span>
            <button class="modal-close" data-close-modal="modalDeleteClient">×</button>
        </div>
        <div class="modal-body">
            <p style="font-size:14px;color:var(--text-dark);margin-bottom:12px">
                Are you sure you want to permanently delete the following client?
            </p>
            <div style="background:var(--bg);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:14px">
                <strong id="delClientName" style="font-size:14px"></strong>
            </div>
            <div class="alert alert-warning" style="margin-bottom:0">
                <span style="flex-shrink:0">⚠</span>
                <div>
                    <strong>This action cannot be undone.</strong><br>
                    The client record and all associated transactions will be permanently removed from the database.
                </div>
            </div>
        </div>
        <form method="POST" action="clients.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="account_number" id="delAccountNumber">
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalDeleteClient">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Client</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
// Auto-open Add modal if coming from dashboard quick action
if ($openAdd): ?>
<script>document.addEventListener('DOMContentLoaded', () => openModal('modalAddClient'));</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
