<?php
/**
 * Add last_login column to user_login.
 * Run once: http://localhost/hr/database/setup_user_login_last_login.php
 */
require_once __DIR__ . '/db.php';

$check = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_login' AND COLUMN_NAME = 'last_login'");
if ($check && $check->num_rows > 0) {
    echo "Column last_login already exists.";
    exit;
}

$sql = "ALTER TABLE user_login ADD COLUMN last_login DATETIME NULL DEFAULT NULL AFTER role";
if ($conn->query($sql)) {
    echo "Column last_login added successfully.";
} else {
    echo "Error: " . $conn->error;
}
