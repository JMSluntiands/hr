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
    return (bool) $conn->query($sql);
}

function incidentReportAllowedTypes(): array
{
    return [
        'Accident Report',
        'Hazzard Report',
        'Near Miss Report',
        'Fire Incident Report',
        'Health and Safety Risk Assessment Report',
        'Facility Inspection Report',
        'Slip and Fall Incident Report',
    ];
}
