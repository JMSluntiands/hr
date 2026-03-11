<?php
/**
 * One-time script to add employment_type_id column to employees table.
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

$sql = "ALTER TABLE `employees`
        ADD COLUMN `employment_type_id` INT(11) NULL DEFAULT NULL AFTER `department`,
        ADD KEY `idx_employment_type_id` (`employment_type_id`)";

if ($conn->query($sql) === TRUE) {
    echo "✅ employment_type_id column added to employees table.<br>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "ℹ️ employment_type_id column already exists.<br>";
    } else {
        echo "❌ Error altering employees table: " . $conn->error;
    }
}

echo "<a href=\"../admin/staff-add.php\">Go to Add Employee Page</a>";

$conn->close();
?>

