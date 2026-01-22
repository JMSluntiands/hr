<?php
include 'db.php';
if (!$conn) die("Database connection failed!");

$sql = "CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_entity_type` (`entity_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$ok = $conn->query($sql);
$err = $conn->error;
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Activity Logs Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <h1>Activity Logs Table Setup</h1>
    <?php if ($ok): ?>
        <p class="success">✅ activity_logs table created successfully!</p>
        <p><a href="../admin/index.php">Go to Admin Dashboard</a></p>
    <?php else: ?>
        <p class="error">❌ Error: <?php echo htmlspecialchars($err); ?></p>
    <?php endif; ?>
</body>
</html>
