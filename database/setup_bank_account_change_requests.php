<?php
include 'db.php';
if (!$conn) die("Database connection failed!");

$sql = "CREATE TABLE IF NOT EXISTS `bank_account_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Savings','Checking','Current') DEFAULT 'Savings',
  `branch` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int(11) DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$ok = $conn->query($sql);
$err = $conn->error;
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Bank Account Change Requests Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <h1>Bank Account Change Requests Table Setup</h1>
    <?php if ($ok): ?>
        <p class="success">✅ bank_account_change_requests table created successfully!</p>
        <p><a href="../admin/request-bank.php">Go to Request Bank (Admin)</a></p>
    <?php else: ?>
        <p class="error">❌ Error: <?php echo htmlspecialchars($err); ?></p>
    <?php endif; ?>
</body>
</html>
