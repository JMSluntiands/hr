<?php
/**
 * document_files table + document_request_id column for issued request PDFs.
 */

function ensure_document_files_table(mysqli $conn): void
{
    $t = $conn->query("SHOW TABLES LIKE 'document_files'");
    if ($t && $t->num_rows > 0) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `document_files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `document_request_id` int(11) DEFAULT NULL,
        `document_type` varchar(100) NOT NULL,
        `file_path` varchar(500) NOT NULL,
        `approved_by` int(11) DEFAULT NULL,
        `approved_by_name` varchar(255) DEFAULT NULL,
        `approved_at` timestamp NULL DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_employee_id` (`employee_id`),
        KEY `idx_document_type` (`document_type`),
        KEY `idx_document_request_id` (`document_request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function document_files_table_exists(mysqli $conn): bool
{
    $t = $conn->query("SHOW TABLES LIKE 'document_files'");

    return $t && $t->num_rows > 0;
}

function document_files_has_request_link_column(mysqli $conn): bool
{
    if (! document_files_table_exists($conn)) {
        return false;
    }
    $c = $conn->query("SHOW COLUMNS FROM `document_files` LIKE 'document_request_id'");

    return $c && $c->num_rows > 0;
}

function ensure_document_files_request_link(mysqli $conn): void
{
    ensure_document_files_table($conn);
    if (document_files_has_request_link_column($conn)) {
        return;
    }
    $conn->query('ALTER TABLE `document_files` ADD COLUMN `document_request_id` INT NULL DEFAULT NULL AFTER `employee_id`');
    $conn->query('ALTER TABLE `document_files` ADD KEY `idx_document_request_id` (`document_request_id`)');
}
