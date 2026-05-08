<?php
/**
 * Ensures employment_types master table exists (and seeds defaults if empty).
 */
function ensure_employment_types_table(mysqli $mysqli): void
{
    $t = $mysqli->query("SHOW TABLES LIKE 'employment_types'");
    if (!$t || $t->num_rows === 0) {
        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS `employment_types` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(100) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`),
              KEY `idx_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
    $t2 = $mysqli->query("SHOW TABLES LIKE 'employment_types'");
    if (!$t2 || $t2->num_rows === 0) {
        return;
    }
    $chk = $mysqli->query("SELECT COUNT(*) AS c FROM `employment_types`");
    $count = 0;
    if ($chk && $row = $chk->fetch_assoc()) {
        $count = (int)$row['c'];
    }
    if ($count > 0) {
        return;
    }
    $stmt = $mysqli->prepare("INSERT INTO `employment_types` (`name`) VALUES (?)");
    if (!$stmt) {
        return;
    }
    $initial = ['Regular Employee', 'Contractor'];
    foreach ($initial as $type) {
        $name = $type;
        $stmt->bind_param('s', $name);
        $stmt->execute();
    }
    $stmt->close();
}
