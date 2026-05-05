<?php

function ensureInventoryItemRequestsTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS inventory_item_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            details TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            admin_remark TEXT NULL,
            resolved_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_inventory_item_requests_employee_id (employee_id),
            INDEX idx_inventory_item_requests_status (status),
            CONSTRAINT fk_inventory_item_requests_employee
                FOREIGN KEY (employee_id) REFERENCES employees(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
