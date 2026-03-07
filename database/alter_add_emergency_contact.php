<?php
/**
 * Adds emergency contact fields to employees.
 * Run once.
 */
include 'db.php';
if (!$conn) die("Database connection failed.");

$cols = [
    'emergency_contact_name'  => "ALTER TABLE `employees` ADD COLUMN `emergency_contact_name` VARCHAR(255) NULL DEFAULT NULL AFTER `address`",
    'emergency_contact_phone' => "ALTER TABLE `employees` ADD COLUMN `emergency_contact_phone` VARCHAR(20) NULL DEFAULT NULL AFTER `emergency_contact_name`",
];
$done = [];
foreach ($cols as $name => $q) {
    if ($conn->query($q) === true) {
        $done[] = "employees.{$name} added.";
    } else {
        if (strpos($conn->error, 'Duplicate column') !== false) {
            $done[] = "employees.{$name} already exists.";
        } else {
            $done[] = "Error {$name}: " . $conn->error;
        }
    }
}
$conn->close();
header('Content-Type: text/html; charset=utf-8');
echo "<p>" . implode(" ", array_map('htmlspecialchars', $done)) . "</p>";
?>
