<?php
/**
 * Adds COE-specific columns to document_requests when the table exists but predates COE options.
 */
function ensure_document_requests_coe_columns(mysqli $mysqli): void
{
    $t = $mysqli->query("SHOW TABLES LIKE 'document_requests'");
    if (!$t || $t->num_rows === 0) {
        return;
    }

    $has = static function (mysqli $mysqli, string $field): bool {
        if (!preg_match('/^[a-z0-9_]+$/i', $field)) {
            return false;
        }
        $r = $mysqli->query("SHOW COLUMNS FROM `document_requests` LIKE '" . $mysqli->real_escape_string($field) . "'");
        return $r && $r->num_rows > 0;
    };

    if (!$has($mysqli, 'coe_purpose')) {
        $mysqli->query("ALTER TABLE `document_requests` ADD COLUMN `coe_purpose` VARCHAR(128) NULL DEFAULT NULL AFTER `document_type`");
    }
    if (!$has($mysqli, 'coe_include_salary')) {
        if ($has($mysqli, 'coe_purpose')) {
            $mysqli->query("ALTER TABLE `document_requests` ADD COLUMN `coe_include_salary` ENUM('Yes','No') NULL DEFAULT NULL AFTER `coe_purpose`");
        } else {
            $mysqli->query("ALTER TABLE `document_requests` ADD COLUMN `coe_include_salary` ENUM('Yes','No') NULL DEFAULT NULL AFTER `document_type`");
        }
    }

    // Invalid id=0 breaks approve (forms send id 0). Repair data and ensure AUTO_INCREMENT on id.
    $needAiReset = false;
    $zero = $mysqli->query('SELECT COUNT(*) AS c FROM `document_requests` WHERE `id` = 0');
    if ($zero && ($z = $zero->fetch_assoc()) && (int)($z['c'] ?? 0) > 0) {
        $mxR = $mysqli->query('SELECT COALESCE(MAX(`id`), 0) AS m FROM `document_requests`');
        $m = 0;
        if ($mxR && $mxRow = $mxR->fetch_assoc()) {
            $m = (int)($mxRow['m'] ?? 0);
        }
        $nextId = $m + 1;
        $mysqli->query('UPDATE `document_requests` SET `id` = ' . (int)$nextId . ' WHERE `id` = 0 LIMIT 1');
        $needAiReset = true;
    }
    $idCol = $mysqli->query("SHOW COLUMNS FROM `document_requests` WHERE Field = 'id'");
    if ($idCol && $info = $idCol->fetch_assoc()) {
        $extra = strtolower((string)($info['Extra'] ?? ''));
        if (strpos($extra, 'auto_increment') === false) {
            $mysqli->query('ALTER TABLE `document_requests` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT');
            $needAiReset = true;
        }
    }
    if ($needAiReset) {
        $mxR2 = $mysqli->query('SELECT COALESCE(MAX(`id`), 0) AS mx FROM `document_requests`');
        $nextAi = 1;
        if ($mxR2 && $row2 = $mxR2->fetch_assoc()) {
            $nextAi = max(1, (int)($row2['mx'] ?? 0) + 1);
        }
        $mysqli->query('ALTER TABLE `document_requests` AUTO_INCREMENT = ' . (int)$nextAi);
    }
}
