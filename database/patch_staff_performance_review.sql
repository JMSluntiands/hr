-- Staff Performance Review: department flag + submissions table
-- Run once against your HR database (phpMyAdmin or mysql CLI).

ALTER TABLE `departments`
  ADD COLUMN `additional_performance_review` TINYINT(1) NOT NULL DEFAULT 0
  AFTER `name`;

CREATE TABLE IF NOT EXISTS `staff_performance_reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `review_date` DATE NOT NULL,
  `staff_name` VARCHAR(255) NOT NULL,
  `supervisor_name` VARCHAR(255) NOT NULL,
  `accuracy_rating` TINYINT NOT NULL,
  `accuracy_explanation` TEXT NOT NULL,
  `cross_ref_rating` TINYINT NOT NULL,
  `cross_ref_explanation` TEXT NOT NULL,
  `comprehension_rating` TINYINT NOT NULL,
  `comprehension_explanation` TEXT NOT NULL,
  `teamwork_support_rating` TINYINT NOT NULL,
  `teamwork_support_explanation` TEXT NOT NULL,
  `initiative_learning_rating` TINYINT NOT NULL,
  `initiative_learning_explanation` TEXT NOT NULL,
  `daily_output_rating` TINYINT NOT NULL,
  `daily_output_explanation` TEXT NOT NULL,
  `task_management_rating` TINYINT NOT NULL,
  `task_management_explanation` TEXT NOT NULL,
  `communication_delays_rating` TINYINT NOT NULL,
  `communication_delays_explanation` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_review_date` (`review_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
