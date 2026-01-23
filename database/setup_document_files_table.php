<?php
include 'db.php';
if (!$conn) die("Database connection failed!");

$sql = "CREATE TABLE IF NOT EXISTS `document_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_by_name` varchar(255) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$ok = $conn->query($sql);
$err = $conn->error;
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Document Files Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <h1>Document Files Table Setup</h1>
    <?php if ($ok): ?>
        <p class="success">✅ document_files table created successfully!</p>
        <p><a href="../admin/request-document-file.php">Go to Document File</a></p>
    <?php else: ?>
        <p class="error">❌ Error: <?php echo htmlspecialchars($err); ?></p>
    <?php endif; ?>
</body>
</html>
