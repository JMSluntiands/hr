<?php
/**
 * Certificate PDF: COE is auto-generated from HR data (see include/generate_coe_pdf.php).
 * Other types: copy from uploads/certificate_templates/ when a file exists there.
 * If no template file exists, fall back to the latest approved employee_document_uploads path for this type.
 */

/**
 * Absolute path to a PDF template for this document_requests.document_type, or null.
 */
function certificate_template_abs_path(string $documentType): ?string
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'certificate_templates';
    if (!is_dir($base)) {
        return null;
    }
    $dt = trim($documentType);
    $slug = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $dt);
    $candidates = [];
    if ($dt === 'COE') {
        $candidates[] = $base . DIRECTORY_SEPARATOR . 'COE.pdf';
    }
    $candidates[] = $base . DIRECTORY_SEPARATOR . $slug . '.pdf';

    $seen = [];
    foreach ($candidates as $p) {
        if (isset($seen[$p])) {
            continue;
        }
        $seen[$p] = true;
        if (is_file($p)) {
            return $p;
        }
    }
    return null;
}

/**
 * Copy master template into employee_documents/ for this request. Returns relative path under uploads/, or null.
 */
function copy_certificate_template_to_request_file(int $employeeId, int $requestId, string $docType): ?string
{
    $tpl = certificate_template_abs_path($docType);
    if ($tpl === null || !is_file($tpl)) {
        return null;
    }
    $ext = strtolower(pathinfo($tpl, PATHINFO_EXTENSION));
    if ($ext !== 'pdf' && $ext !== 'png' && $ext !== 'jpg' && $ext !== 'jpeg') {
        return null;
    }
    $employeeDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'employee_documents';
    if (!is_dir($employeeDir) && !mkdir($employeeDir, 0755, true)) {
        return null;
    }
    $basename = $employeeId . '_req' . $requestId . '_from_template_' . time() . '.' . $ext;
    $destAbs = $employeeDir . DIRECTORY_SEPARATOR . $basename;
    if (!@copy($tpl, $destAbs)) {
        return null;
    }
    return 'employee_documents/' . $basename;
}

/**
 * Prefer generated COE PDF; otherwise copy static template from uploads/certificate_templates/.
 */
function document_request_auto_issued_relative_path(
    mysqli $conn,
    int $employeeId,
    int $requestId,
    string $docType
): ?string {
    require_once __DIR__ . '/generate_coe_pdf.php';
    if (coe_document_type_is_coe($docType)) {
        $gen = generate_coe_pdf_for_document_request($conn, $employeeId, $requestId);
        if ($gen !== null && $gen !== '') {
            return $gen;
        }
    }
    return copy_certificate_template_to_request_file($employeeId, $requestId, $docType);
}

/**
 * For approved requests with no usable linked file, generate COE PDF or copy static template
 * (fixes rows approved before auto-link, deleted files, or missing first-time copy).
 */
function sync_missing_template_links_for_employee(mysqli $conn, int $employeeId): int
{
    $checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
    $colLink = @$conn->query("SHOW COLUMNS FROM document_files LIKE 'document_request_id'");
    if (!$checkTable || $checkTable->num_rows === 0 || !$colLink || $colLink->num_rows === 0) {
        return 0;
    }
    $tchk = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if (!$tchk || $tchk->num_rows === 0) {
        return 0;
    }
    $eid = (int)$employeeId;
    $res = $conn->query(
        "SELECT dr.id AS rid, dr.document_type FROM document_requests dr WHERE dr.employee_id = {$eid} AND dr.status = 'Approved' ORDER BY dr.id ASC"
    );
    if (!$res) {
        return 0;
    }
    $uploadsRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    $pathOk = static function (string $rel) use ($uploadsRoot): bool {
        $rel = trim($rel);
        if ($rel === '' || strpos($rel, '..') !== false) {
            return false;
        }
        $rel = preg_replace('#^uploads[/\\\\]+#i', '', str_replace('\\', '/', $rel));
        $rel = ltrim($rel, '/');
        $full = $uploadsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($full);
    };

    $linked = 0;
    while ($row = $res->fetch_assoc()) {
        $rid = (int)($row['rid'] ?? 0);
        $dtype = (string)($row['document_type'] ?? '');
        if ($rid <= 0 || $dtype === '') {
            continue;
        }

        $sel = $conn->prepare('SELECT id, file_path FROM document_files WHERE document_request_id = ? ORDER BY id DESC LIMIT 1');
        if (!$sel) {
            continue;
        }
        $sel->bind_param('i', $rid);
        $sel->execute();
        $exRes = $sel->get_result();
        $ex = $exRes ? $exRes->fetch_assoc() : null;
        $sel->close();

        if ($ex) {
            $fp = trim((string)($ex['file_path'] ?? ''));
            if ($fp !== '' && $pathOk($fp)) {
                continue;
            }
            $newRel = document_request_auto_issued_relative_path($conn, $eid, $rid, $dtype);
            if ($newRel === null) {
                continue;
            }
            $exId = (int)($ex['id'] ?? 0);
            if ($exId > 0) {
                $upd = $conn->prepare('UPDATE document_files SET file_path = ?, document_type = ?, approved_at = NOW() WHERE id = ?');
                if ($upd) {
                    $upd->bind_param('ssi', $newRel, $dtype, $exId);
                    if ($upd->execute()) {
                        $linked++;
                    }
                    $upd->close();
                }
            }
            continue;
        }

        $newRel = document_request_auto_issued_relative_path($conn, $eid, $rid, $dtype);
        if ($newRel === null) {
            continue;
        }
        $ins = $conn->prepare(
            'INSERT INTO document_files (employee_id, document_request_id, document_type, file_path, approved_at, created_at) VALUES (?, ?, ?, ?, NOW(), NOW())'
        );
        if ($ins) {
            $ins->bind_param('iiss', $eid, $rid, $dtype, $newRel);
            if ($ins->execute()) {
                $linked++;
            }
            $ins->close();
        }
    }
    return $linked;
}

/**
 * Save an already-generated relative path (under uploads/) to document_files for this request — does not run Dompdf again.
 */
function coe_register_generated_file_for_request(
    mysqli $conn,
    int $employeeId,
    int $requestId,
    string $docType,
    string $relativePathUnderUploads
): void {
    $relativePathUnderUploads = trim(str_replace('\\', '/', $relativePathUnderUploads));
    if ($relativePathUnderUploads === '' || strpos($relativePathUnderUploads, '..') !== false) {
        return;
    }
    $relativePathUnderUploads = preg_replace('#^uploads/#i', '', $relativePathUnderUploads);
    $relativePathUnderUploads = ltrim($relativePathUnderUploads, '/');

    $checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
    $colLink = @$conn->query("SHOW COLUMNS FROM document_files LIKE 'document_request_id'");
    if (!$checkTable || $checkTable->num_rows === 0 || !$colLink || $colLink->num_rows === 0) {
        return;
    }

    $uploadsRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    $pathOk = static function (string $rel) use ($uploadsRoot): bool {
        $rel = trim(str_replace('\\', '/', $rel));
        if ($rel === '' || strpos($rel, '..') !== false) {
            return false;
        }
        $rel = preg_replace('#^uploads/#i', '', $rel);
        $rel = ltrim($rel, '/');
        $full = $uploadsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($full);
    };

    $eid = (int)$employeeId;
    $rid = (int)$requestId;
    if ($eid <= 0 || $rid <= 0) {
        return;
    }

    $dtype = trim($docType);
    if ($dtype === '') {
        $dtype = 'COE';
    }

    $sel = $conn->prepare('SELECT id, file_path FROM document_files WHERE document_request_id = ? ORDER BY id DESC LIMIT 1');
    if (!$sel) {
        return;
    }
    $sel->bind_param('i', $rid);
    $sel->execute();
    $res = $sel->get_result();
    $ex = $res ? $res->fetch_assoc() : null;
    $sel->close();

    if ($ex) {
        $fp = trim((string)($ex['file_path'] ?? ''));
        $exId = (int)($ex['id'] ?? 0);
        if ($fp !== '' && $pathOk($fp)) {
            return;
        }
        if ($exId > 0) {
            $upd = $conn->prepare('UPDATE document_files SET employee_id = ?, document_type = ?, file_path = ?, approved_at = NOW() WHERE id = ?');
            if ($upd) {
                $upd->bind_param('issi', $eid, $dtype, $relativePathUnderUploads, $exId);
                $upd->execute();
                $upd->close();
            }
        }
        return;
    }

    $ins = $conn->prepare(
        'INSERT INTO document_files (employee_id, document_request_id, document_type, file_path, approved_at, created_at) VALUES (?, ?, ?, ?, NOW(), NOW())'
    );
    if ($ins) {
        $ins->bind_param('iiss', $eid, $rid, $dtype, $relativePathUnderUploads);
        $ins->execute();
        $ins->close();
    }
}

/**
 * On approve: COE PDF is generated from employee + request data; other types copy uploads/certificate_templates/
 * when present. If still no file, fall back to the latest approved employee_document_uploads path for this type.
 */
function ensure_issued_file_for_document_request(
    mysqli $conn,
    int $employeeId,
    int $requestId,
    string $docType,
    string $uploadRelativePath,
    int $adminId,
    string $adminName
): void {
    $checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
    $colLink = @$conn->query("SHOW COLUMNS FROM document_files LIKE 'document_request_id'");
    if (!$checkTable || $checkTable->num_rows === 0 || !$colLink || $colLink->num_rows === 0) {
        return;
    }

    $uploadRelativePath = trim($uploadRelativePath);
    $relativeForDb = document_request_auto_issued_relative_path($conn, $employeeId, $requestId, $docType) ?? '';

    if ($relativeForDb === '' && $uploadRelativePath !== '' && strpos($uploadRelativePath, '..') === false) {
        $relativeForDb = $uploadRelativePath;
    }

    if ($relativeForDb === '') {
        return;
    }

    $rqSel = $conn->prepare('SELECT id, file_path FROM document_files WHERE document_request_id = ? ORDER BY id DESC LIMIT 1');
    if (!$rqSel) {
        return;
    }
    $rqSel->bind_param('i', $requestId);
    $rqSel->execute();
    $rqRes = $rqSel->get_result();
    $rqRow = ($rqRes && $rqRes->num_rows > 0) ? $rqRes->fetch_assoc() : null;
    $rqSel->close();

    if ($rqRow) {
        $rqPath = trim((string)($rqRow['file_path'] ?? ''));
        $rqFileId = (int)($rqRow['id'] ?? 0);
        if ($rqPath !== '' || $rqFileId <= 0) {
            return;
        }
        $upd = $conn->prepare(
            'UPDATE document_files SET file_path = ?, document_type = ?, approved_by = ?, approved_by_name = ?, approved_at = NOW() WHERE id = ?'
        );
        if ($upd) {
            $upd->bind_param('ssisi', $relativeForDb, $docType, $adminId, $adminName, $rqFileId);
            $upd->execute();
            $upd->close();
        }
        return;
    }

    $insertStmt = $conn->prepare(
        'INSERT INTO document_files (employee_id, document_request_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
    );
    if ($insertStmt) {
        $insertStmt->bind_param('iissis', $employeeId, $requestId, $docType, $relativeForDb, $adminId, $adminName);
        $insertStmt->execute();
        $insertStmt->close();
    }
}
