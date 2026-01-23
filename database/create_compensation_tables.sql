-- Create employee_bank_details table (only 1 per employee)
CREATE TABLE IF NOT EXISTS `employee_bank_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Savings','Checking','Current') DEFAULT 'Savings',
  `branch` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_bank` (`employee_id`),
  KEY `idx_employee_id` (`employee_id`),
  CONSTRAINT `fk_bank_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create employee_compensation table
CREATE TABLE IF NOT EXISTS `employee_compensation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `basic_salary_monthly` decimal(10,2) DEFAULT NULL,
  `basic_salary_daily` decimal(10,2) DEFAULT NULL,
  `basic_salary_annually` decimal(10,2) DEFAULT NULL,
  `employment_type` enum('Regular','Contractual','Probationary','Part-time') DEFAULT 'Regular',
  `effective_date` date NOT NULL,
  `allowance_internet` decimal(10,2) DEFAULT 0.00,
  `allowance_meal` decimal(10,2) DEFAULT 0.00,
  `allowance_position` decimal(10,2) DEFAULT 0.00,
  `allowance_transportation` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_compensation` (`employee_id`),
  KEY `idx_employee_id` (`employee_id`),
  CONSTRAINT `fk_compensation_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create employee_salary_adjustments table (history)
CREATE TABLE IF NOT EXISTS `employee_salary_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `previous_salary` decimal(10,2) NOT NULL,
  `new_salary` decimal(10,2) NOT NULL,
  `reason` enum('Promotion','Annual Increase','Adjustment','Other') DEFAULT 'Adjustment',
  `approved_by` varchar(255) DEFAULT NULL,
  `date_approved` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_date_approved` (`date_approved`),
  CONSTRAINT `fk_adjustment_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
