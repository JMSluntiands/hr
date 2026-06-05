<?php
if (! defined('HR_LEGACY_EMBEDDED')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (! isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
} elseif (! isset($_SESSION['user_id']) || (int) ($_SESSION['user_id'] ?? 0) <= 0) {
    header('Location: '.(defined('HR_APP_URL') ? HR_APP_URL : '/'));
    exit;
}

$coeFormAction = (defined('HR_APP_URL') ? rtrim(HR_APP_URL, '/') : '').'/employee/request-document-submit.php';
$coeCsrfToken = function_exists('csrf_token') ? csrf_token() : '';
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
        $sql = "SELECT dr.id, dr.document_type, dr.coe_purpose, dr.coe_include_salary, dr.status, dr.created_at
                FROM document_requests dr
                WHERE dr.employee_id = {$eid} AND dr.document_type = 'COE'
                ORDER BY dr.created_at DESC LIMIT 50";
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

            $hasReqLinkCol = document_files_has_request_link_column($conn);
            $fileByReqStmt = null;
            if ($hasReqLinkCol) {
                $fileByReqStmt = $conn->prepare('SELECT id, file_path FROM document_files WHERE document_request_id = ? ORDER BY id DESC LIMIT 25');
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
    <title>COE Request</title>
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
    <style>
        /* Fallback when Tailwind CDN is slow — prevents invisible full-screen blockers */
        #govCertMaintModal { display: none !important; pointer-events: none !important; }
        #employee-sidebar-backdrop { display: none; pointer-events: none; }
        #employee-sidebar-backdrop.is-open { display: block; pointer-events: auto; }
        #main-inner { position: relative; z-index: 1; }
        #coeLivePreviewShell .coe-preview-watermark { pointer-events: none; }
    </style>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



    <!-- Main Content -->
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Certificate of Employment (COE)</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Request a COE for HR review. Live preview updates as you choose purpose and salary options.
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
            <div class="lg:col-span-4 space-y-4 relative z-10">
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 lg:p-5 relative z-10">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-[#FA9800] text-white text-xs font-semibold">COE</span>
                        <h2 class="text-sm font-semibold text-slate-700">New request</h2>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800" id="pdfTitle">Certificate of Employment (COE)</h3>
                    <p class="text-xs text-slate-500 mt-1 mb-3" id="pdfSubtitle">Fill the form below and submit. HR will review your request.</p>
                    <form action="<?php echo htmlspecialchars($coeFormAction ?: 'request-document-submit.php'); ?>" method="post" class="space-y-3 border-t border-slate-100 pt-3">
                        <?php if ($coeCsrfToken !== ''): ?>
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($coeCsrfToken); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="document_code" id="document_code_field" value="COE">
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Request details</p>
                        <div id="coeOptionsWrap" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label for="coe_purpose" class="block text-xs font-medium text-slate-600 mb-1">Purpose</label>
                                <select name="coe_purpose" id="coe_purpose" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800]/25 focus:border-[#FA9800] bg-white text-slate-800">
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
                                <select name="coe_include_salary" id="coe_include_salary" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800]/25 focus:border-[#FA9800] bg-white text-slate-800">
                                    <option value="">Select</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                        <p id="coeOptionsHint" class="text-xs text-slate-500">Required: purpose and whether salary appears on the certificate.</p>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#e08900] focus:outline-none focus:ring-2 focus:ring-[#FA9800]/40">
                            Submit request
                        </button>
                    </form>
                </section>
            </div>

            <div class="lg:col-span-8 min-w-0 space-y-6">
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">My COE request history</h2>
                        <p class="text-xs text-slate-500 mt-1"><strong>Download</strong> = official file from HR. <strong>Preview</strong> = draft PDF with on-screen watermark (not for external use).</p>
                        <p class="text-xs text-amber-900/90 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-2">Ang <strong>COE</strong> ay auto-generated na PDF mula sa employee records (pangalan, petsa ng hire, posisyon, purpose, at sahod kung pinili) kapag na-approve ng HR.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <?php if ($employeeProfileUnlinked): ?>
                        <p class="px-4 sm:px-6 py-8 text-sm text-slate-500">Link your profile to see history.</p>
                        <?php elseif ($docHistoryLoadError !== ''): ?>
                        <p class="px-4 sm:px-6 py-8 text-sm text-amber-800 bg-amber-50 border-y border-amber-100"><?php echo htmlspecialchars($docHistoryLoadError); ?></p>
                        <?php elseif (empty($docHistory)): ?>
                        <p class="px-4 sm:px-6 py-8 text-sm text-slate-500">No COE requests yet. Submit using the form on the left.</p>
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
                                <div class="coe-preview-watermark absolute inset-0 flex items-center justify-center overflow-hidden z-10" aria-hidden="true">
                                    <span class="select-none text-[clamp(1.5rem,8vw,3.2rem)] font-black text-black/[0.07] -rotate-[22deg] tracking-[0.35em] whitespace-nowrap">PREVIEW</span>
                                </div>
                                <div id="coeLivePreviewInner" class="relative z-[1] text-left" style="font-family:Arial,Helvetica,sans-serif;font-size:11pt;color:#111827;padding:24px 32px;box-sizing:border-box;"></div>
                            </div>
                        </div>
                    </div>
                    <div id="pdfPreviewShell" class="relative bg-slate-200 min-h-[520px] hidden">
                        <iframe id="pdfPreviewFrame" class="w-full min-h-[520px] border-0 bg-white" title="Document preview"></iframe>
                        <div class="coe-preview-watermark absolute inset-0 flex items-center justify-center overflow-hidden pointer-events-none" aria-hidden="true">
                            <span class="select-none text-[clamp(2rem,10vw,4.5rem)] font-black text-black/[0.08] -rotate-[22deg] tracking-[0.35em] whitespace-nowrap">PREVIEW</span>
                        </div>
                    </div>
                    <div id="pdfPreviewPlaceholder" class="min-h-[120px] flex flex-col items-center justify-center text-slate-400 text-sm px-4 py-8 border-t border-slate-100 hidden">
                        <p class="text-center max-w-md">Click <strong class="text-slate-600">Preview</strong> on a row that has a PDF. The watermark is shown on top of the viewer only.</p>
                    </div>
                </section>
            </div>
        </div>

        <script type="application/json" id="coe-preview-data"><?php echo $coePreviewJson !== false ? $coePreviewJson : '{}'; ?></script>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/sidebar-dropdown.js"></script>
    <script>
      (function () {
        var main = document.getElementById('main-inner');
        if (main) {
          main.classList.remove('opacity-60', 'pointer-events-none');
        }
        var backdrop = document.getElementById('employee-sidebar-backdrop');
        if (backdrop && window.innerWidth >= 768) {
          backdrop.classList.remove('is-open');
          backdrop.style.display = 'none';
        }
      })();
      $(function () {
        $('#main-inner').removeClass('opacity-60 pointer-events-none');
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

        window.__coeActiveCert = 'coe';
        window.__coePdfIframeMode = false;
        $('#document_code_field').val('COE');

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

