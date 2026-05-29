<?php
// ============================================================
//  transactions.php — Deposit / Withdraw / Transfer Module
//  Prototype Bank
//  Requires: PERM_TRANSACTIONS (64) or PERM_FULL (-1)
// ============================================================
define('PAGE_TITLE', 'Transactions');
require_once 'config/db.php';
require_once 'config/auth_check.php';
require_permission(PERM_TRANSACTIONS);

$formError   = '';
$formSuccess = '';
$activeTab   = 0; // 0=Deposit 1=Withdraw 2=Transfer

// ── POST Handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ─── DEPOSIT ───────────────────────────────────────────
    if ($action === 'deposit') {
        $activeTab = 0;
        $acc    = trim($_POST['account_number'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        if (!$acc || $amount <= 0) {
            $formError = 'Please provide a valid account and a positive amount.';
        } else {
            $stmt = $conn->prepare("SELECT full_name FROM clients WHERE account_number=? AND is_deleted=0");
            $stmt->bind_param('s', $acc);
            $stmt->execute();
            $client = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$client) {
                $formError = "Account \"$acc\" not found or is inactive.";
            } else {
                // Update balance
                $upd = $conn->prepare("UPDATE clients SET balance = balance + ? WHERE account_number = ?");
                $upd->bind_param('ds', $amount, $acc);
                $upd->execute();
                $upd->close();
                // Insert transaction
                $ins = $conn->prepare("INSERT INTO transactions (account_number, type, amount) VALUES (?, 'Deposit', ?)");
                $ins->bind_param('sd', $acc, $amount);
                $ins->execute();
                $ins->close();

                header("Location: transactions.php?msg=" . urlencode("Deposit of " . fmt_money($amount) . " to {$client['full_name']} successful.") . "&type=success");
                exit;
            }
        }
    }

    // ─── WITHDRAW ──────────────────────────────────────────
    elseif ($action === 'withdraw') {
        $activeTab = 1;
        $acc    = trim($_POST['account_number'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);

        if (!$acc || $amount <= 0) {
            $formError = 'Please provide a valid account and a positive amount.';
        } else {
            $stmt = $conn->prepare("SELECT full_name, balance FROM clients WHERE account_number=? AND is_deleted=0");
            $stmt->bind_param('s', $acc);
            $stmt->execute();
            $client = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$client) {
                $formError = "Account \"$acc\" not found or is inactive.";
            } elseif ($client['balance'] < $amount) {
                $formError = "Insufficient balance. Available: " . fmt_money($client['balance']);
            } else {
                $upd = $conn->prepare("UPDATE clients SET balance = balance - ? WHERE account_number = ?");
                $upd->bind_param('ds', $amount, $acc);
                $upd->execute();
                $upd->close();

                $ins = $conn->prepare("INSERT INTO transactions (account_number, type, amount) VALUES (?, 'Withdraw', ?)");
                $ins->bind_param('sd', $acc, $amount);
                $ins->execute();
                $ins->close();

                header("Location: transactions.php?msg=" . urlencode("Withdrawal of " . fmt_money($amount) . " from {$client['full_name']} successful.") . "&type=success");
                exit;
            }
        }
    }

    // ─── TRANSFER (Atomic) ─────────────────────────────────
    elseif ($action === 'transfer') {
        $activeTab = 2;
        $sender   = trim($_POST['sender_account']   ?? '');
        $receiver = trim($_POST['receiver_account'] ?? '');
        $amount   = (float)($_POST['amount']        ?? 0);

        if (!$sender || !$receiver || $amount <= 0) {
            $formError = 'Please fill in all transfer fields with a positive amount.';
        } elseif ($sender === $receiver) {
            $formError = 'Sender and receiver accounts cannot be the same.';
        } else {
            $conn->begin_transaction();
            try {
                // Lock sender row
                $stmt = $conn->prepare(
                    "SELECT full_name, balance FROM clients
                     WHERE account_number = ? AND is_deleted = 0
                     FOR UPDATE"
                );
                $stmt->bind_param('s', $sender);
                $stmt->execute();
                $senderData = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$senderData) throw new Exception("Sender account \"$sender\" not found.");
                if ((float)$senderData['balance'] < $amount) {
                    throw new Exception("Insufficient sender balance. Available: " . fmt_money($senderData['balance']));
                }

                // Lock receiver row
                $stmt = $conn->prepare(
                    "SELECT full_name FROM clients
                     WHERE account_number = ? AND is_deleted = 0
                     FOR UPDATE"
                );
                $stmt->bind_param('s', $receiver);
                $stmt->execute();
                $receiverData = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$receiverData) throw new Exception("Receiver account \"$receiver\" not found.");

                // Deduct from sender
                $stmt = $conn->prepare("UPDATE clients SET balance = balance - ? WHERE account_number = ?");
                $stmt->bind_param('ds', $amount, $sender);
                $stmt->execute();
                $stmt->close();

                // Add to receiver
                $stmt = $conn->prepare("UPDATE clients SET balance = balance + ? WHERE account_number = ?");
                $stmt->bind_param('ds', $amount, $receiver);
                $stmt->execute();
                $stmt->close();

                // Insert single transaction record (from sender's perspective)
                $stmt = $conn->prepare(
                    "INSERT INTO transactions (account_number, type, amount, target_account)
                     VALUES (?, 'Transfer', ?, ?)"
                );
                $stmt->bind_param('sds', $sender, $amount, $receiver);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $successMsg = "Transfer of " . fmt_money($amount) . " from {$senderData['full_name']} to {$receiverData['full_name']} completed.";
                header("Location: transactions.php?msg=" . urlencode($successMsg) . "&type=success");
                exit;

            } catch (Exception $ex) {
                $conn->rollback();
                $formError = $ex->getMessage();
            }
        }
    }
}

// ── Transaction history ──────────────────────────────────────
$histRes = $conn->query(
    "SELECT t.id, t.account_number, t.type, t.amount, t.target_account, t.timestamp,
            c.full_name
     FROM transactions t
     LEFT JOIN clients c ON t.account_number = c.account_number
     ORDER BY t.timestamp DESC
     LIMIT 50"
);
$history = $histRes->fetch_all(MYSQLI_ASSOC);

// ── Client list for account dropdowns ────────────────────────
$clientRes  = $conn->query(
    "SELECT account_number, full_name FROM clients WHERE is_deleted=0 ORDER BY full_name ASC"
);
$clientList = $clientRes ? $clientRes->fetch_all(MYSQLI_ASSOC) : [];

// Pre-select chosen account when form re-renders after a validation error
$selDep = ($activeTab === 0) ? ($_POST['account_number']   ?? '') : '';
$selWit = ($activeTab === 1) ? ($_POST['account_number']   ?? '') : '';
$selSnd = ($activeTab === 2) ? ($_POST['sender_account']   ?? '') : '';
$selRcv = ($activeTab === 2) ? ($_POST['receiver_account'] ?? '') : '';

$typeIcons = ['Deposit' => '⬆', 'Withdraw' => '⬇', 'Transfer' => '↔'];
$typeCss   = ['Deposit' => 'deposit', 'Withdraw' => 'withdraw', 'Transfer' => 'transfer'];

include 'includes/header.php';
?>
<div class="app-layout">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div>
            <div class="page-title">Transactions</div>
            <div class="page-subtitle">Deposit, withdraw, or transfer between accounts</div>
        </div>
    </div>

    <div class="page-body">
    <div class="grid g-3-2" style="align-items:start">

        <!-- ══ LEFT: Dark green action card ══ -->
        <div class="card-dark" style="border-radius:var(--radius)">
            <div style="padding:24px">

                <!-- Tab selector -->
                <div class="tab-list" data-target="txnTabs">
                    <button class="tab-btn">⬆ Deposit</button>
                    <button class="tab-btn">⬇ Withdraw</button>
                    <button class="tab-btn">↔ Transfer</button>
                </div>

                <?php if ($formError): ?>
                <div style="background:rgba(220,38,38,.15);border:1px solid rgba(220,38,38,.35);border-radius:9px;
                            padding:10px 14px;color:#FCA5A5;font-size:13px;margin-bottom:16px;display:flex;gap:8px;align-items:flex-start">
                    <span style="flex-shrink:0">⚠</span>
                    <span><?= e($formError) ?></span>
                </div>
                <?php endif; ?>

                <div data-tab-group="txnTabs">

                    <!-- ─ DEPOSIT ─ -->
                    <div class="tab-panel">
                        <form method="POST" action="transactions.php">
                            <input type="hidden" name="action" value="deposit">
                            <div class="form-group">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Account</label>
                                <div class="acc-combo">
                                    <div class="acc-combo-trigger">
                                        <input type="text" class="acc-combo-search" placeholder="Type to search account or name…" autocomplete="off">
                                        <span class="acc-combo-arrow">▾</span>
                                    </div>
                                    <input type="hidden" name="account_number" value="<?= e($selDep) ?>">
                                    <div class="acc-combo-list">
                                        <?php foreach ($clientList as $cl): ?>
                                        <div class="acc-combo-item"
                                             data-value="<?= e($cl['account_number']) ?>"
                                             data-label="<?= e($cl['account_number'] . ' — ' . $cl['full_name']) ?>">
                                            <code class="acc-combo-num"><?= e($cl['account_number']) ?></code>
                                            <span class="acc-combo-name"><?= e($cl['full_name']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Amount ($)</label>
                                <input type="number" name="amount" class="form-control dark"
                                       placeholder="0.00" min="0.01" step="0.01" required
                                       value="<?= $activeTab === 0 ? e($_POST['amount'] ?? '') : '' ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:18px;
                                background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.25);
                                color:#fff;backdrop-filter:blur(4px)">
                                ⬆ Confirm Deposit
                            </button>
                        </form>
                    </div>

                    <!-- ─ WITHDRAW ─ -->
                    <div class="tab-panel">
                        <form method="POST" action="transactions.php">
                            <input type="hidden" name="action" value="withdraw">
                            <div class="form-group">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Account</label>
                                <div class="acc-combo">
                                    <div class="acc-combo-trigger">
                                        <input type="text" class="acc-combo-search" placeholder="Type to search account or name…" autocomplete="off">
                                        <span class="acc-combo-arrow">▾</span>
                                    </div>
                                    <input type="hidden" name="account_number" value="<?= e($selWit) ?>">
                                    <div class="acc-combo-list">
                                        <?php foreach ($clientList as $cl): ?>
                                        <div class="acc-combo-item"
                                             data-value="<?= e($cl['account_number']) ?>"
                                             data-label="<?= e($cl['account_number'] . ' — ' . $cl['full_name']) ?>">
                                            <code class="acc-combo-num"><?= e($cl['account_number']) ?></code>
                                            <span class="acc-combo-name"><?= e($cl['full_name']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Amount ($)</label>
                                <input type="number" name="amount" class="form-control dark"
                                       placeholder="0.00" min="0.01" step="0.01" required
                                       value="<?= $activeTab === 1 ? e($_POST['amount'] ?? '') : '' ?>">
                                <div class="form-hint" style="color:rgba(255,255,255,.35);margin-top:5px">
                                    Withdrawal will be rejected if balance is insufficient.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:18px;
                                background:rgba(220,38,38,.2);border:1.5px solid rgba(220,38,38,.4);
                                color:#FCA5A5">
                                ⬇ Confirm Withdrawal
                            </button>
                        </form>
                    </div>

                    <!-- ─ TRANSFER ─ -->
                    <div class="tab-panel">
                        <form method="POST" action="transactions.php">
                            <input type="hidden" name="action" value="transfer">
                            <div class="form-group">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Sender Account</label>
                                <div class="acc-combo">
                                    <div class="acc-combo-trigger">
                                        <input type="text" class="acc-combo-search" placeholder="Type to search account or name…" autocomplete="off">
                                        <span class="acc-combo-arrow">▾</span>
                                    </div>
                                    <input type="hidden" name="sender_account" value="<?= e($selSnd) ?>">
                                    <div class="acc-combo-list">
                                        <?php foreach ($clientList as $cl): ?>
                                        <div class="acc-combo-item"
                                             data-value="<?= e($cl['account_number']) ?>"
                                             data-label="<?= e($cl['account_number'] . ' — ' . $cl['full_name']) ?>">
                                            <code class="acc-combo-num"><?= e($cl['account_number']) ?></code>
                                            <span class="acc-combo-name"><?= e($cl['full_name']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Receiver Account</label>
                                <div class="acc-combo">
                                    <div class="acc-combo-trigger">
                                        <input type="text" class="acc-combo-search" placeholder="Type to search account or name…" autocomplete="off">
                                        <span class="acc-combo-arrow">▾</span>
                                    </div>
                                    <input type="hidden" name="receiver_account" value="<?= e($selRcv) ?>">
                                    <div class="acc-combo-list">
                                        <?php foreach ($clientList as $cl): ?>
                                        <div class="acc-combo-item"
                                             data-value="<?= e($cl['account_number']) ?>"
                                             data-label="<?= e($cl['account_number'] . ' — ' . $cl['full_name']) ?>">
                                            <code class="acc-combo-num"><?= e($cl['account_number']) ?></code>
                                            <span class="acc-combo-name"><?= e($cl['full_name']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label" style="color:rgba(255,255,255,.7)">Amount ($)</label>
                                <input type="number" name="amount" class="form-control dark"
                                       placeholder="0.00" min="0.01" step="0.01" required
                                       value="<?= $activeTab === 2 ? e($_POST['amount'] ?? '') : '' ?>">
                                <div class="form-hint" style="color:rgba(255,255,255,.35);margin-top:5px">
                                    Transfer is atomic — rolled back on any failure.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:18px;
                                background:rgba(37,99,235,.25);border:1.5px solid rgba(37,99,235,.4);
                                color:#93C5FD">
                                ↔ Confirm Transfer
                            </button>
                        </form>
                    </div>

                </div><!-- /tab-group -->

                <!-- Quick balance lookup -->
                <div class="divider" style="border-color:rgba(255,255,255,.1);margin-top:20px"></div>
                <div style="background:rgba(255,255,255,.06);border-radius:9px;padding:14px">
                    <div style="color:rgba(255,255,255,.55);font-size:11px;font-weight:700;
                                text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px">
                        Quick Balance Lookup
                    </div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="text" id="lookupAcc" class="form-control dark"
                               placeholder="Account number…" style="flex:1">
                        <button onclick="doLookup()" class="btn"
                                style="background:rgba(255,255,255,.15);color:#fff;border:none;white-space:nowrap">
                            Look Up
                        </button>
                    </div>
                    <div id="lookupResult" style="margin-top:8px;font-size:14px;font-weight:700;color:#4ADE80;min-height:20px"></div>
                </div>

            </div>
        </div>

        <!-- ══ RIGHT: Transaction History ══ -->
        <div class="card" style="height:fit-content">
            <div class="card-header">
                <span class="card-title">📜 Transaction History</span>
                <span class="text-muted text-sm">Last <?= count($history) ?> records</span>
            </div>
            <div style="padding:8px 22px 20px;max-height:620px;overflow-y:auto">
                <?php if ($history): ?>
                <div class="txn-list">
                    <?php foreach ($history as $h): ?>
                    <div class="txn-item">
                        <div class="txn-icon <?= $typeCss[$h['type']] ?>">
                            <?= $typeIcons[$h['type']] ?>
                        </div>
                        <div class="txn-info">
                            <div class="txn-name">
                                <?= e($h['full_name'] ?? $h['account_number']) ?>
                                <?php if ($h['type'] === 'Transfer' && $h['target_account']): ?>
                                <span style="font-weight:400;color:var(--text-muted)"> → <?= e($h['target_account']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="txn-date"><?= date('M j, Y · H:i', strtotime($h['timestamp'])) ?></div>
                        </div>
                        <div class="txn-amt <?= $h['type'] === 'Deposit' ? 'pos' : 'neg' ?>">
                            <?= $h['type'] === 'Deposit' ? '+' : '-' ?><?= fmt_money($h['amount']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📜</div>
                    <div class="empty-text">No transactions yet.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /grid -->
    </div><!-- /page-body -->
</main>
</div><!-- /app-layout -->

<script>
// ── Active tab on validation error ───────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const tab = <?= $activeTab ?>;
    const btns = document.querySelectorAll('.tab-list[data-target="txnTabs"] .tab-btn');
    if (tab > 0 && btns[tab]) {
        setTimeout(() => btns[tab].click(), 50);
    }
});

// ── Balance lookup ────────────────────────────────────────────
function doLookup() {
    const acc = document.getElementById('lookupAcc').value.trim();
    const res = document.getElementById('lookupResult');
    if (!acc) { res.textContent = ''; return; }
    res.textContent = 'Looking up…'; res.style.color = 'rgba(255,255,255,.5)';
    fetch('api_balance.php?account=' + encodeURIComponent(acc))
        .then(r => r.json())
        .then(d => {
            if (d.error) {
                res.textContent = '✕ ' + d.error; res.style.color = '#FCA5A5';
            } else {
                res.textContent = '✓ ' + d.name + ' — $' + parseFloat(d.balance).toFixed(2);
                res.style.color = '#4ADE80';
            }
        })
        .catch(() => { res.textContent = 'Lookup failed.'; res.style.color = '#FCA5A5'; });
}

document.getElementById('lookupAcc')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); doLookup(); }
});
</script>

<?php include 'includes/footer.php'; ?>
