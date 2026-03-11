<?php
/**
 * One-time script to add NBI and Police clearance numbers to employees table.
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

$sql = "ALTER TABLE `employees`
        ADD COLUMN `nbi_clearance` varchar(50) DEFAULT NULL AFTER `tin`,
        ADD COLUMN `police_clearance` varchar(50) DEFAULT NULL AFTER `nbi_clearance`";

if ($conn->query($sql) === TRUE) {
    echo "✅ NBI and Police clearance columns added to employees table.<br>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "ℹ️ Columns already exist on employees table.<br>";
    } else {
        echo "❌ Error altering employees table: " . $conn->error;
    }
}

echo "<a href=\"../admin/staff-add.php\">Go to Add Employee Page</a>";

$conn->close();
?>

