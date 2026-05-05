<?php
/**
 * One-time setup: creates incident_reports table. Open in browser once if needed.
 */
include __DIR__ . '/db.php';
include __DIR__ . '/incident_reports_schema.php';

$ok = $conn && ensureIncidentReportsTable($conn);
$err = $conn ? $conn->error : 'No connection';
if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Incident Reports</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Incident Reports Table</h1>
    <?php if ($ok): ?>
        <div class="success">Table is ready.</div>
        <p><a href="../admin/incident-report-list">Admin list</a> · <a href="../employee/incident-report-list.php">Employee list</a></p>
    <?php else: ?>
        <div class="error">Error: <?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>
</body>
</html>
