<?php
/**
 * Links issued files to a specific document_requests row (employee "My Request" PDF column).
 */
function ensure_document_files_request_link(mysqli $conn): void
{
    $t = $conn->query("SHOW TABLES LIKE 'document_files'");
    if (!$t || $t->num_rows === 0) {
        return;
    }
    $c = $conn->query("SHOW COLUMNS FROM `document_files` LIKE 'document_request_id'");
    if ($c && $c->num_rows > 0) {
        return;
    }
    $conn->query('ALTER TABLE `document_files` ADD COLUMN `document_request_id` INT NULL DEFAULT NULL AFTER `employee_id`');
    $conn->query('ALTER TABLE `document_files` ADD KEY `idx_document_request_id` (`document_request_id`)');
}
