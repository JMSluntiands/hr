<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include '../database/db.php';
require_once __DIR__ . '/../include/ensure_employment_types_table.php';
require_once __DIR__ . '/../include/ensure_employees_employment_type_id_column.php';
if ($conn) {
    ensure_employment_types_table($conn);
    ensure_employees_employment_type_id_column($conn);
}

// Get employee data from database
$userId = (int)$_SESSION['user_id'];
$employeeData = null;
$employeeDbId = null;

if ($conn) {
    // Get user email
    $userStmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    
    if ($user) {
        $empStmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
        $empStmt->bind_param('s', $user['email']);
        $empStmt->execute();
        $empResult = $empStmt->get_result();
        $employeeData = $empResult->fetch_assoc();
        $empStmt->close();
        
        if ($employeeData) {
            $employeeDbId = (int)$employeeData['id'];
        }
    }
}

$employeeName = $employeeData['full_name'] ?? $_SESSION['name'] ?? 'Employee';
$employeePhoto = !empty($employeeData['profile_picture']) ? $employeeData['profile_picture'] : null;
$position     = $employeeData['position'] ?? $_SESSION['position'] ?? '';
$department   = $employeeData['department'] ?? $_SESSION['department'] ?? '';
$employeeId   = $employeeData['employee_id'] ?? $_SESSION['employee_id'] ?? '';
$dateHired    = !empty($employeeData['date_hired']) ? date('M d, Y', strtotime($employeeData['date_hired'])) : ($employeeData['date_hired'] ?? '');
$employmentTypeName = null;

// Documents as list (same as admin staff-view)
$documents = [];
$compensation = null;
$latestAdjustment = null;
$bankDetails = null;

if ($employeeDbId && $conn) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $hasDelCol = $conn->query("SHOW COLUMNS FROM employee_document_uploads LIKE 'deletion_requested_at'");
        $docExtraCols = ($hasDelCol && $hasDelCol->num_rows > 0) ? ', deletion_requested_at' : '';
        $docStmt = $conn->prepare("SELECT id, document_type, file_path, status, created_at, updated_at{$docExtraCols} FROM employee_document_uploads WHERE employee_id = ? ORDER BY created_at DESC, id DESC");
        if ($docStmt) {
            $docStmt->bind_param('i', $employeeDbId);
            $docStmt->execute();
            $docResult = $docStmt->get_result();
            while ($row = $docResult->fetch_assoc()) {
                $documents[] = $row;
            }
            $docStmt->close();
        }
    }
    $checkCompTable = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    if ($checkCompTable && $checkCompTable->num_rows > 0) {
        $compStmt = $conn->prepare("SELECT * FROM employee_compensation WHERE employee_id = ? LIMIT 1");
        if ($compStmt) {
            $compStmt->bind_param('i', $employeeDbId);
            $compStmt->execute();
            $compResult = $compStmt->get_result();
            $compensation = $compResult->fetch_assoc();
            $compStmt->close();
        }
    }

    // Resolve employment type name from master table
    if (!empty($employeeData['employment_type_id'])) {
        $typeStmt = $conn->prepare("SELECT name FROM employment_types WHERE id = ? LIMIT 1");
        if ($typeStmt) {
            $typeStmt->bind_param('i', $employeeData['employment_type_id']);
            $typeStmt->execute();
            $typeRes = $typeStmt->get_result();
            if ($typeRow = $typeRes->fetch_assoc()) {
                $employmentTypeName = $typeRow['name'] ?? null;
            }
            $typeStmt->close();
        }
    }
    $checkAdjTable = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
    if ($checkAdjTable && $checkAdjTable->num_rows > 0) {
        $adjStmt = $conn->prepare("SELECT * FROM employee_salary_adjustments WHERE employee_id = ? ORDER BY date_approved DESC, created_at DESC LIMIT 1");
        if ($adjStmt) {
            $adjStmt->bind_param('i', $employeeDbId);
            $adjStmt->execute();
            $adjResult = $adjStmt->get_result();
            $latestAdjustment = $adjResult->fetch_assoc();
            $adjStmt->close();
        }
    }
    $checkBankTable = $conn->query("SHOW TABLES LIKE 'employee_bank_details'");
    if ($checkBankTable && $checkBankTable->num_rows > 0) {
        $bankStmt = $conn->prepare("SELECT * FROM employee_bank_details WHERE employee_id = ? LIMIT 1");
        if ($bankStmt) {
            $bankStmt->bind_param('i', $employeeDbId);
            $bankStmt->execute();
            $bankResult = $bankStmt->get_result();
            $bankDetails = $bankResult->fetch_assoc();
            $bankStmt->close();
        }
    }
}

$employee = $employeeData; // alias for same template as admin

// Required document types; latest upload per type for status
$documentTypes = [
    'SSS',
    'Philhealth',
    'Pag-Ibig',
    'TIN',
    'NBI Clearance',
    'Police Clearance',
    'Bank Account',
    'Employee Agreement Contract',
    'Contractual Agreement Contract',
];
$documentsByType = [];
foreach ($documents as $d) {
    if (!isset($documentsByType[$d['document_type']])) {
        $documentsByType[$d['document_type']] = $d;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



    <!-- Main Content (match employee/index.php shell so dashboard size stays consistent after navigation) -->
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 pb-12 overflow-y-auto bg-[#f1f5f9]">
        <div id="main-inner" class="w-full max-w-full space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-2">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 text-amber-800 text-xs font-semibold px-3 py-1 ring-1 ring-amber-500/25 mb-3">Employee profile</span>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">My Profile</h1>
                <p class="text-sm text-slate-500 mt-1 max-w-2xl">Your details and information at a glance — updated as HR records change.</p>
            </div>
        </div>

        <?php if (!$employee): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 rounded-xl shadow-sm">No employee record found for your account. Please contact HR.</div>
        <?php else: ?>

        <!-- Hero Card: Photo + Name + Quick Info -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200/90 overflow-hidden ring-1 ring-slate-200/60 hover:shadow-lg transition-shadow duration-300">
            <div class="relative h-28 md:h-32 bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 overflow-hidden">
                <div class="absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_20%_120%,white,transparent_55%),radial-gradient(circle_at_80%_-20%,white,transparent_45%)]"></div>
                <div class="absolute -right-8 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -left-4 bottom-0 h-24 w-24 rounded-full bg-black/10"></div>
            </div>
            <div class="px-5 sm:px-8 pb-6 -mt-12 md:-mt-14 relative">
                <div class="flex flex-col md:flex-row md:items-end gap-6">
                    <div class="flex flex-col items-center md:items-start gap-4">
                        <div id="profilePhotoPreview" class="w-28 h-28 md:w-32 md:h-32 rounded-2xl overflow-hidden bg-white border-4 border-white shadow-lg flex items-center justify-center flex-shrink-0">
                            <?php
                            $photo = !empty($employee['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $employee['profile_picture']) ? $employee['profile_picture'] : null;
                            if ($photo): ?>
                                <img id="profilePhotoImg" src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="" class="w-full h-full object-cover">
                                <span id="profilePhotoInitial" class="text-4xl font-bold text-amber-600 hidden"><?php echo strtoupper(substr($employee['full_name'] ?? '?', 0, 1)); ?></span>
                            <?php else: ?>
                                <img id="profilePhotoImg" src="" alt="" class="w-full h-full object-cover hidden">
                                <span id="profilePhotoInitial" class="text-4xl font-bold text-amber-600"><?php echo strtoupper(substr($employee['full_name'] ?? '?', 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <form id="profilePhotoForm" enctype="multipart/form-data" class="flex flex-col items-center gap-1">
                            <input type="file" name="profile_picture" id="profilePhotoInput" accept="image/jpeg,image/jpg,image/png" class="hidden">
                            <button type="button" id="profilePhotoBtn" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg shadow-sm transition-all">Upload photo</button>
                            <p class="text-xs text-slate-500">JPG or PNG, max 2MB</p>
                        </form>
                        <div id="profilePhotoMessage" class="hidden text-sm text-center max-w-[200px]"></div>
                    </div>
                    <div class="flex-1 text-center md:text-left md:pb-1">
                        <h2 class="text-xl md:text-2xl font-bold text-slate-800 mb-1"><?php echo htmlspecialchars($employee['full_name'] ?? ''); ?></h2>
                        <p class="text-sm text-slate-500 font-mono mb-3"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo ($employee['status'] ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo htmlspecialchars($employee['status'] ?? 'Active'); ?>
                        </span>
                        <?php if ($position): ?>
                        <p class="text-slate-600 font-medium mt-3"><?php echo htmlspecialchars($position); ?> · <?php echo htmlspecialchars($department); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-4">
                    <?php $sigPath = !empty($employeeData['signature']) && file_exists(__DIR__ . '/../uploads/' . $employeeData['signature']) ? $employeeData['signature'] : null; ?>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Signature</p>
                    <div id="signaturePreview" class="w-40 h-16 border border-slate-200 rounded-lg bg-white flex items-center justify-center overflow-hidden shadow-sm">
                        <?php if ($sigPath): ?>
                            <img id="signatureImg" src="../uploads/<?php echo htmlspecialchars($sigPath); ?>" alt="Signature" class="max-w-full max-h-full object-contain">
                            <span id="signaturePlaceholder" class="text-slate-400 text-xs hidden">No signature</span>
                        <?php else: ?>
                            <img id="signatureImg" src="" alt="" class="max-w-full max-h-full object-contain hidden">
                            <span id="signaturePlaceholder" class="text-slate-400 text-xs">No signature</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="signature" id="signatureInput" accept="image/png" class="hidden">
                    <button type="button" id="signatureUploadBtn" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Upload signature</button>
                    <p class="text-xs text-slate-500">PNG only, max 2MB</p>
                    <div id="signatureMessage" class="hidden text-sm text-center"></div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200/90 overflow-hidden ring-1 ring-slate-200/50 hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 md:p-8 bg-gradient-to-br from-slate-50 via-white to-amber-50/30 border-b border-slate-100">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-amber-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Personal Information</h3>
                        <p class="text-sm text-slate-500">Contact details and work locations</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-5">
                    <div class="p-4 rounded-xl bg-white/90 border border-slate-100 hover:border-amber-300/80 hover:shadow-md transition-all duration-200">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Email</p>
                        <p class="font-medium text-slate-800 truncate" title="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/90 border border-slate-100 hover:border-amber-300/80 hover:shadow-md transition-all duration-200">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Phone</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/90 border border-slate-100 hover:border-amber-300/80 hover:shadow-md transition-all duration-200">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Birthdate</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['birthdate']) ? date('M d, Y', strtotime($employee['birthdate'])) : 'N/A'; ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/90 border border-slate-100 hover:border-amber-300/80 hover:shadow-md transition-all duration-200">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Gender</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['gender'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/90 border border-slate-100 hover:border-amber-300/80 hover:shadow-md transition-all duration-200 sm:col-span-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Primary Workplace</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['address'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/90 border border-slate-100 hover:border-amber-300/80 hover:shadow-md transition-all duration-200 sm:col-span-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Secondary Workplace</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['secondary_workplace'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50/80 border border-amber-200/60 shadow-sm ring-1 ring-amber-100/80">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Emergency Contact</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['emergency_contact_name'] ?? 'N/A'); ?></p>
                        <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($employee['emergency_contact_relationship'] ?? ''); ?> · <?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="p-6 md:p-8 border-t border-slate-100">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-amber-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Employment</h3>
                        <p class="text-sm text-slate-500">Position, department &amp; type</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Position</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Department</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Employment Type</p>
                        <p class="font-medium text-slate-800"><?php echo $employmentTypeName ? htmlspecialchars($employmentTypeName) : (!empty($compensation['employment_type']) ? htmlspecialchars($compensation['employment_type']) : 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Date Hired</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['date_hired']) ? date('M d, Y', strtotime($employee['date_hired'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Government Information -->
            <div class="p-6 md:p-8 border-t border-slate-100 bg-slate-50/30">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-amber-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Government IDs</h3>
                        <p class="text-sm text-slate-500">SSS, PhilHealth, Pag-IBIG, TIN, clearances</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">SSS</p>
                        <p class="font-medium text-slate-800 font-mono text-sm"><?php echo htmlspecialchars($employee['sss'] ?? '—'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">PhilHealth</p>
                        <p class="font-medium text-slate-800 font-mono text-sm"><?php echo htmlspecialchars($employee['philhealth'] ?? '—'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Pag-IBIG</p>
                        <p class="font-medium text-slate-800 font-mono text-sm"><?php echo htmlspecialchars($employee['pagibig'] ?? '—'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">TIN</p>
                        <p class="font-medium text-slate-800 font-mono text-sm"><?php echo htmlspecialchars($employee['tin'] ?? '—'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">NBI Clearance</p>
                        <p class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($employee['nbi_clearance'] ?? '—'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Police Clearance</p>
                        <p class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($employee['police_clearance'] ?? '—'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compensation Details (view only) -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200/90 overflow-hidden ring-1 ring-slate-200/50 hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 md:p-8 border-b border-slate-100 bg-gradient-to-br from-amber-50/80 via-white to-orange-50/40">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-amber-500/25">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">Compensation</h3>
                            <p class="text-sm text-slate-500">Salary, allowances &amp; bank details</p>
                        </div>
                    </div>
                    <?php
                    $compPrivacyTarget = 'profileCompPrivacyBody';
                    $compPrivacyPlaceholder = 'profileCompPrivacyPlaceholder';
                    include __DIR__ . '/include/compensation-privacy-snippet.php';
                    ?>
                </div>
            </div>
            <div id="profileCompPrivacyPlaceholder" class="px-6 md:px-8 py-8 text-center text-sm text-slate-500">
                <p>Compensation details are hidden for privacy.</p>
                <p class="text-xs mt-1 text-slate-400">Click <strong>View</strong> to show salary and bank information.</p>
            </div>
            <div id="profileCompPrivacyBody" class="px-6 md:px-8 pb-6 hidden">
                <?php
                $currentSalary = null;
                if ($latestAdjustment) {
                    $currentSalary = $latestAdjustment['new_salary'];
                } elseif ($compensation) {
                    $currentSalary = $compensation['basic_salary_monthly'];
                }
                ?>
                <?php if ($compensation || $latestAdjustment): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if ($compensation): ?>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Basic Salary (Daily)</p>
                                <p class="font-medium text-slate-800 text-lg">₱<?php echo number_format($compensation['basic_salary_daily'] ?? 0, 2); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Employment Type</p>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($compensation['employment_type'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Effective Date</p>
                                <p class="font-medium text-slate-800"><?php echo !empty($compensation['effective_date']) ? date('M d, Y', strtotime($compensation['effective_date'])) : 'N/A'; ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($currentSalary && $compensation):
                            $dailyGross = !empty($compensation['basic_salary_daily'])
                                ? (float)$compensation['basic_salary_daily']
                                : ((float)$currentSalary / 26);
                        ?>
                        <div class="md:col-span-2 border-slate-200 mt-4">
                            <h4 class="text-md font-semibold text-slate-700 mb-4">Gross Income (Based on Current Salary)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-amber-50 border border-amber-100 p-5 rounded-xl">
                                    <p class="text-xs font-medium text-amber-700 uppercase tracking-wide mb-1">Daily Gross</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($dailyGross, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php if ($compensation): ?>
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-200">
                                <h4 class="text-md font-semibold text-slate-700 mb-3">Allowances</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div><p class="text-xs text-slate-500 mb-1">Internet</p><p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_internet'] ?? 0, 2); ?></p></div>
                                    <div><p class="text-xs text-slate-500 mb-1">Meal</p><p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_meal'] ?? 0, 2); ?></p></div>
                                    <div><p class="text-xs text-slate-500 mb-1">Position/Representation</p><p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_position'] ?? 0, 2); ?></p></div>
                                    <div><p class="text-xs text-slate-500 mb-1">Transportation</p><p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_transportation'] ?? 0, 2); ?></p></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-500 text-sm">No compensation information available.</p>
                <?php endif; ?>

                <!-- Bank Details (view only) -->
                <?php if ($bankDetails): ?>
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h4 class="text-md font-semibold text-slate-700 mb-4">Bank Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Bank Name</p>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($bankDetails['bank_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Account Number</p>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($bankDetails['account_number']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Account Name</p>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($bankDetails['account_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Account Type</p>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($bankDetails['account_type']); ?></p>
                            </div>
                            <?php if (!empty($bankDetails['branch'])): ?>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Branch</p>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($bankDetails['branch']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h4 class="text-md font-semibold text-slate-700 mb-4">Bank Details</h4>
                        <p class="text-slate-500 text-sm">No bank details on file.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload Documents (employee uploads; admin validates under Request Upload) -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200/90 overflow-hidden ring-1 ring-slate-200/50 hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 md:p-8 border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-amber-50/30">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-md shadow-amber-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Upload Documents</h3>
                        <p class="text-sm text-slate-500">HRIS 201 documents — admin will validate each upload.</p>
                    </div>
                </div>
            </div>
            <div class="p-6 md:p-8 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="w-10 text-center px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Done</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Document Type</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Updated</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($documentTypes as $docType):
                            $doc = $documentsByType[$docType] ?? null;
                            $hasFile = $doc && !empty($doc['file_path']);
                            $pendingRemoval = $hasFile && !empty($doc['deletion_requested_at'] ?? null);
                            if (!$hasFile) {
                                $status = 'No File';
                                $statusClass = 'bg-slate-100 text-slate-600';
                                $statusText = 'No File';
                            } elseif ($pendingRemoval) {
                                $status = $doc['status'] ?? 'Pending';
                                $statusClass = 'bg-slate-200 text-slate-700';
                                $statusText = 'Pending HR removal';
                            } else {
                                $status = $doc['status'] ?? 'Pending';
                                $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                $statusText = $status === 'Approved' ? 'Verified' : ($status === 'Rejected' ? 'Rejected' : 'Pending validation');
                            }
                            $lastUpdated = $doc && isset($doc['updated_at']) && $doc['updated_at'] ? date('M d, Y', strtotime($doc['updated_at'])) : ($doc && !empty($doc['created_at']) ? date('M d, Y', strtotime($doc['created_at'])) : '—');
                            $isVerifiedActive = $hasFile && ($status === 'Approved' || ($doc['status'] ?? '') === 'Approved') && !$pendingRemoval;
                        ?>
                        <tr>
                            <td class="px-3 py-2 text-center">
                                <?php if ($isVerifiedActive): ?>
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                <?php elseif ($hasFile): ?>
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-amber-300 text-amber-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01" />
                                        </svg>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-slate-300 text-slate-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="5" stroke-width="2" />
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars($docType); ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs"><?php echo $lastUpdated; ?></td>
                            <td class="px-4 py-2">
                                <div class="flex items-center flex-wrap gap-2">
                                    <button type="button" class="upload-doc-btn px-3 py-1.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white text-xs font-medium transition-colors" data-doc-type="<?php echo htmlspecialchars($docType); ?>" title="Upload">Upload</button>
                                    <?php if ($hasFile && !$pendingRemoval): ?>
                                    <a href="document-view.php?id=<?php echo (int)$doc['id']; ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium transition-colors" title="View">View</a>
                                    <a href="document-download.php?id=<?php echo (int)$doc['id']; ?>" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-medium transition-colors" title="Download">Download</a>
                                    <?php elseif ($pendingRemoval): ?>
                                    <span class="text-xs text-slate-500">On hold until HR approves removal</span>
                                    <?php else: ?>
                                    <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs cursor-not-allowed">View</span>
                                    <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs cursor-not-allowed">Download</span>
                                    <?php endif; ?>
                                    <?php if ($isVerifiedActive): ?>
                                    <button type="button" class="request-doc-removal-btn px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 text-xs font-medium transition-colors" data-doc-id="<?php echo (int)$doc['id']; ?>" data-doc-name="<?php echo htmlspecialchars($docType); ?>" title="Request removal (HR approval required)">Request removal</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Employee Documents (uploaded files - view only) -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200/90 overflow-hidden ring-1 ring-slate-200/50 hover:shadow-lg transition-shadow duration-300">
            <div class="p-6 md:p-8 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Your Uploaded Documents</h3>
                        <p class="text-sm text-slate-500">Status updated after admin validation</p>
                    </div>
                </div>
            </div>
            <div class="p-6 md:p-8">
                <?php if (empty($documents)): ?>
                    <div class="text-center py-8 text-slate-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">No documents uploaded yet.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($documents as $doc):
                            $fileUrl = !empty($doc['file_path']) ? '../uploads/' . htmlspecialchars($doc['file_path']) : '';
                            $isImage = $fileUrl && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $doc['file_path']);
                            $isPdf = $fileUrl && preg_match('/\.pdf$/i', $doc['file_path']);
                            $status = $doc['status'] ?? 'Pending';
                            $gridPendingRemoval = !empty($doc['deletion_requested_at'] ?? null);
                            if ($gridPendingRemoval) {
                                $statusClass = 'bg-slate-200 text-slate-700';
                                $gridStatusLabel = 'Pending HR removal';
                            } else {
                                $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                $gridStatusLabel = $status;
                            }
                            $canRequestRemovalGrid = ($status === 'Approved' && !$gridPendingRemoval);
                        ?>
                        <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($doc['document_type'] ?? 'Document'); ?></h4>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>"><?php echo htmlspecialchars($gridStatusLabel); ?></span>
                            </div>
                            <?php if ($fileUrl && file_exists(__DIR__ . '/../uploads/' . $doc['file_path']) && !$gridPendingRemoval): ?>
                                <?php if ($isImage): ?>
                                    <div class="mb-3 rounded border border-slate-200 overflow-hidden">
                                        <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($doc['document_type'] ?? ''); ?>" class="w-full h-32 object-cover">
                                    </div>
                                <?php elseif ($isPdf): ?>
                                    <div class="mb-3 p-4 bg-red-50 rounded border border-red-200 text-center">
                                        <svg class="w-12 h-12 mx-auto text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        <span class="text-xs text-red-700 font-medium">PDF Document</span>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center">
                                        <svg class="w-12 h-12 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <span class="text-xs text-slate-700 font-medium">Document File</span>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo $fileUrl; ?>" target="_blank" class="inline-flex items-center gap-2 text-sm text-[#d97706] hover:text-[#b45309] font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    View/Download
                                </a>
                            <?php elseif ($gridPendingRemoval): ?>
                                <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center"><p class="text-xs text-slate-600">Pending HR approval for removal.</p></div>
                            <?php else: ?>
                                <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center"><p class="text-xs text-slate-500">File not found</p></div>
                            <?php endif; ?>
                            <?php if ($canRequestRemovalGrid): ?>
                            <button type="button" class="request-doc-removal-btn mt-2 px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 text-xs font-medium" data-doc-id="<?php echo (int)$doc['id']; ?>" data-doc-name="<?php echo htmlspecialchars($doc['document_type'] ?? 'Document'); ?>">Request removal</button>
                            <?php endif; ?>
                            <?php if (!empty($doc['created_at'])): ?>
                                <p class="text-xs text-slate-400 mt-2">Uploaded: <?php echo date('M d, Y', strtotime($doc['created_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Removal Request Modal -->
        <div id="removalModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
            <div id="removalModalCard" class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all duration-200 scale-95 opacity-0">
                <div class="relative px-6 pt-7 pb-5 bg-gradient-to-br from-red-500 via-red-500 to-rose-600 text-white">
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-8 -left-8 w-28 h-28 bg-white/10 rounded-full"></div>
                    <div class="relative flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white/20 flex items-center justify-center ring-4 ring-white/15">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007M10.42 4.51L2.93 17.49A1.75 1.75 0 004.44 20.13h15.12a1.75 1.75 0 001.51-2.64L13.58 4.51a1.75 1.75 0 00-3.16 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold leading-tight">Request document removal</h2>
                            <p class="text-sm text-white/85 mt-1">HR approval is required before this document can be deleted.</p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Document</p>
                        <p id="removalDocName" class="text-sm font-medium text-slate-800 mt-0.5">—</p>
                    </div>
                    <ul class="text-sm text-slate-600 space-y-2">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd"/></svg>
                            <span>The file stays available until HR approves your request.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 01-1-1v-4a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd"/></svg>
                            <span>Once approved, it will be moved to the admin archive — not permanently destroyed.</span>
                        </li>
                    </ul>
                </div>
                <div class="px-6 pb-6 pt-2 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" id="cancelRemovalBtn" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium transition-colors">Cancel</button>
                    <button type="button" id="confirmRemovalBtn" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white text-sm font-semibold shadow-lg shadow-red-500/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                        <span id="confirmRemovalLabel">Yes, request removal</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast container (removal flow) -->
        <div id="docToast" class="hidden fixed top-5 right-5 z-[60] max-w-sm w-[20rem] rounded-xl shadow-xl border overflow-hidden transform transition-all duration-200 translate-x-4 opacity-0">
            <div class="flex items-start gap-3 p-4 bg-white">
                <div id="docToastIcon" class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center"></div>
                <div class="min-w-0 flex-1">
                    <p id="docToastTitle" class="text-sm font-semibold text-slate-800"></p>
                    <p id="docToastBody" class="text-xs text-slate-600 mt-0.5"></p>
                </div>
                <button type="button" id="docToastClose" class="text-slate-400 hover:text-slate-600 text-lg leading-none">×</button>
            </div>
            <div id="docToastBar" class="h-1"></div>
        </div>

        <!-- Upload Document Modal -->
        <div id="uploadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-slate-800">Upload Document</h2>
                    <button type="button" id="closeUploadModal" class="text-slate-400 hover:text-slate-600 text-xl leading-none">×</button>
                </div>
                <form id="uploadForm" enctype="multipart/form-data" class="p-6 space-y-4">
                    <input type="hidden" name="document_type" id="uploadDocType" value="">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Document Type</label>
                        <input type="text" id="uploadDocTypeDisplay" readonly class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Select File <span class="text-red-500">*</span></label>
                        <input type="file" name="document_file" id="documentFile" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        <p class="text-xs text-slate-500 mt-1">Allowed: PDF, JPG, PNG (Max 5MB)</p>
                    </div>
                    <div id="uploadMessage" class="hidden"></div>
                    <div class="flex justify-end gap-3">
                        <button type="button" id="cancelUpload" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309]">Upload</button>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/sidebar-dropdown.js"></script>
    <script src="/assets/js/compensation-privacy.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          // Dashboard, My Profile, Compensation, and Time Off: full page load so layout and modals stay correct
          const pathOnly = (url || '').split('#')[0].split('?')[0];
          if (url === 'index.php' || url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'reimbursement.php' || url === 'request.php' || pathOnly === 'inventory.php' || ['performance.php', 'performance-my-reviews.php', 'performance-form-review.php', 'performance-review-received.php', 'performance-review-submissions.php'].indexOf(pathOnly) !== -1 || ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'].indexOf(pathOnly) !== -1) {
            window.location.href = url;
            return;
          }

          // Remove any active state from all links
          $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#FA9800] font-medium rounded-l-none rounded-r-full');
          $('.js-side-link').addClass('rounded-lg');

          // Load only the right content
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Delegated filters for Time Off page
        $(document).on('change', '#usageTypeFilter, #usageYearFilter', function () {
          const type = $('#usageTypeFilter').val();
          const year = $('#usageYearFilter').val();

          $('#usageTable tbody tr').each(function () {
            const rowType = $(this).data('type');
            const rowYear = String($(this).data('year'));
            const typeOk = type === 'all' || type === rowType;
            const yearOk = year === 'all' || year === rowYear;
            $(this).toggle(typeOk && yearOk);
          });
        });

        $(document).on('change', '#requestStatusFilter, #requestTypeFilter', function () {
          const status = $('#requestStatusFilter').val();
          const type = $('#requestTypeFilter').val();

          $('#requestTable tbody tr').each(function () {
            const rowStatus = $(this).data('status');
            const rowType = $(this).data('type');
            const statusOk = status === 'all' || status === rowStatus;
            const typeOk = type === 'all' || type === rowType;
            $(this).toggle(statusOk && typeOk);
          });
        });

        // Profile picture upload (visible to admin in staff view)
        $(document).on('click', '#profilePhotoBtn', function(e) {
          e.preventDefault();
          $('#profilePhotoInput').click();
        });
        $(document).on('change', '#profilePhotoInput', function() {
          var $input = $(this);
          var files = $input[0].files;
          if (!files || !files.length) return;
          var fd = new FormData();
          fd.append('profile_picture', files[0]);
          $('#profilePhotoMessage').addClass('hidden').html('');
          $('#profilePhotoBtn').prop('disabled', true).text('Uploading...');
          $.ajax({
            url: 'profile-picture-upload.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
              $('#profilePhotoBtn').prop('disabled', false).text('Upload photo');
              $input.val('');
              if (res.status === 'success') {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-emerald-600').html(res.message);
                if (res.path) {
                  $('#profilePhotoImg').attr('src', '../uploads/' + res.path).removeClass('hidden');
                  $('#profilePhotoInitial').addClass('hidden');
                }
                setTimeout(function() { location.reload(); }, 800);
              } else {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-red-600').html(res.message || 'Upload failed');
              }
            },
            error: function(xhr) {
              $('#profilePhotoBtn').prop('disabled', false).text('Upload photo');
              $input.val('');
              var m = 'Upload failed. Please try again.';
              try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(er) {}
              $('#profilePhotoMessage').removeClass('hidden').addClass('text-red-600').html(m);
            }
          });
        });

        // Signature upload
        $(document).on('click', '#signatureUploadBtn', function(e) {
          e.preventDefault();
          $('#signatureInput').click();
        });
        $(document).on('change', '#signatureInput', function() {
          var $input = $(this);
          var files = $input[0].files;
          if (!files || !files.length) return;
          var fd = new FormData();
          fd.append('signature', files[0]);
          $('#signatureMessage').addClass('hidden').html('');
          $('#signatureUploadBtn').prop('disabled', true).text('Uploading...');
          $.ajax({
            url: 'signature-upload.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
              $('#signatureUploadBtn').prop('disabled', false).text('Upload signature');
              $input.val('');
              if (res.status === 'success') {
                $('#signatureMessage').removeClass('hidden').addClass('text-emerald-600').html(res.message);
                if (res.path) {
                  $('#signatureImg').attr('src', '../uploads/' + res.path).removeClass('hidden');
                  $('#signaturePlaceholder').addClass('hidden');
                }
                setTimeout(function() { location.reload(); }, 800);
              } else {
                $('#signatureMessage').removeClass('hidden').addClass('text-red-600').html(res.message || 'Upload failed');
              }
            },
            error: function(xhr) {
              $('#signatureUploadBtn').prop('disabled', false).text('Upload signature');
              $input.val('');
              var m = 'Upload failed. Please try again.';
              try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(er) {}
              $('#signatureMessage').removeClass('hidden').addClass('text-red-600').html(m);
            }
          });
        });

        // Document Upload Modal (employee uploads; admin validates)
        $(document).on('click', '.upload-doc-btn', function() {
          const docType = $(this).data('doc-type');
          $('#uploadDocType').val(docType);
          $('#uploadDocTypeDisplay').val(docType);
          $('#documentFile').val('');
          $('#uploadMessage').addClass('hidden').html('');
          $('#uploadModal').removeClass('hidden');
        });
        $('#closeUploadModal, #cancelUpload').on('click', function() { $('#uploadModal').addClass('hidden'); });
        $('#uploadModal').on('click', function(e) { if (e.target === this) $('#uploadModal').addClass('hidden'); });
        $('#uploadForm').on('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          const $btn = $(this).find('button[type="submit"]');
          const originalText = $btn.text();
          $btn.prop('disabled', true).text('Uploading...');
          $('#uploadMessage').addClass('hidden').html('');
          $.ajax({
            url: 'document-upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
              if (res.status === 'success') {
                $('#uploadMessage').removeClass('hidden').addClass('bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-lg text-sm').html(res.message);
                setTimeout(function() { location.reload(); }, 1500);
              } else {
                $('#uploadMessage').removeClass('hidden').addClass('bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-lg text-sm').html(res.message || 'Upload failed');
                $btn.prop('disabled', false).text(originalText);
              }
            },
            error: function(xhr) {
              var m = 'Upload failed. Please try again.';
              try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(er) {}
              $('#uploadMessage').removeClass('hidden').addClass('bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-lg text-sm').html(m);
              $btn.prop('disabled', false).text(originalText);
            }
          });
        });

        var pendingRemoval = { docId: null, $btn: null };

        function showRemovalModal(docId, docName, $btn) {
          pendingRemoval.docId = docId;
          pendingRemoval.$btn = $btn;
          $('#removalDocName').text(docName || 'Document');
          $('#confirmRemovalBtn').prop('disabled', false);
          $('#confirmRemovalLabel').text('Yes, request removal');
          var $modal = $('#removalModal');
          var $card = $('#removalModalCard');
          $modal.removeClass('hidden').addClass('flex');
          requestAnimationFrame(function() {
            $card.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
          });
        }

        function hideRemovalModal() {
          var $modal = $('#removalModal');
          var $card = $('#removalModalCard');
          $card.removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
          setTimeout(function() {
            $modal.addClass('hidden').removeClass('flex');
          }, 180);
        }

        function showDocToast(type, title, body) {
          var $t = $('#docToast');
          var $icon = $('#docToastIcon');
          var $bar = $('#docToastBar');
          var palette = {
            success: { ring: 'border-emerald-200', icon: 'bg-emerald-100 text-emerald-600', bar: 'bg-emerald-500',
                       svg: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' },
            error:   { ring: 'border-red-200',     icon: 'bg-red-100 text-red-600',         bar: 'bg-red-500',
                       svg: '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' }
          };
          var p = palette[type] || palette.success;
          $t.removeClass('hidden border-emerald-200 border-red-200').addClass(p.ring);
          $icon.removeClass().addClass('flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center ' + p.icon).html(p.svg);
          $bar.removeClass().addClass('h-1 ' + p.bar);
          $('#docToastTitle').text(title || '');
          $('#docToastBody').text(body || '');
          requestAnimationFrame(function() {
            $t.removeClass('translate-x-4 opacity-0').addClass('translate-x-0 opacity-100');
          });
          clearTimeout(window.__docToastTimer);
          window.__docToastTimer = setTimeout(hideDocToast, 3500);
        }

        function hideDocToast() {
          var $t = $('#docToast');
          $t.removeClass('translate-x-0 opacity-100').addClass('translate-x-4 opacity-0');
          setTimeout(function() { $t.addClass('hidden'); }, 200);
        }

        $(document).on('click', '#docToastClose', hideDocToast);

        $(document).on('click', '.request-doc-removal-btn', function() {
          var docId = $(this).data('doc-id');
          var docName = $(this).data('doc-name')
            || $(this).closest('tr').find('td').eq(1).text().trim()
            || $(this).closest('.border-slate-200').find('h4').first().text().trim()
            || 'Document';
          showRemovalModal(docId, docName, $(this));
        });

        $(document).on('click', '#cancelRemovalBtn', hideRemovalModal);
        $('#removalModal').on('click', function(e) { if (e.target === this) hideRemovalModal(); });
        $(document).on('keydown', function(e) {
          if (e.key === 'Escape' && !$('#removalModal').hasClass('hidden')) hideRemovalModal();
        });

        $(document).on('click', '#confirmRemovalBtn', function() {
          if (!pendingRemoval.docId) return;
          var $cBtn = $(this);
          $cBtn.prop('disabled', true);
          $('#confirmRemovalLabel').text('Sending…');
          if (pendingRemoval.$btn) pendingRemoval.$btn.prop('disabled', true);
          $.ajax({
            url: 'document-request-deletion.php',
            type: 'POST',
            data: { id: pendingRemoval.docId },
            dataType: 'json',
            success: function(res) {
              if (res.status === 'success') {
                hideRemovalModal();
                showDocToast('success', 'Removal request sent', res.message || 'HR will review your request.');
                setTimeout(function() { location.reload(); }, 1100);
              } else {
                $cBtn.prop('disabled', false);
                $('#confirmRemovalLabel').text('Yes, request removal');
                if (pendingRemoval.$btn) pendingRemoval.$btn.prop('disabled', false);
                showDocToast('error', 'Request failed', res.message || 'Could not submit request.');
              }
            },
            error: function(xhr) {
              var m = 'Something went wrong. Please try again.';
              try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch (er) {}
              $cBtn.prop('disabled', false);
              $('#confirmRemovalLabel').text('Yes, request removal');
              if (pendingRemoval.$btn) pendingRemoval.$btn.prop('disabled', false);
              showDocToast('error', 'Request failed', m);
            }
          });
        });
      });
    </script>
</body>
</html>

