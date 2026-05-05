<?php

/**
 * Ensures incident_reports table exists (idempotent).
 */
function ensureIncidentReportsTable(mysqli $conn): bool
{
    if (!$conn) {
        return false;
    }
    $sql = "CREATE TABLE IF NOT EXISTS `incident_reports` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `submitted_by_user_id` int(11) NOT NULL,
        `company_name` varchar(255) NOT NULL DEFAULT '',
        `employee_name` varchar(255) NOT NULL DEFAULT '',
        `location_area` varchar(255) NOT NULL DEFAULT '',
        `incident_date` date NOT NULL,
        `incident_time` varchar(32) NOT NULL DEFAULT '',
        `incident_type` varchar(160) NOT NULL,
        `incident_details` text NOT NULL,
        `witness_name` varchar(255) NOT NULL DEFAULT '',
        `anyone_injured` enum('No','Yes') NOT NULL DEFAULT 'No',
        `injury_types` varchar(500) DEFAULT NULL,
        `injury_details` text,
        `report_date` date NOT NULL,
        `report_time` varchar(32) NOT NULL DEFAULT '',
        `action_taken` text,
        `attachment_path` varchar(500) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_submitter` (`submitted_by_user_id`),
        KEY `idx_incident_date` (`incident_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!(bool) $conn->query($sql)) {
        return false;
    }
    return incidentReportEnsureApprovalColumns($conn);
}

function incidentReportEnsureApprovalColumns(mysqli $conn): bool
{
    $columns = [];
    $res = $conn->query("SHOW COLUMNS FROM `incident_reports`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $name = (string)($row['Field'] ?? '');
            if ($name !== '') {
                $columns[$name] = true;
            }
        }
    }

    $ok = true;
    if (!isset($columns['review_status'])) {
        $ok = $ok && (bool)$conn->query("ALTER TABLE `incident_reports` ADD COLUMN `review_status` ENUM('Pending','Approved','Declined') NOT NULL DEFAULT 'Pending' AFTER `attachment_path`");
    }
    if (!isset($columns['reviewed_by_user_id'])) {
        $ok = $ok && (bool)$conn->query("ALTER TABLE `incident_reports` ADD COLUMN `reviewed_by_user_id` INT(11) DEFAULT NULL AFTER `review_status`");
    }
    if (!isset($columns['reviewed_at'])) {
        $ok = $ok && (bool)$conn->query("ALTER TABLE `incident_reports` ADD COLUMN `reviewed_at` DATETIME DEFAULT NULL AFTER `reviewed_by_user_id`");
    }
    return $ok;
}

function incidentReportAllowedTypes(): array
{
    return array_keys(incidentReportTypeDescriptions());
}

function incidentReportTypeDescriptions(): array
{
    return [
        'Safety & Health' => [
            'Physical injury or accident (on-site)',
            'Ergonomic or home office-related injury (remote)',
            'Mental health or wellbeing concern',
        ],
        'Near Miss' => [
            'An event that could have resulted in harm but did not',
        ],
        'Property & Equipment Damage' => [
            'Damage to office or company-issued equipment',
            'Damage to personal equipment used for work',
            'Power or utility disruption affecting work',
        ],
        'Security Breach' => [
            'Unauthorized physical access to office premises',
            'Unauthorized access to home workspace or work materials',
        ],
        'Confidentiality / Data Breach' => [
            'Improper sharing of sensitive or confidential information',
            'Data exposed via unsecured network or public environment (remote)',
            'Accidental disclosure through messaging, email, or screen sharing',
        ],
        'IT / System Failure' => [
            'Network or internet outage',
            'Hardware or software malfunction',
            'Unauthorized VPN, remote access, or cloud platform failure',
        ],
        'Misconduct / Policy Violation' => [
            'Breach of workplace code of conduct',
            'Non-compliance with remote work policy',
            'Unauthorized use of company data, tools, or systems',
        ],
        'Workplace Harassment / Bullying' => [
            'Occurring in person, via messaging, email, or video calls',
        ],
        'Communication or Coordination' => [
            'Miscommunication leading to errors or missed deadlines',
            'Failure to escalate or report critical information',
        ],
        'Environmental Incident' => [
            'Unsafe working conditions on-site or at a remote location',
        ],
        'Others' => [
            "Any incident that doesn't fall under the categories above",
        ],
    ];
}
