<?php
/**
 * One-time script to add secondary_workplace column to employees table.
 * Run in browser: http://localhost/hr/database/alter_add_secondary_workplace.php
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

$sql = "ALTER TABLE `employees` ADD COLUMN `secondary_workplace` text DEFAULT NULL AFTER `address`";

if ($conn->query($sql) === TRUE) {
    echo "✅ secondary_workplace column added to employees table.<br>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "ℹ️ Column secondary_workplace already exists.<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

echo "<a href=\"../admin/staff-add.php\">Go to Add Employee</a>";

$conn->close();
