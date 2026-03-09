<?php

function ensureInventoryItemsTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS inventory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id VARCHAR(30) NOT NULL UNIQUE,
            item_name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            type VARCHAR(100) NULL,
            item_condition VARCHAR(50) NULL,
            remarks TEXT NULL,
            item_image_path VARCHAR(255) NULL,
            date_arrived DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Backward compatibility for existing tables created before item_condition.
    $check = $conn->query("SHOW COLUMNS FROM inventory_items LIKE 'item_condition'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_items ADD COLUMN item_condition VARCHAR(50) NULL AFTER type");
    }

    // Backward compatibility for existing tables created before item_image_path.
    $checkImagePath = $conn->query("SHOW COLUMNS FROM inventory_items LIKE 'item_image_path'");
    if ($checkImagePath && $checkImagePath->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_items ADD COLUMN item_image_path VARCHAR(255) NULL AFTER remarks");
    }
}
