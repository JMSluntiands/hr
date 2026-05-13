<?php

require_once __DIR__ . '/../inventory/database/setup_inventory_decommission_requests_table.php';

if (!function_exists('hr_employee_can_review_decommission_requests')) {
    /**
     * Supervisors: employment type name contains "supervisor", or performance_review_supervisor flag.
     */
    function hr_employee_can_review_decommission_requests(mysqli $conn, int $employeeDbId): bool
    {
        if ($employeeDbId <= 0) {
            return false;
        }

        $hasPrs = $conn->query("SHOW COLUMNS FROM employees LIKE 'performance_review_supervisor'");
        $prsSelect = ($hasPrs && $hasPrs->num_rows > 0)
            ? 'COALESCE(e.performance_review_supervisor, 0) AS prs'
            : '0 AS prs';

        $hasEt = $conn->query("SHOW COLUMNS FROM employees LIKE 'employment_type_id'");
        if ($hasEt && $hasEt->num_rows > 0) {
            $sql = "SELECT {$prsSelect}, et.name AS employment_type_name
                    FROM employees e
                    LEFT JOIN employment_types et ON et.id = e.employment_type_id
                    WHERE e.id = ?
                    LIMIT 1";
        } else {
            $sql = "SELECT {$prsSelect}, NULL AS employment_type_name FROM employees e WHERE e.id = ? LIMIT 1";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $employeeDbId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }
        if (!empty($row['prs'])) {
            return true;
        }
        $et = strtolower(trim((string)($row['employment_type_name'] ?? '')));

        return $et !== '' && strpos($et, 'supervisor') !== false;
    }
}

if (!function_exists('inventory_decommission_save_upload')) {
    /**
     * @return string|null Relative path like uploads/inventory_decommission/… or null if no file
     */
    function inventory_decommission_save_upload(int $userId): ?string
    {
        if (empty($_FILES['attachment_proof']['name']) || (int)($_FILES['attachment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $f = $_FILES['attachment_proof'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return '__error_upload__';
        }
        if ($f['size'] > 8 * 1024 * 1024) {
            return '__error_size__';
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $okExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($ext, $okExt, true)) {
            return '__error_type__';
        }
        $dir = __DIR__ . '/../uploads/inventory_decommission/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $basename = 'decom_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . $basename;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            return '__error_save__';
        }

        return 'uploads/inventory_decommission/' . $basename;
    }
}
