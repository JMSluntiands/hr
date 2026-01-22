<?php
/**
 * Setup script to create document_requests table
 * Run this file once to create the table
 */

include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

$sql = "CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` enum('COE','SSS Certificate','Pag-IBIG Certificate','PhilHealth Certificate') NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_document_type` (`document_type`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$ok = $conn->query($sql);
$err = $conn->error;
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Document Requests Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <h1>Document Requests Table Setup</h1>
    <?php if ($ok): ?>
        <p class="success">✅ document_requests table created successfully!</p>
        <p><a href="../admin/index.php">Go to Admin Dashboard</a></p>
    <?php else: ?>
        <p class="error">❌ Error: <?php echo htmlspecialchars($err); ?></p>
    <?php endif; ?>
</body>
</html>
