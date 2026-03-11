<?php
/**
 * Setup script to create employment_types table and seed initial data.
 * Run this once to prepare the employment type master data.
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

// Create employment_types table
$sql = "CREATE TABLE IF NOT EXISTS `employment_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($sql)) {
    die('❌ Error creating employment_types table: ' . $conn->error);
}

// Initial seed data
$initialTypes = [
    'Regular Employee',
    'Contractor',
];

// Insert only if table is empty
$check = $conn->query("SELECT COUNT(*) AS c FROM employment_types");
$count = 0;
if ($check && $row = $check->fetch_assoc()) {
    $count = (int)$row['c'];
}

if ($count === 0) {
    $stmt = $conn->prepare("INSERT INTO employment_types (name) VALUES (?)");
    if ($stmt) {
        foreach ($initialTypes as $type) {
            $name = $type;
            $stmt->bind_param('s', $name);
            $stmt->execute();
        }
        $stmt->close();
        echo "✅ employment_types table created and seeded successfully.<br>";
    } else {
        echo "⚠️ employment_types table created, but failed to prepare seed insert statement.<br>";
    }
} else {
    echo "ℹ️ employment_types table already has data. No seed inserted.<br>";
}

echo "<a href=\"../admin/employment-type.php\">Go to Employment Type Management</a>";

$conn->close();
?>

