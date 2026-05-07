<?php
include __DIR__ . '/db.php';

if (!$conn) {
    die("Connection failed: " . (mysqli_connect_error() ?: 'unknown error'));
}

$sql = "CREATE TABLE IF NOT EXISTS reimbursements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    expense_type VARCHAR(100) NOT NULL,
    expense_description TEXT NOT NULL,
    purchased_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    receipt_path VARCHAR(255) NULL,
    receipt_original_name VARCHAR(255) NULL,
    admin_receipt_path VARCHAR(255) NULL,
    admin_receipt_original_name VARCHAR(255) NULL,
    reimbursed_at DATETIME NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    rejection_reason TEXT NULL,
    approved_by INT NULL,
    approved_by_name VARCHAR(150) NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    INDEX idx_purchased_date (purchased_date),
    CONSTRAINT fk_reimbursements_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === true) {
    echo "reimbursements table is ready.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
