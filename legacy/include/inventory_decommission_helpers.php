<?php

require_once __DIR__ . '/datetime_helpers.php';

require_once __DIR__ . '/../inventory/database/setup_inventory_decommission_requests_table.php';
require_once __DIR__ . '/../inventory/database/mysqli-stmt-fetch.php';

if (!function_exists('inventory_decommission_html_escape')) {
    /**
     * Escape for HTML output; ENT_SUBSTITUTE avoids ValueError on invalid UTF-8 (PHP 8.2+).
     */
    function inventory_decommission_html_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

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
        $row = inventory_stmt_fetch_one_assoc($stmt);
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
    function inventory_decommission_save_upload(int $userId)
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

if (!function_exists('inventory_decommission_format_datetime_manila')) {
    function inventory_decommission_format_datetime_manila($mysqlDatetime, string $format = 'M d, Y h:i A'): string
    {
        return hr_format_datetime_manila($mysqlDatetime, $format);
    }
}

if (!function_exists('inventory_decommission_format_date_manila')) {
    function inventory_decommission_format_date_manila($mysqlDate, string $format = 'M d, Y'): string
    {
        return hr_format_date_manila($mysqlDate, $format);
    }
}

if (!function_exists('inventory_decommission_decode_attachment_paths_json')) {
    /**
     * @return list<string> Relative paths stored via inventory_decommission_save_multi_uploads()
     */
    function inventory_decommission_decode_attachment_paths_json($json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $p) {
            $s = trim((string)$p);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return $out;
    }
}

if (!function_exists('inventory_decommission_format_attachments_html_from_paths')) {
    /**
     * @param list<string> $paths
     */
    function inventory_decommission_format_attachments_html_from_paths(array $paths, string $hrefPrefix = '../'): string
    {
        if ($paths === []) {
            return '—';
        }
        $html = '';
        $i = 0;
        foreach ($paths as $p) {
            $i++;
            $safe = inventory_decommission_html_escape($p);
            $bn = inventory_decommission_html_escape(basename($p));
            $pref = inventory_decommission_html_escape($hrefPrefix);
            $html .= '<div><a class="text-[#FA9800] underline font-medium" href="' . $pref . $safe . '" target="_blank" rel="noopener">Image ' . $i . ' — ' . $bn . '</a></div>';
        }

        return $html;
    }
}

if (!function_exists('inventory_decommission_format_attachments_html')) {
    function inventory_decommission_format_attachments_html($json, string $hrefPrefix = '../'): string
    {
        return inventory_decommission_format_attachments_html_from_paths(
            inventory_decommission_decode_attachment_paths_json($json),
            $hrefPrefix
        );
    }
}

if (!function_exists('inventory_decommission_save_multi_uploads')) {
    /**
     * Save multiple image uploads from a single form field (use name="test_1_attachments[]" multiple).
     *
     * @return array{ok: bool, paths?: list<string>, error?: string}
     */
    function inventory_decommission_save_multi_uploads(int $userId, string $inputName): array
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxEachBytes = 8 * 1024 * 1024;
        $maxFiles = 20;

        if (!isset($_FILES[$inputName])) {
            return ['ok' => true, 'paths' => []];
        }

        $f = $_FILES[$inputName];
        $entries = [];
        if (is_array($f['name'])) {
            $n = count($f['name']);
            for ($i = 0; $i < $n; $i++) {
                if ((int)($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $entries[] = [
                    'name' => (string)($f['name'][$i] ?? ''),
                    'type' => (string)($f['type'][$i] ?? ''),
                    'tmp_name' => (string)($f['tmp_name'][$i] ?? ''),
                    'error' => (int)($f['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int)($f['size'][$i] ?? 0),
                ];
            }
        } else {
            if ((int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $entries[] = [
                    'name' => (string)($f['name'] ?? ''),
                    'type' => (string)($f['type'] ?? ''),
                    'tmp_name' => (string)($f['tmp_name'] ?? ''),
                    'error' => (int)($f['error'] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int)($f['size'] ?? 0),
                ];
            }
        }

        if ($entries === []) {
            return ['ok' => true, 'paths' => []];
        }

        if (count($entries) > $maxFiles) {
            return ['ok' => false, 'error' => 'too_many'];
        }

        $dir = __DIR__ . '/../uploads/inventory_decommission/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $paths = [];
        foreach ($entries as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['ok' => false, 'error' => 'upload'];
            }
            if ($file['size'] > $maxEachBytes) {
                return ['ok' => false, 'error' => 'size'];
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                return ['ok' => false, 'error' => 'type'];
            }
            $basename = 'decom_' . $userId . '_' . str_replace(['.', ' '], '', uniqid('', true)) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $dir . $basename;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                return ['ok' => false, 'error' => 'save'];
            }
            $paths[] = 'uploads/inventory_decommission/' . $basename;
        }

        return ['ok' => true, 'paths' => $paths];
    }
}

if (!function_exists('inventory_finalize_decommission_approved_request')) {
    /**
     * After a decommission request is approved: close the employee allocation and mark the inventory item
     * decommissioned so it is excluded from the main list and from new allocations.
     */
    function inventory_finalize_decommission_approved_request(mysqli $conn, int $requestId, string $resolutionRemark = ''): bool
    {
        require_once __DIR__ . '/../inventory/database/setup_inventory_items_table.php';
        ensureInventoryItemsTable($conn);

        $st = $conn->prepare("SELECT inventory_item_allocation_id FROM inventory_decommission_requests WHERE id = ? AND status = 'approved' LIMIT 1");
        if (!$st) {
            return false;
        }
        $st->bind_param('i', $requestId);
        $st->execute();
        $row = inventory_stmt_fetch_one_assoc($st);
        $st->close();
        if (!$row) {
            return true;
        }
        $allocId = (int)($row['inventory_item_allocation_id'] ?? 0);
        if ($allocId <= 0) {
            return true;
        }

        $sa = $conn->prepare('SELECT inventory_item_id FROM inventory_item_allocations WHERE id = ? LIMIT 1');
        if (!$sa) {
            return false;
        }
        $sa->bind_param('i', $allocId);
        $sa->execute();
        $arow = inventory_stmt_fetch_one_assoc($sa);
        $sa->close();
        if (!$arow) {
            return true;
        }
        $inventoryItemId = (int)($arow['inventory_item_id'] ?? 0);
        if ($inventoryItemId <= 0) {
            return true;
        }

        $returnRemarks = 'Decommission approved (request #' . $requestId . ').';
        $trimRemark = trim($resolutionRemark);
        if ($trimRemark !== '') {
            $noteSnippet = function_exists('mb_substr')
                ? mb_substr($trimRemark, 0, 400, 'UTF-8')
                : substr($trimRemark, 0, 400);
            $returnRemarks .= ' Note: ' . $noteSnippet;
        }

        $u1 = $conn->prepare('UPDATE inventory_item_allocations SET date_return = CURDATE(), return_remarks = ? WHERE id = ? AND date_return IS NULL');
        if (!$u1) {
            return false;
        }
        $u1->bind_param('si', $returnRemarks, $allocId);
        if (!$u1->execute()) {
            $u1->close();

            return false;
        }
        $u1->close();

        $u2 = $conn->prepare("UPDATE inventory_items SET decommissioned_at = COALESCE(decommissioned_at, NOW()), item_condition = 'Decommissioned' WHERE id = ?");
        if (!$u2) {
            return false;
        }
        $u2->bind_param('i', $inventoryItemId);
        $ok2 = $u2->execute();
        $u2->close();

        return (bool)$ok2;
    }
}
