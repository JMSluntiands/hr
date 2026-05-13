<?php

function ensureInventoryDecommissionRequestsTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS inventory_decommission_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            inventory_item_allocation_id INT NULL,
            company_name VARCHAR(255) NULL,
            request_employee_name VARCHAR(255) NOT NULL,
            equipment_name VARCHAR(255) NOT NULL,
            item_code VARCHAR(100) NOT NULL,
            equipment_type VARCHAR(150) NULL,
            serial_number TEXT NULL,
            equipment_description TEXT NULL,
            brand_manufacturer VARCHAR(255) NULL,
            item_date_received DATE NULL,
            date_decommissioning DATE NULL,
            reason_decommissioning TEXT NOT NULL,
            test_1_notes TEXT NULL,
            test_1_date DATE NULL,
            test_2_notes TEXT NULL,
            test_2_date DATE NULL,
            test_3_notes TEXT NULL,
            test_3_date DATE NULL,
            attachment_path VARCHAR(500) NULL,
            status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
            resolution_remark TEXT NULL,
            reviewed_by_user_id INT NULL,
            reviewed_by_name VARCHAR(255) NULL,
            resolved_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_decom_employee (employee_id),
            INDEX idx_decom_status (status),
            CONSTRAINT fk_decom_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $colSerial = $conn->query("SHOW COLUMNS FROM inventory_decommission_requests LIKE 'serial_number'");
    if ($colSerial && ($row = $colSerial->fetch_assoc())) {
        $type = strtolower((string)($row['Type'] ?? ''));
        if ($type !== '' && str_contains($type, 'varchar')) {
            $conn->query('ALTER TABLE inventory_decommission_requests MODIFY COLUMN serial_number TEXT NULL');
        }
    }
}
