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

$stmt = $conn->prepare(
    "SELECT full_name, balance FROM clients WHERE account_number = ? AND is_deleted = 0 LIMIT 1"
);
$stmt->bind_param('s', $acc);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['error' => 'Account not found']);
} else {
    echo json_encode([
        'name'    => $row['full_name'],
        'balance' => number_format((float)$row['balance'], 2, '.', ''),
    ]);
}
