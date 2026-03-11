<?php
session_start();

// Check kung may naka-login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

// Auto-logout after 5 minutes of inactivity
require_once __DIR__ . '/session_timeout.php';
