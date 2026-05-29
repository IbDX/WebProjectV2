<?php
// ============================================================
//  includes/header.php — Shared HTML <head>
//  Included at the top of every page (except login).
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Prototype Bank');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Prototype Bank — Secure, modern bank management system.">
    <title><?= e(PAGE_TITLE) ?> — Prototype Bank</title>

    <!-- Huninn — Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Huninn&display=swap" rel="stylesheet">

    <!-- Design System -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">

    <!-- Chart.js 4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
