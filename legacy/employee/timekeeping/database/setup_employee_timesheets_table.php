<?php
/**
 * Setup script for employee_timesheets table.
 * Run this once during deployment/migration.
 */

include __DIR__ . '/../../../database/db.php';

if (!$conn instanceof mysqli) {
    die('Database connection not available.');
}

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS employee_timesheets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    row_number TINYINT UNSIGNED NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    time_start TIME DEFAULT NULL,
    time_end TIME DEFAULT NULL,
    total_minutes INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_date (employee_id, work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

if ($conn->query($sql) === true) {
    echo "employee_timesheets table created or already exists.";
} else {
    echo "Error creating employee_timesheets table: " . $conn->error;
}

