<?php

function ensureInventoryItemAllocationsTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS inventory_item_allocations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inventory_item_id INT NOT NULL,
            employee_id INT NOT NULL,
            date_received DATE NOT NULL,
            date_return DATE NULL,
            return_remarks TEXT NULL,
            employee_appeal TEXT NULL,
            employee_appeal_remarks TEXT NULL,
            employee_appeal_at DATETIME NULL,
            admin_viewed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_inventory_item_allocations_employee_id (employee_id),
            INDEX idx_inventory_item_allocations_item_id (inventory_item_id),
            CONSTRAINT fk_inventory_item_allocations_item
                FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_inventory_item_allocations_employee
                FOREIGN KEY (employee_id) REFERENCES employees(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Backward compatibility for existing tables created before date_return.
    $checkDateReturn = $conn->query("SHOW COLUMNS FROM inventory_item_allocations LIKE 'date_return'");
    if ($checkDateReturn && $checkDateReturn->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_item_allocations ADD COLUMN date_return DATE NULL AFTER date_received");
    }

    // Backward compatibility for existing tables created before return_remarks.
    $checkReturnRemarks = $conn->query("SHOW COLUMNS FROM inventory_item_allocations LIKE 'return_remarks'");
    if ($checkReturnRemarks && $checkReturnRemarks->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_item_allocations ADD COLUMN return_remarks TEXT NULL AFTER date_return");
    }

    // Backward compatibility for employee appeal fields.
    $checkEmployeeAppeal = $conn->query("SHOW COLUMNS FROM inventory_item_allocations LIKE 'employee_appeal'");
    if ($checkEmployeeAppeal && $checkEmployeeAppeal->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_item_allocations ADD COLUMN employee_appeal TEXT NULL AFTER return_remarks");
    }

    $checkEmployeeAppealRemarks = $conn->query("SHOW COLUMNS FROM inventory_item_allocations LIKE 'employee_appeal_remarks'");
    if ($checkEmployeeAppealRemarks && $checkEmployeeAppealRemarks->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_item_allocations ADD COLUMN employee_appeal_remarks TEXT NULL AFTER employee_appeal");
    }

    $checkEmployeeAppealAt = $conn->query("SHOW COLUMNS FROM inventory_item_allocations LIKE 'employee_appeal_at'");
    if ($checkEmployeeAppealAt && $checkEmployeeAppealAt->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_item_allocations ADD COLUMN employee_appeal_at DATETIME NULL AFTER employee_appeal_remarks");
    }

    $checkAdminViewedAt = $conn->query("SHOW COLUMNS FROM inventory_item_allocations LIKE 'admin_viewed_at'");
    if ($checkAdminViewedAt && $checkAdminViewedAt->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_item_allocations ADD COLUMN admin_viewed_at DATETIME NULL AFTER employee_appeal_at");
    }
}
