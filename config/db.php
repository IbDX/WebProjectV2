<?php
// ============================================================
//  config/db.php — Database Connection & Permission Constants
//  Prototype Bank | AppServ / MySQLi (procedural)
// ============================================================

// ---- Database Credentials ----------------------------------
//  Default AppServ root password is usually empty.
//  Change DB_PASS below if your MySQL root has a password.
// ------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '12345678');          // <-- update if needed
define('DB_NAME', 'prototype_bank');
define('DB_PORT', 3306);

// ---- Bitmask Permission Constants --------------------------
//  Matches the C++ enum exactly.
// ------------------------------------------------------------
define('PERM_FULL',          -1);   // pFullAccess  : unrestricted
define('PERM_CLIENT_LIST',    1);   // showClientList
define('PERM_ADD_CLIENT',     2);   // addNewClient
define('PERM_DEL_CLIENT',     4);   // DeleteClient
define('PERM_UPD_CLIENT',     8);   // UpdateClient
define('PERM_FIND_CLIENT',   16);   // FindClient
define('PERM_MANAGE_USERS',  32);   // ManageUsers
define('PERM_TRANSACTIONS',  64);   // Transactions

// ---- Connect -----------------------------------------------
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    $err = htmlspecialchars(mysqli_connect_error(), ENT_QUOTES, 'UTF-8');
    http_response_code(503);
    die(<<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Error — Prototype Bank</title>
        <style>
            body { font-family: system-ui, sans-serif; background: #F5F0E1;
                   display: flex; align-items: center; justify-content: center;
                   min-height: 100vh; margin: 0; }
            .box { background: #fff; border-radius: 16px; padding: 36px 40px;
                   max-width: 480px; box-shadow: 0 4px 24px rgba(0,0,0,.1);
                   border-left: 5px solid #DC2626; }
            h2 { color: #B91C1C; margin: 0 0 10px; }
            p  { color: #374151; line-height: 1.6; margin: 0 0 8px; }
            code { background: #FEE2E2; padding: 2px 6px; border-radius: 4px;
                   font-size: 13px; color: #991B1B; }
        </style>
    </head>
    <body>
      <div class="box">
        <h2>⚠ Database Connection Failed</h2>
        <p><code>{$err}</code></p>
        <p>Open <strong>config/db.php</strong> and verify
           <code>DB_HOST</code>, <code>DB_USER</code>, and
           <code>DB_PASS</code>.</p>
        <p>Make sure you have imported <strong>schema.sql</strong>
           into phpMyAdmin first.</p>
      </div>
    </body>
    </html>
    HTML);
}

mysqli_set_charset($conn, 'utf8mb4');
