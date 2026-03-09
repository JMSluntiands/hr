<?php
/**
 * Setup script to create progressive discipline table.
 * Run this file once after deployment.
 */

include 'db.php';

if (!$conn) {
    die('Database connection failed!');
}

$sql = "CREATE TABLE IF NOT EXISTS `progressive_discipline_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `incident_date` date NOT NULL,
  `offense_type` varchar(120) NOT NULL,
  `discipline_level` enum('Verbal Warning','Written Warning','Final Warning','Suspension','Termination') NOT NULL,
  `description` text NOT NULL,
  `action_taken` text DEFAULT NULL,
  `status` enum('Active','Resolved','Escalated') NOT NULL DEFAULT 'Active',
  `issued_by` int(11) NOT NULL,
  `next_review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_incident_date` (`incident_date`),
  CONSTRAINT `fk_progressive_discipline_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$ok = $conn->query($sql);
$err = $conn->error;
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Progressive Discipline Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Progressive Discipline Table Setup</h1>

    <?php if ($ok): ?>
        <div class="success">Progressive discipline table is ready.</div>
        <p><a href="../admin/progressive-discipline.php">Go to Progressive Discipline</a></p>
    <?php else: ?>
        <div class="error">Error: <?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>
</body>
</html>
