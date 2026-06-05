<?php
/**
 * Add privacy policy acceptance tracking to user_login.
 * Run once: http://localhost/hr/database/setup_user_login_privacy_policy.php
 */
require_once __DIR__ . '/db.php';

$check = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_login' AND COLUMN_NAME = 'privacy_policy_accepted_at'");
if ($check && $check->num_rows > 0) {
    echo 'Column privacy_policy_accepted_at already exists.';
    exit;
}

$after = 'role';
$colCheck = $conn->query("SHOW COLUMNS FROM user_login LIKE 'last_login'");
if ($colCheck && $colCheck->num_rows > 0) {
    $after = 'last_login';
}
$sql = "ALTER TABLE user_login ADD COLUMN privacy_policy_accepted_at DATETIME NULL DEFAULT NULL AFTER {$after}";
if ($conn->query($sql)) {
    echo 'Column privacy_policy_accepted_at added successfully.';
} else {
    echo 'Error: '.$conn->error;
}
