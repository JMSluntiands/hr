<?php

function ensureInventoryItemsTable(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS inventory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id VARCHAR(30) NOT NULL UNIQUE,
            item_name VARCHAR(100) NOT NULL,
            description TEXT NULL,
            brand_manufacturer VARCHAR(150) NULL,
            type VARCHAR(100) NULL,
            item_condition VARCHAR(50) NULL,
            remarks TEXT NULL,
            item_image_path VARCHAR(255) NULL,
            item_image_paths TEXT NULL,
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

    // Backward compatibility for existing tables created before brand_manufacturer.
    $checkBrandManufacturer = $conn->query("SHOW COLUMNS FROM inventory_items LIKE 'brand_manufacturer'");
    if ($checkBrandManufacturer && $checkBrandManufacturer->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_items ADD COLUMN brand_manufacturer VARCHAR(150) NULL AFTER description");
    }

    // Backward compatibility for existing tables created before item_image_path.
    $checkImagePath = $conn->query("SHOW COLUMNS FROM inventory_items LIKE 'item_image_path'");
    if ($checkImagePath && $checkImagePath->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_items ADD COLUMN item_image_path VARCHAR(255) NULL AFTER remarks");
    }

    // Multiple images: JSON array of paths (same relative format as item_image_path); first path mirrored in item_image_path.
    $checkImagePaths = $conn->query("SHOW COLUMNS FROM inventory_items LIKE 'item_image_paths'");
    if ($checkImagePaths && $checkImagePaths->num_rows === 0) {
        $conn->query("ALTER TABLE inventory_items ADD COLUMN item_image_paths TEXT NULL AFTER item_image_path");
    }
}
