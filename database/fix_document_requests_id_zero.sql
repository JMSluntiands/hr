-- Fix document_requests with id = 0 (invalid primary key; Approve sends id 0 → "Missing document request reference")
-- Run statements in order in phpMyAdmin (SQL tab).

SET @next_id := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `document_requests`);
UPDATE `document_requests` SET `id` = @next_id WHERE `id` = 0 LIMIT 1;

ALTER TABLE `document_requests` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;

SET @ai := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `document_requests`);
ALTER TABLE `document_requests` AUTO_INCREMENT = @ai;
