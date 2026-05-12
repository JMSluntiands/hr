-- COE request options. Run each line once on existing DBs (ignore duplicate column errors),
-- or rely on `include/ensure_document_requests_coe_columns.php` which adds columns automatically.

ALTER TABLE `document_requests`
  ADD COLUMN `coe_purpose` VARCHAR(128) NULL DEFAULT NULL AFTER `document_type`;

ALTER TABLE `document_requests`
  ADD COLUMN `coe_include_salary` ENUM('Yes','No') NULL DEFAULT NULL AFTER `coe_purpose`;
