<?php
// ============================================================
//  api_balance.php — Quick Balance Lookup (JSON)
//  Called via fetch() from transactions.php
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['username'])) {
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

require_once 'config/db.php';

$acc = trim($_GET['account'] ?? '');
if (!$acc) {
    echo json_encode(['error' => 'No account provided']);
    exit;
}

$stmt = mysqli_prepare($conn,
    "SELECT full_name, balance FROM clients WHERE account_number = ? AND is_deleted = 0 LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $acc);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['error' => 'Account not found']);
} else {
    echo json_encode([
        'name'    => $row['full_name'],
        'balance' => number_format((float)$row['balance'], 2, '.', ''),
    ]);
}
