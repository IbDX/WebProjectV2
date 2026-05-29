<?php
// ============================================================
//  users.php — User Management Module
//  Prototype Bank
//  Requires: PERM_MANAGE_USERS (32)
// ============================================================
define('PAGE_TITLE', 'Users');
require_once 'config/db.php';
require_once 'config/auth_check.php';
require_permission(PERM_MANAGE_USERS);

$msg  = '';
$type = 'success';

// ── POST Handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── ADD USER ────────────────────────────────────────────
    if ($action === 'add') {
        $uname = trim($_POST['username'] ?? '');
        $pass  = trim($_POST['password'] ?? '');
        $full  = (int)($_POST['perm_full'] ?? 0);
        $perms = 0;

        if ($full) {
            $perms = PERM_FULL; // -1
        } else {
            foreach ([PERM_CLIENT_LIST, PERM_ADD_CLIENT, PERM_DEL_CLIENT,
                      PERM_UPD_CLIENT, PERM_FIND_CLIENT, PERM_MANAGE_USERS,
                      PERM_TRANSACTIONS] as $bit) {
                if (!empty($_POST['perm_' . $bit])) $perms |= $bit;
            }
        }

        if (!$uname || !$pass) {
            $msg = 'Username and password are required.'; $type = 'error';
        } else {
            // Duplicate check
            $chk = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
            $chk->bind_param('s', $uname);
            $chk->execute();
            $exists = (bool)$chk->get_result()->fetch_row();
            $chk->close();

            if ($exists) {
                $msg = "Username \"$uname\" already exists."; $type = 'error';
            } else {
                $hashed = md5($pass);
                $stmt   = $conn->prepare(
                    "INSERT INTO users (username, password, permissions) VALUES (?, ?, ?)"
                );
                $stmt->bind_param('ssi', $uname, $hashed, $perms);
                $stmt->execute();
                $stmt->close();
                $msg = "User \"$uname\" created successfully.";
            }
        }
        header("Location: users.php?msg=" . urlencode($msg) . "&type=$type");
        exit;
    }

    // ── UPDATE USER ─────────────────────────────────────────
    if ($action === 'edit') {
        $uname = trim($_POST['username'] ?? '');

        // Protect full-access users from modification
        $chk = $conn->prepare("SELECT permissions FROM users WHERE username=? AND is_deleted=0");
        $chk->bind_param('s', $uname);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$existing) {
            $msg = "User not found."; $type = 'error';
        } elseif ((int)$existing['permissions'] === PERM_FULL) {
            $msg = "Cannot modify a Full Access user."; $type = 'error';
        } else {
            $full  = (int)($_POST['perm_full'] ?? 0);
            $perms = 0;
            if ($full) {
                $perms = PERM_FULL;
            } else {
                foreach ([PERM_CLIENT_LIST, PERM_ADD_CLIENT, PERM_DEL_CLIENT,
                          PERM_UPD_CLIENT, PERM_FIND_CLIENT, PERM_MANAGE_USERS,
                          PERM_TRANSACTIONS] as $bit) {
                    if (!empty($_POST['perm_' . $bit])) $perms |= $bit;
                }
            }

            // Optionally update password
            $newPass = trim($_POST['password'] ?? '');
            if ($newPass !== '') {
                $hashed = md5($newPass);
                $stmt   = $conn->prepare("UPDATE users SET password=?, permissions=? WHERE username=?");
                $stmt->bind_param('sis', $hashed, $perms, $uname);
            } else {
                $stmt = $conn->prepare("UPDATE users SET permissions=? WHERE username=?");
                $stmt->bind_param('is', $perms, $uname);
            }
            $stmt->execute();
            $stmt->close();
            $msg = "User \"$uname\" updated.";
        }
        header("Location: users.php?msg=" . urlencode($msg) . "&type=$type");
        exit;
    }

    // ── DELETE USER (logical) ───────────────────────────────
    if ($action === 'delete') {
        $uname = trim($_POST['username'] ?? '');

        // Protect full-access users
        $chk = $conn->prepare("SELECT permissions FROM users WHERE username=? AND is_deleted=0");
        $chk->bind_param('s', $uname);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$existing) {
            $msg = "User not found."; $type = 'error';
        } elseif ((int)$existing['permissions'] === PERM_FULL) {
            $msg = "Cannot delete a Full Access user."; $type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE users SET is_deleted=1 WHERE username=? AND is_deleted=0");
            $stmt->bind_param('s', $uname);
            $stmt->execute();
            $stmt->close();
            $msg = "User \"$uname\" removed (logical delete).";
        }
        header("Location: users.php?msg=" . urlencode($msg) . "&type=$type");
        exit;
    }
}

// ── Fetch users ──────────────────────────────────────────────
$users = $conn->query(
    "SELECT username, permissions, created_at FROM users WHERE is_deleted=0 ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$permMap = [
    PERM_CLIENT_LIST  => 'View Clients',
    PERM_ADD_CLIENT   => 'Add Client',
    PERM_DEL_CLIENT   => 'Delete Client',
    PERM_UPD_CLIENT   => 'Update Client',
    PERM_FIND_CLIENT  => 'Find Client',
    PERM_MANAGE_USERS => 'Manage Users',
    PERM_TRANSACTIONS => 'Transactions',
];

include 'includes/header.php';
?>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <div class="page-title">User Management</div>
            <div class="page-subtitle"><?= count($users) ?> system user<?= count($users) !== 1 ? 's' : '' ?></div>
        </div>
        <button class="btn btn-primary" onclick="openModal('modalAddUser')">+ Add User</button>
    </div>

    <div class="page-body">

        <div class="page-actions">
            <div class="search-wrap" style="max-width:320px">
                <span class="search-icon">🔍</span>
                <input type="text" id="tableSearch" placeholder="Search by username…">
            </div>
            <div class="alert alert-info mb-0" style="margin:0;font-size:12px;padding:8px 12px">
                🔒 Full Access users cannot be edited or deleted.
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table id="mainTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Permissions</th>
                            <th>Created</th>
                            <th style="text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $i => $u): ?>
                        <?php $isFull = ((int)$u['permissions'] === PERM_FULL); ?>
                        <tr>
                            <td class="text-muted text-sm"><?= $i + 1 ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="user-avatar" style="width:30px;height:30px;font-size:11px;border:none">
                                        <?= strtoupper(substr($u['username'], 0, 2)) ?>
                                    </div>
                                    <strong><?= e($u['username']) ?></strong>
                                    <?php if ($isFull): ?>
                                    <span class="badge badge-full" style="font-size:10px">⭐ Owner</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="max-width:320px">
                                <?= perm_labels((int)$u['permissions']) ?>
                            </td>
                            <td class="text-muted text-sm"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td style="text-align:center;white-space:nowrap">
                                <?php if ($isFull): ?>
                                <span class="text-muted text-sm">Protected</span>
                                <?php else: ?>
                                <button class="btn btn-ghost btn-icon"
                                    title="Edit User"
                                    onclick='setupEditUser(<?= json_encode([
                                        "username"    => $u["username"],
                                        "permissions" => $u["permissions"],
                                    ]) ?>)'>✏️</button>
                                <button class="btn btn-ghost btn-icon"
                                    title="Delete User"
                                    onclick="setupDeleteUser('<?= e(addslashes($u["username"])) ?>')">🗑️</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">👤</div>
                                    <div class="empty-text">No users found.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
</div>

<!-- ══════════ MODAL: Add User ══════════ -->
<div class="modal-overlay" id="modalAddUser">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title">👤 Add New User</span>
            <button class="modal-close" data-close-modal="modalAddUser">×</button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_username">Username <span style="color:red">*</span></label>
                        <input type="text" id="add_username" name="username" class="form-control"
                               placeholder="Unique username" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_password">Password <span style="color:red">*</span></label>
                        <input type="password" id="add_password" name="password" class="form-control"
                               placeholder="Set a password" required>
                        <div class="form-hint">Stored as MD5 hash (prototype).</div>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="form-label" style="margin-bottom:12px">Permissions</div>

                <!-- Full Access Toggle -->
                <div class="cb-item" style="margin-bottom:12px;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px">
                    <input type="checkbox" id="permFullAccess" name="perm_full" value="1">
                    <label for="permFullAccess" style="font-weight:700;color:var(--accent)">
                        ⭐ Full Access (unrestricted — overrides all below)
                    </label>
                </div>

                <div class="cb-group" style="padding-left:4px">
                    <?php foreach ($permMap as $bit => $label): ?>
                    <div class="cb-item">
                        <input type="checkbox" id="add_perm_<?= $bit ?>" name="perm_<?= $bit ?>"
                               value="1" class="perm-checkbox">
                        <label for="add_perm_<?= $bit ?>"><?= e($label) ?>
                            <span class="text-muted" style="font-size:11px">(bit <?= $bit ?>)</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalAddUser">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════ MODAL: Edit User ══════════ -->
<div class="modal-overlay" id="modalEditUser">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title">✏️ Edit User — <span id="editUserLabel" style="color:var(--accent)"></span></span>
            <button class="modal-close" data-close-modal="modalEditUser">×</button>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="username" id="edit_username_input">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_password">New Password</label>
                    <input type="password" id="edit_password" name="password" class="form-control"
                           placeholder="Leave blank to keep current password">
                </div>
                <div class="divider"></div>
                <div class="form-label" style="margin-bottom:12px">Permissions</div>

                <div class="cb-item" style="margin-bottom:12px;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px">
                    <input type="checkbox" id="editPermFullAccess" name="perm_full" value="1">
                    <label for="editPermFullAccess" style="font-weight:700;color:var(--accent)">
                        ⭐ Full Access (unrestricted)
                    </label>
                </div>

                <div class="cb-group" style="padding-left:4px">
                    <?php foreach ($permMap as $bit => $label): ?>
                    <div class="cb-item">
                        <input type="checkbox" id="edit_perm_<?= $bit ?>" name="perm_<?= $bit ?>"
                               value="<?= $bit ?>" class="edit-perm-cb">
                        <label for="edit_perm_<?= $bit ?>"><?= e($label) ?>
                            <span class="text-muted" style="font-size:11px">(bit <?= $bit ?>)</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalEditUser">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════ MODAL: Delete User ══════════ -->
<div class="modal-overlay" id="modalDeleteUser">
    <div class="modal modal-sm">
        <div class="modal-header">
            <span class="modal-title">🗑️ Confirm Delete</span>
            <button class="modal-close" data-close-modal="modalDeleteUser">×</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-warning">
                ⚠ This is a <strong>logical delete</strong> — the user record will be hidden.
            </div>
            <p style="font-size:14px">Delete user <strong id="delUserLabel"></strong>?</p>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="username" id="delUsernameInput">
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal="modalDeleteUser">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
