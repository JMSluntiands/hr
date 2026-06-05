<?php
/**
 * Adds start_time, end_time, total_hours to leave_requests.
 * Run once: php database/alter_leave_requests_add_times.php (from project root, with db.php configured)
 */
$root = dirname(__DIR__);
if (is_file($root.'/database/db.php')) {
    include $root.'/database/db.php';
} else {
    die("database/db.php not found.\n");
}

if (! $conn) {
    die("Database connection failed.\n");
}

$columns = [
    'start_time' => "ADD COLUMN `start_time` TIME NULL DEFAULT NULL AFTER `start_date`",
    'end_time' => "ADD COLUMN `end_time` TIME NULL DEFAULT NULL AFTER `end_date`",
    'total_hours' => "ADD COLUMN `total_hours` DECIMAL(8,2) NULL DEFAULT NULL AFTER `total_days`",
];

foreach ($columns as $name => $sqlPart) {
    $check = $conn->query("SHOW COLUMNS FROM `leave_requests` LIKE '{$name}'");
    if ($check && $check->num_rows > 0) {
        echo "leave_requests.{$name} already exists.\n";
        continue;
    }
    if ($conn->query("ALTER TABLE `leave_requests` {$sqlPart}") === true) {
        echo "leave_requests.{$name} added.\n";
    } else {
        echo "Error ({$name}): ".$conn->error."\n";
    }
}

$conn->close();
