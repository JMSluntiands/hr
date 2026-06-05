<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventorySchemaService
{
    public function ensureItemsTable(): void
    {
        if (Schema::hasTable('inventory_items')) {
            $this->ensureItemsColumns();

            return;
        }

        DB::statement("
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
                decommissioned_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function ensureItemsColumns(): void
    {
        $columns = [
            'item_condition' => 'ALTER TABLE inventory_items ADD COLUMN item_condition VARCHAR(50) NULL AFTER type',
            'brand_manufacturer' => 'ALTER TABLE inventory_items ADD COLUMN brand_manufacturer VARCHAR(150) NULL AFTER description',
            'item_image_path' => 'ALTER TABLE inventory_items ADD COLUMN item_image_path VARCHAR(255) NULL AFTER remarks',
            'item_image_paths' => 'ALTER TABLE inventory_items ADD COLUMN item_image_paths TEXT NULL AFTER item_image_path',
            'decommissioned_at' => 'ALTER TABLE inventory_items ADD COLUMN decommissioned_at DATETIME NULL AFTER date_arrived',
        ];

        foreach ($columns as $col => $sql) {
            if (! Schema::hasColumn('inventory_items', $col)) {
                DB::statement($sql);
            }
        }
    }

    public function ensureAllocationsTable(): void
    {
        if (! Schema::hasTable('inventory_items') || ! Schema::hasTable('employees')) {
            return;
        }

        if (Schema::hasTable('inventory_item_allocations')) {
            $this->ensureAllocationColumns();

            return;
        }

        DB::statement("
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
    }

    private function ensureAllocationColumns(): void
    {
        $columns = [
            'date_return' => 'ALTER TABLE inventory_item_allocations ADD COLUMN date_return DATE NULL AFTER date_received',
            'return_remarks' => 'ALTER TABLE inventory_item_allocations ADD COLUMN return_remarks TEXT NULL AFTER date_return',
            'employee_appeal' => 'ALTER TABLE inventory_item_allocations ADD COLUMN employee_appeal TEXT NULL AFTER return_remarks',
            'employee_appeal_remarks' => 'ALTER TABLE inventory_item_allocations ADD COLUMN employee_appeal_remarks TEXT NULL AFTER employee_appeal',
            'employee_appeal_at' => 'ALTER TABLE inventory_item_allocations ADD COLUMN employee_appeal_at DATETIME NULL AFTER employee_appeal_remarks',
            'admin_viewed_at' => 'ALTER TABLE inventory_item_allocations ADD COLUMN admin_viewed_at DATETIME NULL AFTER employee_appeal_at',
        ];

        foreach ($columns as $col => $sql) {
            if (! Schema::hasColumn('inventory_item_allocations', $col)) {
                DB::statement($sql);
            }
        }
    }

    public function ensureRequestsTable(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (Schema::hasTable('inventory_item_requests')) {
            return;
        }

        DB::statement("
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

    public function ensureDecommissionRequestsTable(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (Schema::hasTable('inventory_decommission_requests')) {
            return;
        }

        DB::statement("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function ensureActivityLogsTable(): void
    {
        if (Schema::hasTable('inventory_activity_logs')) {
            return;
        }

        DB::statement("
            CREATE TABLE IF NOT EXISTS inventory_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                user_name VARCHAR(255) NOT NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT NULL,
                item_code VARCHAR(50) NULL,
                description TEXT NULL,
                change_details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_entity_type (entity_type),
                INDEX idx_created_at (created_at),
                INDEX idx_item_code (item_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function ensureCoreTables(): void
    {
        $this->ensureItemsTable();
        $this->ensureAllocationsTable();
        $this->ensureRequestsTable();
        $this->ensureDecommissionRequestsTable();
        $this->ensureActivityLogsTable();
    }
}
