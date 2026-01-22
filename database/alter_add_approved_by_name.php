<?php
/**
 * Adds approved_by_name to leave_requests and document_requests.
 * Run once if you need "Approved by" display.
 */
include 'db.php';
if (!$conn) die("Database connection failed!");

$done = [];
$err = [];

$q1 = "ALTER TABLE `leave_requests` ADD COLUMN `approved_by_name` VARCHAR(255) NULL DEFAULT NULL AFTER `approved_at`";
if ($conn->query($q1) === true) {
    $done[] = "leave_requests.approved_by_name added.";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        $done[] = "leave_requests.approved_by_name already exists.";
    } else {
        $err[] = "leave_requests: " . $conn->error;
    }
}

$q2 = "ALTER TABLE `document_requests` ADD COLUMN `approved_by_name` VARCHAR(255) NULL DEFAULT NULL AFTER `approved_at`";
if ($conn->query($q2) === true) {
    $done[] = "document_requests.approved_by_name added.";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        $done[] = "document_requests.approved_by_name already exists.";
    } else {
        $err[] = "document_requests: " . $conn->error;
    }
}

$conn->close();
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Alter: approved_by_name</h1>";
foreach ($done as $m) echo "<p style='color:green'>" . htmlspecialchars($m) . "</p>";
foreach ($err as $m) echo "<p style='color:red'>" . htmlspecialchars($m) . "</p>";
echo "<p><a href='../admin/request-leaves'>Go to Request Leaves</a></p>";
?>
