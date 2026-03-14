-- Add secondary_workplace column to employees table
-- Run in phpMyAdmin (SQL tab) if the column is missing.

ALTER TABLE `employees`
  ADD COLUMN `secondary_workplace` text DEFAULT NULL AFTER `address`;
