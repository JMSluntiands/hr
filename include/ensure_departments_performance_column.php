<?php
/**
 * Ensures the departments master table exists and has additional_performance_review.
 * Safe to call from admin department pages on every request.
 */
function ensure_departments_performance_column($mysqli)
{
    if (!$mysqli instanceof mysqli) {
        return;
    }
    $t = $mysqli->query("SHOW TABLES LIKE 'departments'");
    if (!$t || $t->num_rows === 0) {
        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS `departments` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(100) NOT NULL,
              `additional_performance_review` TINYINT(1) NOT NULL DEFAULT 0,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`),
              KEY `idx_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        return;
    }
    $c = $mysqli->query("SHOW COLUMNS FROM `departments` LIKE 'additional_performance_review'");
    if ($c && $c->num_rows > 0) {
        return;
    }
    $ok = $mysqli->query(
        "ALTER TABLE `departments` ADD COLUMN `additional_performance_review` TINYINT(1) NOT NULL DEFAULT 0 AFTER `name`"
    );
    if (!$ok) {
        $mysqli->query(
            "ALTER TABLE `departments` ADD COLUMN `additional_performance_review` TINYINT(1) NOT NULL DEFAULT 0"
        );
    }
}
