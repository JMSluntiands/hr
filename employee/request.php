<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include '../database/db.php';
include 'include/employee_data.php';
require_once __DIR__ . '/../include/ensure_document_requests_coe_columns.php';
require_once __DIR__ . '/../include/ensure_document_files_request_link.php';
require_once __DIR__ . '/../include/ensure_document_request_issued_file.php';
if ($conn) {
    ensure_document_requests_coe_columns($conn);
    ensure_document_files_request_link($conn);
}

$certMsg = '';
$certMsgOk = false;
if (!empty($_SESSION['request_cert_msg'])) {
    $certMsg = (string)$_SESSION['request_cert_msg'];
    $certMsgOk = !empty($_SESSION['request_cert_msg_ok']);
    unset($_SESSION['request_cert_msg'], $_SESSION['request_cert_msg_ok']);
}

$roleLc = strtolower((string)($_SESSION['role'] ?? ''));
$employeeProfileUnlinked = ($roleLc === 'employee' && (!$employeeDbId || (int)$employeeDbId <= 0));

require_once __DIR__ . '/../include/generate_coe_pdf.php';

$coePreviewGender = null;
$coePreviewDateHiredYmd = '';
if ($employeeDbId && $conn) {
    $gq = $conn->prepare('SELECT gender, date_hired FROM employees WHERE id = ? LIMIT 1');
    if ($gq) {
        $eidPreview = (int)$employeeDbId;
        $gq->bind_param('i', $eidPreview);
        $gq->execute();
        $gr = $gq->get_result();
        $grow = $gr ? $gr->fetch_assoc() : null;
        $gq->close();
        if ($grow) {
            $coePreviewGender = $grow['gender'] ?? null;
            $coePreviewDateHiredYmd = trim((string)($grow['date_hired'] ?? ''));
        }
    }
}

$coePreviewComp = ($employeeDbId && $conn) ? coe_load_compensation($conn, (int)$employeeDbId) : null;
$coePreviewBasicStr = '';
$coePreviewItStr = '';
if (is_array($coePreviewComp)) {
    if (isset($coePreviewComp['basic']) && $coePreviewComp['basic'] !== null) {
        $coePreviewBasicStr = coe_money_php((float)$coePreviewComp['basic']);
    }
    if (isset($coePreviewComp['it_allowance']) && $coePreviewComp['it_allowance'] !== null) {
        $coePreviewItStr = coe_money_php((float)$coePreviewComp['it_allowance']);
    }
}

[$coePreviewSubj, $coePreviewPoss] = coe_pronouns($coePreviewGender);
$coePreviewBrand = coe_pdf_branding();
$coePreviewPayload = [
    'hasProfile' => !$employeeProfileUnlinked && (int)$employeeDbId > 0,
    'employeeName' => (string)$employeeName,
    'position' => (string)$position,
    'department' => (string)$department,
    'dateHiredLong' => $coePreviewDateHiredYmd !== '' ? coe_format_long_date($coePreviewDateHiredYmd) : '',
    'subjectPronoun' => $coePreviewSubj,
    'possPronoun' => $coePreviewPoss,
    'issueDate' => coe_format_long_date(date('Y-m-d')),
    'employerCert' => trim((string)($coePreviewBrand['employer_name_cert'] ?? 'the Company')) ?: 'the Company',
    'salaryBasic' => $coePreviewBasicStr,
    'salaryIt' => $coePreviewItStr,
    'brand' => [
        'accent' => (string)($coePreviewBrand['accent'] ?? '#E85D04'),
        'accent_dark' => (string)($coePreviewBrand['accent_dark'] ?? '#C2410C'),
        'company_primary' => (string)($coePreviewBrand['company_primary'] ?? ''),
        'company_secondary' => (string)($coePreviewBrand['company_secondary'] ?? ''),
        'tagline' => (string)($coePreviewBrand['tagline'] ?? ''),
        'contact_lines' => is_array($coePreviewBrand['contact_lines'] ?? null) ? $coePreviewBrand['contact_lines'] : [],
        'signatory_name' => (string)($coePreviewBrand['signatory_name'] ?? ''),
        'signatory_title' => (string)($coePreviewBrand['signatory_title'] ?? ''),
        'footer_company' => (string)($coePreviewBrand['footer_company'] ?? ''),
        'footer_address' => (string)($coePreviewBrand['footer_address'] ?? ''),
        'footer_registration' => (string)($coePreviewBrand['footer_registration'] ?? ''),
    ],
];
$coePreviewJson = json_encode($coePreviewPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

/**
 * Map document_requests.document_type to possible employee_document_uploads / document_files labels.
 */
$resolveUploadTypeAliases = static function (string $reqType): array {
    $reqType = trim($reqType);
    $map = [
        'COE' => ['COE', 'Certificate of Employment', 'Certificate of Employment (COE)', 'Certificate Of Employment'],
        'SSS Certificate' => ['SSS Certificate', 'SSS'],
        'Pag-IBIG Certificate' => ['Pag-IBIG Certificate', 'Pag-Ibig', 'Pag-IBIG'],
        'PhilHealth Certificate' => ['PhilHealth Certificate', 'Philhealth'],
    ];
    return $map[$reqType] ?? [$reqType];
};

/**
 * True if a row's document_type column matches the request row's type (exact, alias, or COE fuzzy).
 */
$docTypeMatchesRequest = static function (string $fileRowType, string $reqType, callable $aliases): bool {
    $fileRowType = trim($fileRowType);
    $reqType = trim($reqType);
    if ($fileRowType === '' || $reqType === '') {
        return false;
    }
    if (strcasecmp($fileRowType, $reqType) === 0) {
        return true;
    }
    foreach ($aliases($reqType) as $alias) {
        if (strcasecmp($fileRowType, trim($alias)) === 0) {
            return true;
        }
    }
    if ($reqType === 'COE') {
        $l = strtolower($fileRowType);
        if (strpos($l, 'coe') !== false || strpos($l, 'certificate of employment') !== false) {
            return true;
        }
    }
    return false;
};

$docHistory = [];
$docHistoryLoadError = '';

$uploadsBaseDir = realpath(__DIR__ . '/../uploads');
if ($uploadsBaseDir === false) {
    $uploadsBaseDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads';
}
$resolveDiskPath = static function (?string $filePath) use ($uploadsBaseDir): ?string {
    if ($filePath === null) {
        return null;
    }
    $raw = trim($filePath);
    if ($raw === '' || strpos($raw, '..') !== false) {
        return null;
    }
    if (preg_match('#^https?://#i', $raw)) {
        return null;
    }
    $p = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $raw);
    if (preg_match('#[\\\\/]uploads[\\\\/](.+)$#i', $p, $m)) {
        $p = $m[1];
    }
    $p = trim($p);
    while (strpos($p, 'uploads' . DIRECTORY_SEPARATOR) === 0 || strcasecmp($p, 'uploads') === 0) {
        if (strcasecmp($p, 'uploads') === 0) {
            $p = '';
            break;
        }
        $p = substr($p, strlen('uploads' . DIRECTORY_SEPARATOR));
    }
    $p = ltrim($p, DIRECTORY_SEPARATOR);
    if ($p === '' || strpos($p, '..') !== false) {
        return null;
    }
    $full = $uploadsBaseDir . DIRECTORY_SEPARATOR . $p;
    if (is_file($full)) {
        $rp = realpath($full);
        $baseRp = realpath($uploadsBaseDir);
        if ($baseRp !== false && $rp !== false && strncmp($rp, $baseRp, strlen($baseRp)) === 0) {
            return $rp;
        }
        return $full;
    }
    $fullNorm = realpath($full);
    if ($fullNorm !== false && is_file($fullNorm)) {
        $baseRp = realpath($uploadsBaseDir) ?: $uploadsBaseDir;
        if (strncmp($fullNorm, $baseRp, strlen($baseRp)) === 0) {
            return $fullNorm;
        }
    }
    return null;
};

if ($employeeDbId && $conn) {
    sync_missing_template_links_for_employee($conn, (int)$employeeDbId);
    $tchk = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($tchk && $tchk->num_rows > 0) {
        $eid = (int)$employeeDbId;
        $sql = 'SELECT dr.id, dr.document_type, dr.coe_purpose, dr.coe_include_salary, dr.status, dr.created_at
                FROM document_requests dr WHERE dr.employee_id = ' . $eid . ' ORDER BY dr.created_at DESC LIMIT 50';
        $hr = $conn->query($sql);
        if (!$hr) {
            $docHistoryLoadError = 'Could not load your request history. Please refresh or contact IT if this continues.';
        } else {
            $filesCache = [];
            $tf = $conn->query("SHOW TABLES LIKE 'document_files'");
            if ($tf && $tf->num_rows > 0) {
                $fc = $conn->query('SELECT id, document_type, file_path FROM document_files WHERE employee_id = ' . $eid . ' ORDER BY id DESC LIMIT 150');
                if ($fc) {
                    while ($x = $fc->fetch_assoc()) {
                        $filesCache[] = $x;
                    }
                }
            }

            $uploadsCache = [];
            $te = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
            if ($te && $te->num_rows > 0) {
                $uc = $conn->query(
                    "SELECT id, document_type, file_path FROM employee_document_uploads WHERE employee_id = " . $eid . " AND status = 'Approved' ORDER BY id DESC LIMIT 150"
                );
                if ($uc) {
                    while ($x = $uc->fetch_assoc()) {
                        $uploadsCache[] = $x;
                    }
                }
            }

            $hasReqLinkCol = false;
            $fileByReqStmt = null;
            if ($tf && $tf->num_rows > 0) {
                $crq = @$conn->query("SHOW COLUMNS FROM document_files LIKE 'document_request_id'");
                if ($crq && $crq->num_rows > 0) {
                    $hasReqLinkCol = true;
                    $fileByReqStmt = $conn->prepare('SELECT id, file_path FROM document_files WHERE document_request_id = ? ORDER BY id DESC LIMIT 25');
                }
            }

            while ($row = $hr->fetch_assoc()) {
                $requestPk = (int)($row['id'] ?? 0);
                $reqDocType = trim((string)($row['document_type'] ?? ''));
                $issuedId = 0;
                $issuedUploadId = 0;
                $issuedPath = '';
                $issuedSource = '';

                if ($fileByReqStmt) {
                    $fileByReqStmt->bind_param('i', $requestPk);
                    if ($fileByReqStmt->execute()) {
                        $rbf = $fileByReqStmt->get_result();
                        if ($rbf) {
                            while ($frow = $rbf->fetch_assoc()) {
                                $candPath = trim((string)($frow['file_path'] ?? ''));
                                if ($candPath === '' || strpos($candPath, '..') !== false) {
                                    continue;
                                }
                                if ($resolveDiskPath($candPath) !== null) {
                                    $issuedId = (int)($frow['id'] ?? 0);
                                    $issuedPath = $candPath;
                                    $issuedSource = 'files';
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($issuedPath === '') {
                foreach ($filesCache as $frow) {
                    if (!$docTypeMatchesRequest((string)($frow['document_type'] ?? ''), $reqDocType, $resolveUploadTypeAliases)) {
                        continue;
                    }
                    $candPath = trim((string)($frow['file_path'] ?? ''));
                    if ($candPath === '' || strpos($candPath, '..') !== false) {
                        continue;
                    }
                    if ($resolveDiskPath($candPath) !== null) {
                        $issuedId = (int)($frow['id'] ?? 0);
                        $issuedPath = $candPath;
                        $issuedSource = 'files';
                        break;
                    }
                }
                }

                if ($issuedPath === '') {
                    foreach ($uploadsCache as $urow) {
                        if (!$docTypeMatchesRequest((string)($urow['document_type'] ?? ''), $reqDocType, $resolveUploadTypeAliases)) {
                            continue;
                        }
                        $upPath = trim((string)($urow['file_path'] ?? ''));
                        if ($upPath === '' || strpos($upPath, '..') !== false) {
                            continue;
                        }
                        if ($resolveDiskPath($upPath) !== null) {
                            $issuedUploadId = (int)($urow['id'] ?? 0);
                            $issuedPath = $upPath;
                            $issuedSource = 'uploads';
                            break;
                        }
                    }
                }

                $canFile = ($row['status'] === 'Approved' && $issuedPath !== '');
                $ext = $canFile ? strtolower(pathinfo($issuedPath, PATHINFO_EXTENSION)) : '';
                $canCoePdf = (($row['status'] ?? '') === 'Approved' && coe_document_type_is_coe($reqDocType));
                $docHistory[] = [
                    'id' => (int)$row['id'],
                    'date' => !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '—',
                    'document_type' => $reqDocType,
                    'coe_purpose' => (string)($row['coe_purpose'] ?? ''),
                    'coe_include_salary' => (string)($row['coe_include_salary'] ?? ''),
                    'status' => (string)($row['status'] ?? ''),
                    'issued_file_id' => $issuedId,
                    'issued_upload_id' => $issuedUploadId,
                    'issued_source' => $issuedSource,
                    'can_download' => $canFile,
                    'can_coe_pdf' => $canCoePdf && !$canFile,
                    'is_pdf' => $ext === 'pdf',
                ];
            }
            if ($fileByReqStmt) {
                $fileByReqStmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#FA9800',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold">
                        <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate">
                    <?php echo htmlspecialchars($employeeName); ?>
                </span>
                <span class="text-[11px] text-white/80">
                    Employee
                </span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <!-- Sidebar (fixed) -->
    <?php require_once __DIR__ . '/../include/sidebar-scrollbar-once.php'; ?>
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-2">
            <!-- Dashboard -->
            <a href="index.php"
               data-url="index.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- My Profile -->
            <a href="profile.php"
               data-url="profile.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>My Profile</span>
            </a>
            <!-- My Leave Credits -->
            <a href="timeoff.php"
               data-url="timeoff.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>My Leave Credits</span>
            </a>
            <!-- My Request -->
            <a href="request.php"
               data-url="request.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>My Request</span>
            </a>
            <a href="reimbursement.php"
               data-url="reimbursement.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2m6 4H9m6-8H9m10 14H5a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z" />
                </svg>
                <span>My Reimbursement</span>
            </a>
            <!-- My Compensation -->
            <a href="compensation.php"
               data-url="compensation.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>My Compensation</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-my-inventory-nav.php'; ?>
            <?php include __DIR__ . '/include/sidebar-performance-nav.php'; ?>
            <a href="progressive-discipline.php"
               data-url="progressive-discipline.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <span>Progressive Discipline</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-incident-nav.php'; ?>
            <!-- Settings -->
            <a href="settings.php"
               data-url="settings.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="shrink-0 border-t border-white/20 p-4">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
    </aside>

    <!-- Mobile sidebar backdrop -->
    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Request</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Request and preview employment and government certificates.
                </p>
            </div>
            <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                <span><?php echo htmlspecialchars($department); ?></span>
                <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                <span><?php echo htmlspecialchars($position); ?></span>
            </div>
        </div>

        <?php if ($certMsg !== ''): ?>
        <div class="rounded-xl border px-4 py-3 text-sm <?php echo $certMsgOk ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-900 border-amber-200'; ?>">
            <?php echo htmlspecialchars($certMsg); ?>
        </div>
        <?php endif; ?>

        <?php if ($employeeProfileUnlinked): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm mb-6">
            Your login is not linked to an employee profile. Ask HR to match your email with your employee record before you can submit or view requests.
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-4">
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 lg:p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">New certificate request</h2>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button type="button" class="js-cert-btn inline-flex items-center px-3 py-1.5 rounded-full bg-[#FA9800] text-white text-xs font-medium shadow-sm" data-doc="coe">COE</button>
                        <button type="button" class="js-cert-btn inline-flex items-center px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium" data-doc="sss">SSS</button>
                        <button type="button" class="js-cert-btn inline-flex items-center px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium" data-doc="pagibig">Pag-IBIG</button>
                        <button type="button" class="js-cert-btn inline-flex items-center px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium" data-doc="philhealth">PhilHealth</button>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800" id="pdfTitle">Certificate of Employment (COE)</h3>
                    <p class="text-xs text-slate-500 mt-1 mb-3" id="pdfSubtitle">Fill the form below and submit. HR will review your request.</p>
                    <form action="request-document-submit.php" method="post" class="space-y-3 border-t border-slate-100 pt-3">
                        <input type="hidden" name="document_code" id="document_code_field" value="COE">
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Request details</p>
                        <div id="coeOptionsWrap" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label for="coe_purpose" class="block text-xs font-medium text-slate-600 mb-1">Purpose</label>
                                <select name="coe_purpose" id="coe_purpose" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800]/25 focus:border-[#FA9800] bg-white text-slate-800">
                                    <option value="">Select purpose</option>
                                    <option value="Bank Account">Bank Account</option>
                                    <option value="Loan and Credit Application">Loan and Credit Application</option>
                                    <option value="Visa and Travel Requirements">Visa and Travel Requirements</option>
                                    <option value="Employment">Employment</option>
                                    <option value="Job Application">Job Application</option>
                                    <option value="Government Transaction">Government Transaction</option>
                                    <option value="Rental">Rental</option>
                                    <option value="Leasing Agreement">Leasing Agreement</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div>
                                <label for="coe_include_salary" class="block text-xs font-medium text-slate-600 mb-1">Include salary?</label>
                                <select name="coe_include_salary" id="coe_include_salary" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800]/25 focus:border-[#FA9800] bg-white text-slate-800">
                                    <option value="">Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                        <p id="coeOptionsHint" class="text-xs text-slate-500 hidden">Required for COE: purpose and whether salary appears on the certificate.</p>
                        <p id="nonCoeHint" class="text-xs text-slate-500 hidden">Submit to queue this certificate with HR.</p>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#e08900] focus:outline-none focus:ring-2 focus:ring-[#FA9800]/40">
                            Submit request
                        </button>
                    </form>
                </section>
            </div>

            <div class="lg:col-span-8 min-w-0 space-y-6">
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">My request history</h2>
                        <p class="text-xs text-slate-500 mt-1"><strong>Download</strong> = official file from HR. <strong>Preview</strong> = same PDF with an on-screen &ldquo;PREVIEW&rdquo; watermark (not for external use).</p>
                        <p class="text-xs text-amber-900/90 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-2">Ang <strong>COE</strong> ay <strong>auto-generated na PDF</strong> mula sa employee records (pangalan, petsa ng hire, posisyon, purpose, at sahod kung pinili) pag na-approve. I-edit ang letterhead sa <code class="bg-white/80 px-1 rounded">config/coe_pdf_branding.php</code>. Iba pang sertipiko (SSS, Pag-IBIG, PhilHealth): optional na <code class="bg-white/80 px-1 rounded">uploads/certificate_templates/</code> (.pdf).</p>
                    </div>
                    <div class="overflow-x-auto">
                        <?php if ($employeeProfileUnlinked): ?>
                        <p class="px-4 sm:px-6 py-8 text-sm text-slate-500">Link your profile to see history.</p>
                        <?php elseif ($docHistoryLoadError !== ''): ?>
                        <p class="px-4 sm:px-6 py-8 text-sm text-amber-800 bg-amber-50 border-y border-amber-100"><?php echo htmlspecialchars($docHistoryLoadError); ?></p>
                        <?php elseif (empty($docHistory)): ?>
                        <p class="px-4 sm:px-6 py-8 text-sm text-slate-500">No requests yet. Submit a certificate using the form on the left.</p>
                        <?php else: ?>
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="text-left px-3 sm:px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date</th>
                                    <th class="text-left px-3 sm:px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Document</th>
                                    <th class="text-left px-3 sm:px-4 py-3 text-xs font-semibold text-slate-500 uppercase hidden md:table-cell">Purpose</th>
                                    <th class="text-left px-3 sm:px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Salary</th>
                                    <th class="text-left px-3 sm:px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                    <th class="text-left px-3 sm:px-4 py-3 text-xs font-semibold text-slate-500 uppercase">PDF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($docHistory as $h):
                                    $pur = $h['coe_purpose'] ?? '';
                                    $purShort = mb_strlen($pur) > 32 ? mb_substr($pur, 0, 29) . '…' : $pur;
                                    $st = $h['status'];
                                    $badge = [
                                        'Approved' => 'bg-emerald-100 text-emerald-700',
                                        'Rejected' => 'bg-red-100 text-red-700',
                                        'Pending' => 'bg-amber-100 text-amber-700',
                                    ];
                                    $bc = $badge[$st] ?? 'bg-slate-100 text-slate-700';
                                    $sal = ($h['document_type'] ?? '') === 'COE' ? ($h['coe_include_salary'] ?: '—') : '—';
                                    $pdfBtnPreview = 'inline-flex items-center justify-center min-h-[2rem] px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#FA9800] text-white shadow-sm border border-[#e08900] hover:bg-[#e08900] focus:outline-none focus:ring-2 focus:ring-[#FA9800]/40 transition-colors';
                                    $pdfBtnDownload = 'inline-flex items-center justify-center min-h-[2rem] px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-700 border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-200/60 transition-colors no-underline';
                                ?>
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-3 sm:px-4 py-2.5 text-slate-700 whitespace-nowrap"><?php echo htmlspecialchars($h['date']); ?></td>
                                    <td class="px-3 sm:px-4 py-2.5 text-slate-800 font-medium"><?php echo htmlspecialchars($h['document_type']); ?></td>
                                    <td class="px-3 sm:px-4 py-2.5 text-slate-600 text-xs hidden md:table-cell" title="<?php echo htmlspecialchars($pur); ?>"><?php echo htmlspecialchars($purShort ?: '—'); ?></td>
                                    <td class="px-3 sm:px-4 py-2.5 text-slate-600 text-xs"><?php echo htmlspecialchars($sal); ?></td>
                                    <td class="px-3 sm:px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $bc; ?>"><?php echo htmlspecialchars($st); ?></span></td>
                                    <td class="px-3 sm:px-4 py-2.5 text-xs">
                                        <?php if (!empty($h['can_download'])): ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <?php if (!empty($h['is_pdf'])): ?>
                                            <button type="button" class="<?php echo htmlspecialchars($pdfBtnPreview); ?> js-doc-preview" data-source="<?php echo htmlspecialchars((string)($h['issued_source'] ?? 'files')); ?>" data-file-id="<?php echo (int)($h['issued_file_id'] ?? 0); ?>" data-upload-id="<?php echo (int)($h['issued_upload_id'] ?? 0); ?>">Preview</button>
                                            <?php endif; ?>
                                            <?php if (($h['issued_source'] ?? '') === 'uploads' && !empty($h['issued_upload_id'])): ?>
                                            <a href="document-download.php?id=<?php echo (int)$h['issued_upload_id']; ?>" class="<?php echo htmlspecialchars($pdfBtnDownload); ?>">Download</a>
                                            <?php elseif (!empty($h['issued_file_id'])): ?>
                                            <a href="document-issued-download.php?file_id=<?php echo (int)$h['issued_file_id']; ?>" class="<?php echo htmlspecialchars($pdfBtnDownload); ?>">Download</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php elseif (!empty($h['can_coe_pdf'])): ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" class="<?php echo htmlspecialchars($pdfBtnPreview); ?> js-coe-req-preview" data-request-id="<?php echo (int)($h['id']); ?>">Preview</button>
                                            <a href="document-coe-request-download.php?request_id=<?php echo (int)$h['id']; ?>" class="<?php echo htmlspecialchars($pdfBtnDownload); ?>">Download</a>
                                        </div>
                                        <?php elseif ($st === 'Approved'): ?>
                                            <span class="text-slate-400 cursor-help" title="Walang available na file para sa request na ito. Para sa COE, i-install ang Dompdf (composer); para sa iba, maglagay ng template sa uploads/certificate_templates/.">No file</span>
                                        <?php else: ?>
                                            <span class="text-slate-300">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden min-w-0">
                    <div class="px-4 sm:px-6 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-slate-700">PDF preview</h2>
                        <button type="button" id="clearPdfPreviewBtn" class="text-xs font-medium text-slate-600 hover:text-slate-800 hidden">Clear preview</button>
                    </div>
                    <p id="coeLivePreviewHint" class="hidden text-xs text-slate-500 px-4 sm:px-6 py-2 border-b border-slate-100 bg-slate-50/80">
                        Draft ng COE: nagbabago habang pinipili ang <strong>Purpose</strong> at <strong>Include salary</strong>. Ang opisyal na PDF ay mula pa rin sa HR pag na-approve.
                    </p>
                    <p id="nonCoePreviewHint" class="hidden text-xs text-slate-500 px-4 sm:px-6 py-2 border-b border-slate-100 bg-slate-50/80">
                        Ang live draft preview ay para sa <strong>COE</strong> lamang. Lumipat sa tab na COE o i-click ang <strong>Preview</strong> sa history kung may PDF na.
                    </p>
                    <div id="coeLivePreviewShell" class="relative bg-slate-200 border-t border-slate-100 hidden">
                        <div id="coeLiveProfileBanner" class="hidden text-xs text-amber-900 bg-amber-50 border-b border-amber-100 px-4 py-2"></div>
                        <div class="overflow-auto max-h-[min(85vh,720px)] p-3 sm:p-4 flex justify-center">
                            <div class="relative shadow-md bg-white" style="width:210mm;min-height:297mm;max-width:100%;">
                                <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden z-10" aria-hidden="true">
                                    <span class="select-none text-[clamp(1.5rem,8vw,3.2rem)] font-black text-black/[0.07] -rotate-[22deg] tracking-[0.35em] whitespace-nowrap">PREVIEW</span>
                                </div>
                                <div id="coeLivePreviewInner" class="relative z-[1] text-left" style="font-family:Arial,Helvetica,sans-serif;font-size:11pt;color:#111827;padding:24px 32px;box-sizing:border-box;"></div>
                            </div>
                        </div>
                    </div>
                    <div id="pdfPreviewShell" class="relative bg-slate-200 min-h-[520px] hidden">
                        <iframe id="pdfPreviewFrame" class="w-full min-h-[520px] border-0 bg-white" title="Document preview"></iframe>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden" aria-hidden="true">
                            <span class="select-none text-[clamp(2rem,10vw,4.5rem)] font-black text-black/[0.08] -rotate-[22deg] tracking-[0.35em] whitespace-nowrap">PREVIEW</span>
                        </div>
                    </div>
                    <div id="pdfPreviewPlaceholder" class="min-h-[120px] flex flex-col items-center justify-center text-slate-400 text-sm px-4 py-8 border-t border-slate-100 hidden">
                        <p class="text-center max-w-md">Click <strong class="text-slate-600">Preview</strong> on a row that has a PDF. The watermark is shown on top of the viewer only.</p>
                    </div>
                </section>
            </div>
        </div>

        <div id="govCertMaintModal" class="fixed inset-0 z-[100] hidden md:left-64 md:right-0 md:top-0 md:bottom-0" role="dialog" aria-modal="true" aria-labelledby="govCertMaintHeading">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900/80 via-amber-950/50 to-slate-900/85 backdrop-blur-[3px] js-gov-maint-close cursor-pointer" aria-hidden="true"></div>
            <div class="relative z-10 flex min-h-0 flex-1 flex-col items-center justify-center overflow-y-auto p-4 sm:p-6 md:p-8 lg:p-10">
                <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl border border-amber-200/60 bg-gradient-to-br from-amber-50 via-white to-orange-50 shadow-[0_25px_80px_-12px_rgba(234,88,12,0.35)] ring-1 ring-white/80">
                    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#FA9800]/20 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-20 -left-12 h-56 w-56 rounded-full bg-orange-300/25 blur-3xl"></div>
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(250,152,0,0.07)_1px,transparent_0)] bg-[length:24px_24px]"></div>
                    <div class="relative p-6 sm:p-10 md:p-12 lg:p-14">
                        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:gap-12">
                            <div class="flex shrink-0 justify-center lg:justify-start">
                                <div class="relative flex h-28 w-28 items-center justify-center rounded-2xl bg-gradient-to-br from-[#FA9800] to-amber-600 text-5xl shadow-xl shadow-orange-600/30 ring-4 ring-white/90 sm:h-32 sm:w-32 sm:text-6xl" aria-hidden="true">
                                    <span class="drop-shadow-md">🛠️</span>
                                    <span class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-full bg-white text-lg shadow-md ring-2 ring-amber-100">✨</span>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 text-center lg:text-left">
                                <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#FA9800] sm:text-xs">Under construction</p>
                                <p id="govCertMaintFeatureLabel" class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl md:text-4xl"></p>
                                <h3 id="govCertMaintHeading" class="mt-3 text-lg font-bold leading-snug text-slate-800 sm:text-xl md:text-2xl">This desk is still getting its superhero cape.</h3>
                                <p id="govCertMaintBody" class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base lg:mx-0 lg:mr-auto"></p>
                                <div class="mt-8 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                                    <button type="button" class="js-gov-maint-close inline-flex min-h-[3rem] items-center justify-center rounded-2xl bg-[#FA9800] px-8 py-3 text-base font-bold text-white shadow-lg shadow-orange-600/25 transition hover:bg-[#e08900] hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-[#FA9800]/35">Got it &mdash; back to COE</button>
                                    <button type="button" class="js-gov-maint-close inline-flex min-h-[3rem] items-center justify-center rounded-2xl border-2 border-slate-200 bg-white/80 px-6 py-3 text-sm font-semibold text-slate-700 backdrop-blur-sm transition hover:border-slate-300 hover:bg-white focus:outline-none focus:ring-2 focus:ring-slate-200">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="relative z-10 mt-6 max-w-lg text-center text-xs text-white/70">Tip: COE requests and preview are ready in the panel on the left.</p>
            </div>
        </div>

        <script type="application/json" id="coe-preview-data"><?php echo $coePreviewJson !== false ? $coePreviewJson : '{}'; ?></script>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="include/sidebar-employee.js"></script>
    <script>
      $(function () {
        // Sidebar partial-load behavior
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          const pathOnly = (url || '').split('#')[0].split('?')[0];
          if (url === 'index.php' || url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'reimbursement.php' || url === 'request.php' || pathOnly === 'inventory.php' || ['performance.php', 'performance-my-reviews.php', 'performance-form-review.php', 'performance-review-received.php', 'performance-review-submissions.php'].indexOf(pathOnly) !== -1 || ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'].indexOf(pathOnly) !== -1) {
            window.location.href = url;
            return;
          }

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Certificate button behavior (UI only for now)
        const titles = {
          coe: 'Certificate of Employment (COE)',
          sss: 'SSS Certificate',
          pagibig: 'Pag-IBIG Certificate',
          philhealth: 'PhilHealth Certificate',
        };

        const subtitles = {
          coe: 'Choose purpose and salary option, then submit.',
          sss: 'Submit to queue this certificate with HR.',
          pagibig: 'Submit to queue this certificate with HR.',
          philhealth: 'Submit to queue this certificate with HR.',
        };

        const docCodeMap = { coe: 'COE', sss: 'SSS', pagibig: 'PAGIBIG', philhealth: 'PHILHEALTH' };

        function syncCertRequestForm(doc) {
          $('#document_code_field').val(docCodeMap[doc] || 'COE');
          const isCoe = doc === 'coe';
          $('#coeOptionsWrap').toggleClass('hidden', !isCoe);
          $('#coe_purpose, #coe_include_salary').prop('required', isCoe);
          $('#coeOptionsHint').toggleClass('hidden', !isCoe);
          $('#nonCoeHint').toggleClass('hidden', isCoe);
          if (!isCoe) {
            $('#coe_purpose').val('');
            $('#coe_include_salary').val('');
          }
          if (window.__coePdfIframeMode) {
            $('#pdfPreviewFrame').attr('src', 'about:blank');
            window.__coePdfIframeMode = false;
            $('#clearPdfPreviewBtn').addClass('hidden');
          }
          syncPdfPreviewPanel();
        }

        const govCertMaintNames = { sss: 'SSS', pagibig: 'Pag-IBIG', philhealth: 'PhilHealth' };

        function openGovCertMaintModal(label) {
          const body =
            '<strong class="text-slate-900">' +
            label +
            "</strong> is on pause while we polish every pixel and wire &mdash; think workshop vibes, not abandonment. We want the experience to feel <em>chef's kiss</em> before it ships. " +
            'Until then, <strong class="text-slate-900">Certificate of Employment (COE)</strong> on the left is live, loud, and ready when you are.';
          $('#govCertMaintFeatureLabel').text(label + ' certificate');
          $('#govCertMaintBody').html(body);
          const $m = $('#govCertMaintModal');
          $m.removeClass('hidden').addClass('flex').addClass('flex-col');
          $('body').css('overflow', 'hidden');
        }

        function closeGovCertMaintModal() {
          const $m = $('#govCertMaintModal');
          $m.addClass('hidden').removeClass('flex').removeClass('flex-col');
          $('body').css('overflow', '');
        }

        $(document).on('click', '.js-gov-maint-close', function () {
          closeGovCertMaintModal();
        });

        $(document).on('keydown', function (e) {
          if (e.key === 'Escape' && !$('#govCertMaintModal').hasClass('hidden')) {
            closeGovCertMaintModal();
          }
        });

        $('.js-cert-btn').on('click', function () {
          const doc = $(this).data('doc');
          if (doc === 'sss' || doc === 'pagibig' || doc === 'philhealth') {
            const label = govCertMaintNames[doc] || 'This certificate';
            openGovCertMaintModal(label);
            return;
          }

          window.__coeActiveCert = doc;

          $('.js-cert-btn')
            .removeClass('bg-[#FA9800] text-white')
            .addClass('bg-slate-100 text-slate-700');

          $(this)
            .removeClass('bg-slate-100 text-slate-700')
            .addClass('bg-[#FA9800] text-white');

          $('#pdfTitle').text(titles[doc] || 'Certificate');
          $('#pdfSubtitle').text(subtitles[doc] || 'Submit your request below.');
          syncCertRequestForm(doc);
        });

        window.__coeActiveCert = 'coe';
        window.__coePdfIframeMode = false;

        function coeEsc(s) {
          if (s == null) return '';
          return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        }

        function coeParsePreviewPayload() {
          const el = document.getElementById('coe-preview-data');
          if (!el || !el.textContent) return null;
          try {
            return JSON.parse(el.textContent);
          } catch (e) {
            return null;
          }
        }

        function renderCoeLivePreview() {
          const data = coeParsePreviewPayload();
          const host = document.getElementById('coeLivePreviewInner');
          if (!data || !host) return;

          const purpose = ($('#coe_purpose').val() || '').trim();
          const incSal = (($('#coe_include_salary').val() || '').trim().toUpperCase() === 'YES');

          const b = data.brand || {};
          const accent = coeEsc(b.accent || '#E85D04');
          const accentDark = coeEsc(b.accent_dark || '#C2410C');
          const co1 = coeEsc(b.company_primary || '');
          const co2 = coeEsc(b.company_secondary || '');
          const tag = coeEsc(b.tagline || '');
          const lines = Array.isArray(b.contact_lines) ? b.contact_lines : [];
          let contactHtml = '';
          lines.forEach(function (ln) {
            const t = String(ln || '').trim();
            if (t) contactHtml += '<span style="margin-right:14px;">' + coeEsc(t) + '</span>';
          });

          const fullName = coeEsc(data.employeeName || '');
          const pos = (data.position || '').trim();
          const dept = (data.department || '').trim();
          const posLine = pos ? coeEsc(pos) : 'as assigned';
          const deptPhrase = dept
            ? 'within the <strong>' + coeEsc(dept) + '</strong> department'
            : 'within the organization';
          const dateH = (data.dateHiredLong || '').trim();
          const subj = coeEsc(data.subjectPronoun || 'They');
          const poss = coeEsc(data.possPronoun || 'their');
          const employer = coeEsc(data.employerCert || 'the Company');
          const issueDate = coeEsc(data.issueDate || '');

          let p1 = fullName + ' is a regular employee of <strong>' + employer + '</strong>';
          if (dateH) p1 += ' since <strong>' + coeEsc(dateH) + '</strong>';
          p1 += '. ' + subj + ' currently holds the position of <strong>' + posLine + '</strong> ' + deptPhrase + '.';

          const gross = (data.salaryBasic || '').trim();
          const it = (data.salaryIt || '').trim();
          let p2 = '';
          if (incSal) {
            if (gross || it) {
              const grossHtml = gross ? coeEsc(gross) : 'on record with HR';
              p2 = 'As of the date of this issuance, ' + poss + ' current gross monthly compensation is <strong>' + grossHtml + '</strong>';
              if (it) p2 += ', inclusive of IT allowance worth <strong>' + coeEsc(it) + '</strong>';
              p2 += '.';
            } else {
              p2 = 'Compensation figures may be provided separately upon verification, as recorded in the employer\'s payroll.';
            }
          }

          let p3 = 'This certification is being issued upon the request of <strong>' + fullName + '</strong>';
          if (purpose) {
            p3 += ' for the purpose of <strong>' + coeEsc(purpose) + '</strong>';
          } else {
            p3 += ' for the purpose of <span style="color:#94a3b8;font-style:italic;">(piliin ang Purpose sa form)</span>';
          }
          p3 += '.';

          const sigName = coeEsc((b.signatory_name || '').trim());
          const sigTitle = coeEsc((b.signatory_title || '').trim());
          const footCo = coeEsc((b.footer_company || '').trim());
          const footAddr = coeEsc((b.footer_address || '').trim());
          const footReg = coeEsc((b.footer_registration || '').trim());

          const html =
            '<style>' +
            '.coepv-bar-top{height:6px;background:' + accent + ';margin:-24px -32px 0 -32px;}' +
            '.coepv-hdr{margin-top:14px;text-align:center;}' +
            '.coepv-co1{font-size:13pt;font-weight:bold;color:' + accent + ';letter-spacing:0.5px;}' +
            '.coepv-co2{font-size:11pt;font-weight:bold;color:#9ca3af;margin-top:2px;letter-spacing:0.5px;}' +
            '.coepv-tagbar{margin-top:10px;background:' + accent + ';color:#fff;font-size:7.5pt;padding:5px 8px;text-align:center;letter-spacing:0.3px;}' +
            '.coepv-contact{margin-top:8px;font-size:7.5pt;color:#1d4ed8;text-align:center;line-height:1.5;}' +
            '.coepv-bar-mid{height:4px;background:' + accentDark + ';margin:12px -32px 0 -32px;}' +
            '.coepv-h1{text-align:center;font-size:13pt;font-weight:bold;margin:28px 0 18px 0;letter-spacing:1px;}' +
            '.coepv-date{margin-bottom:14px;}' +
            '.coepv-p{line-height:1.55;text-align:justify;margin:0 0 12px 0;}' +
            '.coepv-sign{margin-top:36px;}' +
            '.coepv-sig-name{font-weight:bold;margin-top:40px;}' +
            '.coepv-footer{margin-top:48px;padding-top:12px;border-top:1px solid #e5e7eb;font-size:8pt;color:#4b5563;text-align:center;line-height:1.45;}' +
            '</style>' +
            '<div class="coepv-bar-top"></div>' +
            '<div class="coepv-hdr"><div class="coepv-co1">' + co1 + '</div><div class="coepv-co2">' + co2 + '</div></div>' +
            '<div class="coepv-tagbar">' + tag + '</div>' +
            (contactHtml ? '<div class="coepv-contact">' + contactHtml + '</div>' : '') +
            '<div class="coepv-bar-mid"></div>' +
            '<div class="coepv-h1">CERTIFICATE OF EMPLOYMENT</div>' +
            '<div class="coepv-date">' + issueDate + '</div>' +
            '<p class="coepv-p">To whom it may concern:</p>' +
            '<p class="coepv-p">This is to certify that ' + p1 + '</p>' +
            (p2 ? '<p class="coepv-p">' + p2 + '</p>' : '') +
            '<p class="coepv-p">' + p3 + '</p>' +
            '<div class="coepv-sign">' +
            (sigName ? '<div class="coepv-sig-name">' + sigName + '</div>' : '') +
            (sigTitle ? '<div>' + sigTitle + '</div>' : '') +
            '</div>' +
            '<div class="coepv-footer">' +
            (footCo ? '<div><strong>' + footCo + '</strong></div>' : '') +
            (footAddr ? '<div>' + footAddr + '</div>' : '') +
            (footReg ? '<div>' + footReg + '</div>' : '') +
            '</div>';

          host.innerHTML = html;
        }

        function syncPdfPreviewPanel() {
          const iframeOn = !!window.__coePdfIframeMode;
          const isCoe = window.__coeActiveCert === 'coe';

          if (iframeOn) {
            $('#coeLivePreviewShell').addClass('hidden');
            $('#coeLivePreviewHint').addClass('hidden');
            $('#nonCoePreviewHint').addClass('hidden');
            $('#pdfPreviewPlaceholder').addClass('hidden');
            $('#pdfPreviewShell').removeClass('hidden');
            return;
          }

          $('#pdfPreviewShell').addClass('hidden');
          $('#pdfPreviewFrame').attr('src', 'about:blank');

          if (isCoe) {
            $('#coeLivePreviewShell').removeClass('hidden');
            $('#coeLivePreviewHint').removeClass('hidden');
            $('#nonCoePreviewHint').addClass('hidden');
            $('#pdfPreviewPlaceholder').addClass('hidden');
            const d = coeParsePreviewPayload();
            const $ban = $('#coeLiveProfileBanner');
            if (d && !d.hasProfile) {
              $ban.removeClass('hidden').text('Kapag na-link na ang profile mo sa employee record, lalabas dito ang eksaktong datos na ipapadala sa HR.');
            } else {
              $ban.addClass('hidden').text('');
            }
            renderCoeLivePreview();
          } else {
            $('#coeLivePreviewShell').addClass('hidden');
            $('#coeLivePreviewHint').addClass('hidden');
            $('#nonCoePreviewHint').removeClass('hidden');
            $('#pdfPreviewPlaceholder').removeClass('hidden');
            $('#coeLiveProfileBanner').addClass('hidden').text('');
            $('#pdfPreviewPlaceholder p').html(
              'Walang live draft para sa sertipikong ito. Bumalik sa <strong class="text-slate-600">COE</strong> para makita ang template, o gamitin ang <strong class="text-slate-600">Preview</strong> sa table kung may na-approve nang PDF.'
            );
          }
        }

        $('#coe_purpose, #coe_include_salary').on('change input', function () {
          if (window.__coeActiveCert === 'coe' && !window.__coePdfIframeMode) {
            renderCoeLivePreview();
          }
        });

        syncCertRequestForm('coe');
        syncPdfPreviewPanel();

        $(document).on('click', '.js-doc-preview', function () {
          const $btn = $(this);
          const source = ($btn.data('source') || 'files').toString();
          const uploadId = parseInt($btn.data('upload-id'), 10) || 0;
          const fileId = parseInt($btn.data('file-id'), 10) || 0;
          let src = '';
          if (source === 'uploads' && uploadId > 0) {
            src = 'document-download.php?id=' + encodeURIComponent(uploadId) + '&inline=1';
          } else if (fileId > 0) {
            src = 'document-issued-download.php?file_id=' + encodeURIComponent(fileId) + '&inline=1';
          }
          if (!src) return;
          window.__coePdfIframeMode = true;
          $('#pdfPreviewFrame').attr('src', src);
          syncPdfPreviewPanel();
          $('#clearPdfPreviewBtn').removeClass('hidden');
          const el = document.getElementById('pdfPreviewShell');
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        $(document).on('click', '.js-coe-req-preview', function () {
          const rid = parseInt($(this).data('request-id'), 10) || 0;
          if (!rid) return;
          window.__coePdfIframeMode = true;
          $('#pdfPreviewFrame').attr(
            'src',
            'document-coe-request-download.php?request_id=' + encodeURIComponent(rid) + '&inline=1'
          );
          syncPdfPreviewPanel();
          $('#clearPdfPreviewBtn').removeClass('hidden');
          const el = document.getElementById('pdfPreviewShell');
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        $('#clearPdfPreviewBtn').on('click', function () {
          $('#pdfPreviewFrame').attr('src', 'about:blank');
          window.__coePdfIframeMode = false;
          syncPdfPreviewPanel();
          $(this).addClass('hidden');
        });

        $(document).on('click', '#profilePhotoBtn', function(e) { e.preventDefault(); $('#profilePhotoInput').click(); });
        $(document).on('change', '#profilePhotoInput', function() {
          var $input = $(this); var files = $input[0].files;
          if (!files || !files.length) return;
          var fd = new FormData(); fd.append('profile_picture', files[0]);
          $('#profilePhotoMessage').addClass('hidden').html('');
          $('#profilePhotoBtn').prop('disabled', true).text('Uploading...');
          $.ajax({ url: 'profile-picture-upload.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo'); $input.val('');
              if (res.status === 'success') {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-emerald-600').html(res.message);
                if (res.path) { $('#profilePhotoImg').attr('src', '../uploads/' + res.path).removeClass('hidden'); $('#profilePhotoInitial').addClass('hidden'); }
                setTimeout(function() { location.reload(); }, 800);
              } else { $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(res.message || 'Upload failed'); }
            },
            error: function(xhr) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo'); $input.val('');
              var m = 'Upload failed.'; try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(e) {}
              $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(m);
            }
          });
        });
      });
    </script>
</body>
</html>

