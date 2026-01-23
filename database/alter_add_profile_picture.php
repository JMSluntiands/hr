<?php
/**
 * Adds profile_picture to employees.
 * Run once. Stores path like "profile_pictures/123_timestamp.jpg".
 */
include 'db.php';
if (!$conn) die("Database connection failed.");

$q = "ALTER TABLE `employees` ADD COLUMN `profile_picture` VARCHAR(255) NULL DEFAULT NULL AFTER `tin`";
if ($conn->query($q) === true) {
    $msg = "employees.profile_picture added.";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        $msg = "employees.profile_picture already exists.";
    } else {
        $msg = "Error: " . $conn->error;
    }
}
$conn->close();
header('Content-Type: text/html; charset=utf-8');
echo "<p>" . htmlspecialchars($msg) . "</p>";
echo "<p><a href='../employee/profile'>Go to My Profile</a></p>";
?>
