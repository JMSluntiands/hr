-- Reimbursements production schema patch
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS reimbursements (
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
    INDEX idx_purchased_date (purchased_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing columns for older online schema versions.
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'reimbursements'
          AND column_name = 'admin_receipt_path'
    ),
    'SELECT 1',
    'ALTER TABLE reimbursements ADD COLUMN admin_receipt_path VARCHAR(255) NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'reimbursements'
          AND column_name = 'admin_receipt_original_name'
    ),
    'SELECT 1',
    'ALTER TABLE reimbursements ADD COLUMN admin_receipt_original_name VARCHAR(255) NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'reimbursements'
          AND column_name = 'reimbursed_at'
    ),
    'SELECT 1',
    'ALTER TABLE reimbursements ADD COLUMN reimbursed_at DATETIME NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
