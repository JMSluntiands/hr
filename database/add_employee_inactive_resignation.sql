-- Date inactive and resignation letter when employee status is Inactive
ALTER TABLE `employees`
  ADD COLUMN `date_inactive` date DEFAULT NULL AFTER `status`,
  ADD COLUMN `resignation_letter_path` varchar(500) DEFAULT NULL AFTER `date_inactive`;
