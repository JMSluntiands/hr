<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';

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

// Documents as list (same as admin staff-view)
$documents = [];
$compensation = null;
$latestAdjustment = null;
$bankDetails = null;

if ($employeeDbId && $conn) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $docStmt = $conn->prepare("SELECT id, document_type, file_path, status, created_at, updated_at FROM employee_document_uploads WHERE employee_id = ? ORDER BY document_type, created_at DESC");
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

// Required document types for HRIS 201; latest upload per type for status
$documentTypes = ['Birth Certificate (PSA)', 'Government IDs (Valid ID Set)', 'Employment Contract', 'Company ID Form'];
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
    <!-- Sidebar (fixed) -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#FA9800] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
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
        <nav class="flex-1 p-4 space-y-2">
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
            <!-- My Time Off -->
            <a href="timeoff.php"
               data-url="timeoff.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>My Time Off</span>
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
            <!-- My Compensation -->
            <a href="compensation.php"
               data-url="compensation.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>My Compensation</span>
            </a>
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
        <div class="p-4 border-t border-white/20">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content (only this area scrolls) -->
    <main class="ml-64 min-h-screen p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header (view only - same as admin) -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Profile</h1>
                <p class="text-sm text-slate-500 mt-1">View your employee details and information</p>
            </div>
        </div>

        <?php if (!$employee): ?>
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">No employee record found for your account. Please contact HR.</div>
        <?php else: ?>

        <!-- Employee Details (same layout as admin staff-view) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <div class="flex items-start gap-6 mb-6">
                <div class="flex flex-col items-center gap-3 flex-shrink-0">
                    <div id="profilePhotoPreview" class="w-24 h-24 rounded-full overflow-hidden bg-slate-200 flex items-center justify-center">
                        <?php
                        $photo = !empty($employee['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $employee['profile_picture']) ? $employee['profile_picture'] : null;
                        if ($photo): ?>
                            <img id="profilePhotoImg" src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="" class="w-full h-full object-cover">
                            <span id="profilePhotoInitial" class="text-3xl font-semibold text-slate-500 hidden"><?php echo strtoupper(substr($employee['full_name'] ?? '?', 0, 1)); ?></span>
                        <?php else: ?>
                            <img id="profilePhotoImg" src="" alt="" class="w-full h-full object-cover hidden">
                            <span id="profilePhotoInitial" class="text-3xl font-semibold text-slate-500"><?php echo strtoupper(substr($employee['full_name'] ?? '?', 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <form id="profilePhotoForm" enctype="multipart/form-data" class="flex flex-col items-center gap-1">
                        <input type="file" name="profile_picture" id="profilePhotoInput" accept="image/jpeg,image/jpg,image/png" class="hidden">
                        <button type="button" id="profilePhotoBtn" class="px-3 py-1.5 bg-[#d97706] hover:bg-[#b45309] text-white text-sm font-medium rounded-lg transition-colors">Upload photo</button>
                        <p class="text-xs text-slate-500">JPG or PNG, max 2MB</p>
                    </form>
                    <div id="profilePhotoMessage" class="hidden text-sm text-center max-w-[200px]"></div>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-semibold text-slate-800 mb-1"><?php echo htmlspecialchars($employee['full_name'] ?? ''); ?></h2>
                    <p class="text-sm text-slate-500 mb-2"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($employee['status'] ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                        <?php echo htmlspecialchars($employee['status'] ?? 'Active'); ?>
                    </span>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="border-t border-slate-200 pt-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Email Address</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Phone Number</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Birthdate</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['birthdate']) ? date('M d, Y', strtotime($employee['birthdate'])) : 'N/A'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Gender</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['gender'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-slate-500 mb-1">Address</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['address'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Emergency Contact Person</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['emergency_contact_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Emergency Contact Number</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['emergency_contact_phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="border-t border-slate-200 pt-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Employment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Position</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Department</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['department'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Date Hired</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['date_hired']) ? date('M d, Y', strtotime($employee['date_hired'])) : 'N/A'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Created At</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['created_at']) ? date('M d, Y H:i', strtotime($employee['created_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Government Information -->
            <div class="border-t border-slate-200 pt-6 mb-6">
                <h3 class="text-lg font-semibold text-slate-800 mb-4">Government Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-slate-500 mb-1">SSS Number</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['sss'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">PhilHealth Number</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['philhealth'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Pag-IBIG (HDMF) Number</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['pagibig'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">TIN Number</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['tin'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compensation Details (view only) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-lg font-semibold text-slate-800">Compensation Details</h3>
            </div>
            <div class="p-6">
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
                                <p class="text-sm text-slate-500 mb-1">Basic Salary (Monthly)</p>
                                <p class="font-medium text-slate-800 text-lg">₱<?php echo number_format($compensation['basic_salary_monthly'] ?? 0, 2); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Basic Salary (Daily)</p>
                                <p class="font-medium text-slate-800 text-lg">₱<?php echo number_format($compensation['basic_salary_daily'] ?? 0, 2); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">Basic Salary (Annually)</p>
                                <p class="font-medium text-slate-800 text-lg">₱<?php echo number_format($compensation['basic_salary_annually'] ?? 0, 2); ?></p>
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
                            $totalAllowances = ($compensation['allowance_internet'] ?? 0) + ($compensation['allowance_meal'] ?? 0) + ($compensation['allowance_position'] ?? 0) + ($compensation['allowance_transportation'] ?? 0);
                            $monthlyGross = $currentSalary + $totalAllowances;
                            $dailyGross = $monthlyGross / 22;
                            $annualGross = $monthlyGross * 12;
                        ?>
                        <div class="md:col-span-2 border-slate-200 mt-4">
                            <h4 class="text-md font-semibold text-slate-700 mb-4">Gross Income (Based on Current Salary)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Monthly Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($monthlyGross, 2); ?></p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Daily Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($dailyGross, 2); ?></p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Annual Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($annualGross, 2); ?></p>
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

        <!-- Upload Documents (employee uploads; admin validates in Request Document) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800">Upload Documents</h3>
                <p class="text-sm text-slate-500 mt-1">Upload HRIS 201 documents. Admin will validate each upload.</p>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
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
                            if (!$hasFile) {
                                $status = 'No File';
                                $statusClass = 'bg-slate-100 text-slate-600';
                                $statusText = 'No File';
                            } else {
                                $status = $doc['status'] ?? 'Pending';
                                $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                $statusText = $status === 'Approved' ? 'Verified' : ($status === 'Rejected' ? 'Rejected' : 'Pending validation');
                            }
                            $lastUpdated = $doc && isset($doc['updated_at']) && $doc['updated_at'] ? date('M d, Y', strtotime($doc['updated_at'])) : ($doc && !empty($doc['created_at']) ? date('M d, Y', strtotime($doc['created_at'])) : '—');
                        ?>
                        <tr>
                            <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars($docType); ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs"><?php echo $lastUpdated; ?></td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="upload-doc-btn px-3 py-1.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white text-xs font-medium transition-colors" data-doc-type="<?php echo htmlspecialchars($docType); ?>" title="Upload">Upload</button>
                                    <?php if ($hasFile): ?>
                                    <a href="document-view.php?id=<?php echo (int)$doc['id']; ?>" target="_blank" class="px-3 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium transition-colors" title="View">View</a>
                                    <?php else: ?>
                                    <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-400 text-xs cursor-not-allowed">View</span>
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
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800">Your Uploaded Documents</h3>
                <p class="text-sm text-slate-500 mt-1">Documents you uploaded; status updated after admin validation</p>
            </div>
            <div class="p-6">
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
                            $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                        ?>
                        <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($doc['document_type'] ?? 'Document'); ?></h4>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </div>
                            <?php if ($fileUrl && file_exists(__DIR__ . '/../uploads/' . $doc['file_path'])): ?>
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
                            <?php else: ?>
                                <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center"><p class="text-xs text-slate-500">File not found</p></div>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          // My Profile, Compensation, and Time Off: full page load so content and modals always work correctly
          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php') {
            window.location.href = url;
            return;
          }

          // Remove any active state from all links
          $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#FA9800] font-medium rounded-l-none rounded-r-full');
          $('.js-side-link').addClass('rounded-lg');

          // Load only the right content
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
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
      });
    </script>
</body>
</html>

