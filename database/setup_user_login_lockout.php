<?php
/**
 * Add lockout and last_password_change columns to user_login.
 * Run once: http://localhost/hr/database/setup_user_login_lockout.php
 */
require_once __DIR__ . '/db.php';

$columns = [
    'last_password_change' => "ALTER TABLE user_login ADD COLUMN last_password_change DATETIME NULL DEFAULT NULL AFTER role",
    'failed_attempts'       => "ALTER TABLE user_login ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0 AFTER last_password_change",
    'locked'               => "ALTER TABLE user_login ADD COLUMN locked TINYINT(1) NOT NULL DEFAULT 0 AFTER failed_attempts",
    'locked_at'            => "ALTER TABLE user_login ADD COLUMN locked_at DATETIME NULL DEFAULT NULL AFTER locked",
    'unlock_requested'     => "ALTER TABLE user_login ADD COLUMN unlock_requested TINYINT(1) NOT NULL DEFAULT 0 AFTER locked_at",
];

foreach ($columns as $name => $sql) {
    $check = $conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_login' AND COLUMN_NAME = '$name'");
    if ($check && $check->num_rows > 0) {
        echo "Column $name already exists.<br>";
        continue;
    }
    if ($conn->query($sql)) {
        echo "Added column $name.<br>";
    } else {
        echo "Error adding $name: " . $conn->error . "<br>";
    }
}

echo "Done.";
