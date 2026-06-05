<?php
/**
 * Session auto-logout after 5 minutes of inactivity.
 * Include this after session_start() and after confirming user is logged in.
 */
if (!isset($_SESSION['user_id'])) {
    return;
}

$timeout_seconds = 300; // 5 minutes
$now = time();

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = $now;
    return;
}

if (($now - $_SESSION['last_activity']) > $timeout_seconds) {
    session_unset();
    session_destroy();
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $afterHr = preg_replace('#^.*/hr/?#', '', $script);
    $dirOnly = dirname($afterHr);
    $depth = ($dirOnly === '.' || $dirOnly === '') ? 0 : substr_count($dirOnly, '/') + 1;
    $prefix = str_repeat('../', $depth);
    header('Location: ' . $prefix . 'index.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = $now;
