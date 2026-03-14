<?php
/**
 * One-time script to add emergency_contact_relationship column.
 * Run in browser: http://localhost/hr/database/alter_add_emergency_contact_relationship.php
 */
include 'db.php';
if (!$conn) die("Database connection failed.");

$sql = "ALTER TABLE `employees` ADD COLUMN `emergency_contact_relationship` varchar(100) DEFAULT NULL AFTER `emergency_contact_phone`";

if ($conn->query($sql) === TRUE) {
    echo "✅ emergency_contact_relationship column added.<br>";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "ℹ️ Column already exists.<br>";
    } else {
        echo "❌ Error: " . htmlspecialchars($conn->error) . "<br>";
    }
}
echo "<a href=\"../admin/staff-add.php\">Go to Add Employee</a>";
$conn->close();
