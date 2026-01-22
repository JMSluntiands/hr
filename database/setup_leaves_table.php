<?php
/**
 * Setup script to create leave-related tables
 * Run this file once to create the leave tables in your database
 */

// Include database connection
include 'db.php';

if (!$conn) {
    die("Database connection failed!");
}

// SQL to create leave_allocations table
$sqlAllocations = "CREATE TABLE IF NOT EXISTS `leave_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Sick Leave','Vacation Leave','Emergency Leave','Bereavement Leave','Maternity Leave','Paternity Leave') NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 0,
  `used_days` int(11) NOT NULL DEFAULT 0,
  `remaining_days` int(11) NOT NULL DEFAULT 0,
  `year` year(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_year` (`year`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// SQL to create leave_requests table
$sqlRequests = "CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Sick Leave','Vacation Leave','Emergency Leave','Bereavement Leave','Maternity Leave','Paternity Leave') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_leave_type` (`leave_type`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$errors = [];
$success = [];

// Execute leave_allocations table creation
if ($conn->query($sqlAllocations) === TRUE) {
    $success[] = "✅ leave_allocations table created successfully!";
} else {
    $errors[] = "❌ Error creating leave_allocations table: " . $conn->error;
}

// Execute leave_requests table creation
if ($conn->query($sqlRequests) === TRUE) {
    $success[] = "✅ leave_requests table created successfully!";
} else {
    $errors[] = "❌ Error creating leave_requests table: " . $conn->error;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Leave Tables</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Leave Tables Setup</h1>
    
    <?php foreach ($success as $msg): ?>
        <div class="success"><?php echo $msg; ?></div>
    <?php endforeach; ?>
    
    <?php foreach ($errors as $msg): ?>
        <div class="error"><?php echo $msg; ?></div>
    <?php endforeach; ?>
    
    <?php if (empty($errors)): ?>
        <p><strong>All tables are ready to use!</strong></p>
        <p><a href="../admin/index.php">Go to Admin Dashboard</a></p>
    <?php endif; ?>
</body>
</html>
<?php
$conn->close();
?>
