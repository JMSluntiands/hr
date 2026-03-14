-- Add emergency_contact_relationship to employees table
-- Run in phpMyAdmin (SQL tab) if the column is missing.

ALTER TABLE `employees`
  ADD COLUMN `emergency_contact_relationship` varchar(100) DEFAULT NULL AFTER `emergency_contact_phone`;
