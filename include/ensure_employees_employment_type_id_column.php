<?php
/**
 * Ensures employees.employment_type_id exists (links to employment_types.id).
 */
function ensure_employees_employment_type_id_column(mysqli $mysqli): void
{
    $t = $mysqli->query("SHOW TABLES LIKE 'employees'");
    if (!$t || $t->num_rows === 0) {
        return;
    }
    $c = $mysqli->query("SHOW COLUMNS FROM `employees` LIKE 'employment_type_id'");
    if ($c && $c->num_rows > 0) {
        return;
    }
    $ok = $mysqli->query(
        "ALTER TABLE `employees`
         ADD COLUMN `employment_type_id` INT(11) NULL DEFAULT NULL AFTER `department`,
         ADD KEY `idx_employment_type_id` (`employment_type_id`)"
    );
    if ($ok) {
        return;
    }
    $mysqli->query(
        "ALTER TABLE `employees` ADD COLUMN `employment_type_id` INT(11) NULL DEFAULT NULL"
    );
    $idx = $mysqli->query("SHOW INDEX FROM `employees` WHERE Key_name = 'idx_employment_type_id'");
    if (!$idx || $idx->num_rows === 0) {
        $mysqli->query("ALTER TABLE `employees` ADD KEY `idx_employment_type_id` (`employment_type_id`)");
    }
}
