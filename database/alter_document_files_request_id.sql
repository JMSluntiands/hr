-- Optional manual migration (otherwise opening any page that calls ensure_document_files_request_link() runs ALTER for you).
-- Links document_files rows to document_requests.id so each COE row can have its own PDF.

ALTER TABLE `document_files` ADD COLUMN `document_request_id` INT NULL DEFAULT NULL AFTER `employee_id`;
ALTER TABLE `document_files` ADD KEY `idx_document_request_id` (`document_request_id`);
