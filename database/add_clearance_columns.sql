-- Add NBI and Police clearance columns to employees table (if missing)
-- Run this in phpMyAdmin (SQL tab) or: mysql -u root your_database < add_clearance_columns.sql
-- If you get "Duplicate column name", the columns already exist and you can ignore.

ALTER TABLE `employees`
  ADD COLUMN `nbi_clearance` varchar(50) DEFAULT NULL AFTER `tin`;
ALTER TABLE `employees`
  ADD COLUMN `police_clearance` varchar(50) DEFAULT NULL AFTER `nbi_clearance`;
