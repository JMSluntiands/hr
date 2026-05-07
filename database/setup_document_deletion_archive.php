<?php
/**
 * Adds staff-initiated document removal workflow:
 * - deletion_requested_at on employee_document_uploads
 * - document_archive table (file retained for admin after approved removal)
 *
 * Open once in browser or CLI: php setup_document_deletion_archive.php
 */
include __DIR__ . '/db.php';
if (!$conn) {
    die("Database connection failed!\n");
}

$errors = [];
$ok = [];

$col = $conn->query("SHOW COLUMNS FROM employee_document_uploads LIKE 'deletion_requested_at'");
if ($col && $col->num_rows > 0) {
    $ok[] = 'Column deletion_requested_at already exists on employee_document_uploads.';
} else {
    if ($conn->query("ALTER TABLE employee_document_uploads ADD COLUMN deletion_requested_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at")) {
        $ok[] = 'Added deletion_requested_at to employee_document_uploads.';
    } else {
        $errors[] = 'ALTER employee_document_uploads: ' . $conn->error;
    }
}

$sqlArchive = "CREATE TABLE IF NOT EXISTS `document_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `employee_full_name` varchar(255) DEFAULT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `source_upload_id` int(11) DEFAULT NULL,
  `deletion_requested_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int(11) DEFAULT NULL,
  `archived_by_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sqlArchive)) {
    $ok[] = 'Table document_archive is ready.';
} else {
    $errors[] = 'CREATE document_archive: ' . $conn->error;
}

$conn->close();

$isCli = (php_sapi_name() === 'cli');
if ($isCli) {
    foreach ($ok as $m) {
        echo $m . "\n";
    }
    foreach ($errors as $m) {
        echo "ERROR: $m\n";
    }
    exit(empty($errors) ? 0 : 1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Document Deletion / Archive</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <h1>Document deletion &amp; archive setup</h1>
    <?php foreach ($ok as $m): ?>
        <p class="success">✅ <?php echo htmlspecialchars($m); ?></p>
    <?php endforeach; ?>
    <?php foreach ($errors as $m): ?>
        <p class="error">❌ <?php echo htmlspecialchars($m); ?></p>
    <?php endforeach; ?>
    <?php if (empty($errors)): ?>
        <p><a href="../admin/document-archive.php">Open Document Archive (admin)</a></p>
    <?php endif; ?>
</body>
</html>
