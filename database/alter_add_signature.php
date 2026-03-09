<?php
/**
 * Adds signature to employees (path to uploaded signature image).
 * Run once: http://localhost/hr/database/alter_add_signature.php
 */
include 'db.php';
if (!$conn) die("Database connection failed.");

$q = "ALTER TABLE `employees` ADD COLUMN `signature` VARCHAR(255) NULL DEFAULT NULL AFTER `profile_picture`";
if ($conn->query($q) === true) {
    $msg = "employees.signature added.";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        $msg = "employees.signature already exists.";
    } else {
        $msg = "Error: " . $conn->error;
    }
}
$conn->close();
header('Content-Type: text/html; charset=utf-8');
echo "<p>" . htmlspecialchars($msg) . "</p>";
echo "<p><a href='../employee/profile.php'>Go to My Profile</a></p>";
