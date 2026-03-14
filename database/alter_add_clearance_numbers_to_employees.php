<?php
/**
 * One-time script to add NBI and Police clearance numbers to employees table.
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

$altered = false;
foreach (
    [
        "ADD COLUMN `nbi_clearance` varchar(50) DEFAULT NULL AFTER `tin`",
        "ADD COLUMN `police_clearance` varchar(50) DEFAULT NULL AFTER `nbi_clearance`"
    ] as $addColumn
) {
    $sql = "ALTER TABLE `employees` " . $addColumn;
    if ($conn->query($sql) === TRUE) {
        echo "✅ Column added.<br>";
        $altered = true;
    } else {
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "ℹ️ Column already exists.<br>";
        } else {
            echo "❌ Error: " . $conn->error . "<br>";
        }
    }
}
if ($altered) {
    echo "✅ NBI and Police clearance columns are now on employees table.<br>";
}

echo "<a href=\"../admin/staff-add.php\">Go to Add Employee Page</a>";

$conn->close();
?>

