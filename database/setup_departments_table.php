<?php
/**
 * Setup script to create departments table and seed initial data.
 * Run this once (in browser or CLI) to prepare the departments master data.
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

// Create departments table
$sql = "CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($sql)) {
    die('❌ Error creating departments table: ' . $conn->error);
}

// Initial seed data
$initialDepartments = [
    'Supervisor',
    'Management',
    'Energy Team',
    'Admin',
    'IT Department',
];

// Insert only if table is empty
$check = $conn->query("SELECT COUNT(*) AS c FROM departments");
$count = 0;
if ($check && $row = $check->fetch_assoc()) {
    $count = (int)$row['c'];
}

if ($count === 0) {
    $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
    if ($stmt) {
        foreach ($initialDepartments as $dept) {
            $name = $dept;
            $stmt->bind_param('s', $name);
            $stmt->execute();
        }
        $stmt->close();
        echo "✅ Departments table created and seeded successfully.<br>";
    } else {
        echo "⚠️ Departments table created, but failed to prepare seed insert statement.<br>";
    }
} else {
    echo "ℹ️ Departments table already has data. No seed inserted.<br>";
}

echo "<a href=\"../admin/department.php\">Go to Department Management</a>";

$conn->close();
?>

