<?php
/**
 * Adds employee columns expected by staff-add / staff-edit if the table is older than the app.
 */
function ensure_employees_staff_columns(mysqli $mysqli): void
{
    $t = $mysqli->query("SHOW TABLES LIKE 'employees'");
    if (!$t || $t->num_rows === 0) {
        return;
    }

    $has = static function (mysqli $mysqli, string $field): bool {
        if (!preg_match('/^[a-z0-9_]+$/i', $field)) {
            return false;
        }
        $r = $mysqli->query("SHOW COLUMNS FROM `employees` LIKE '" . $mysqli->real_escape_string($field) . "'");
        return $r && $r->num_rows > 0;
    };

    if (!$has($mysqli, 'secondary_workplace')) {
        if ($has($mysqli, 'address')) {
            $mysqli->query("ALTER TABLE `employees` ADD COLUMN `secondary_workplace` TEXT NULL DEFAULT NULL AFTER `address`");
        } else {
            $mysqli->query("ALTER TABLE `employees` ADD COLUMN `secondary_workplace` TEXT NULL DEFAULT NULL");
        }
    }

    if (!$has($mysqli, 'emergency_contact_name')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `emergency_contact_name` VARCHAR(255) NULL DEFAULT NULL");
    }
    if (!$has($mysqli, 'emergency_contact_phone')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `emergency_contact_phone` VARCHAR(50) NULL DEFAULT NULL");
    }
    if (!$has($mysqli, 'emergency_contact_relationship')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `emergency_contact_relationship` VARCHAR(100) NULL DEFAULT NULL");
    }
    if (!$has($mysqli, 'emergency_contact_address')) {
        if ($has($mysqli, 'emergency_contact_relationship')) {
            $mysqli->query("ALTER TABLE `employees` ADD COLUMN `emergency_contact_address` TEXT NULL DEFAULT NULL AFTER `emergency_contact_relationship`");
        } else {
            $mysqli->query("ALTER TABLE `employees` ADD COLUMN `emergency_contact_address` TEXT NULL DEFAULT NULL");
        }
    }

    if (!$has($mysqli, 'sss')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `sss` VARCHAR(50) NULL DEFAULT NULL");
    }
    if (!$has($mysqli, 'nbi_clearance')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `nbi_clearance` VARCHAR(200) NULL DEFAULT NULL");
    }
    if (!$has($mysqli, 'police_clearance')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `police_clearance` VARCHAR(200) NULL DEFAULT NULL");
    }

    if (!$has($mysqli, 'date_inactive')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `date_inactive` DATE NULL DEFAULT NULL");
    }
    if (!$has($mysqli, 'resignation_letter_path')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `resignation_letter_path` VARCHAR(500) NULL DEFAULT NULL");
    }

    if (!$has($mysqli, 'performance_review_supervisor')) {
        $mysqli->query("ALTER TABLE `employees` ADD COLUMN `performance_review_supervisor` TINYINT(1) NOT NULL DEFAULT 0");
    }
}
