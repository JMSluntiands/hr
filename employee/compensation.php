<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/employee_data.php';

// Function to ensure compensation tables exist
function ensureCompensationTables($conn) {
    // Check if employee_bank_details table exists
    $checkBank = $conn->query("SHOW TABLES LIKE 'employee_bank_details'");
    if ($checkBank->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `employee_bank_details` (
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
          KEY `idx_employee_id` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    // Check if employee_compensation table exists
    $checkComp = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    if ($checkComp->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `employee_compensation` (
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
          KEY `idx_employee_id` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    // Check if employee_salary_adjustments table exists
    $checkAdj = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
    if ($checkAdj->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `employee_salary_adjustments` (
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
          KEY `idx_date_approved` (`date_approved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

// Ensure tables exist
if ($conn) {
    ensureCompensationTables($conn);
}

// Get bank details
$bankDetails = null;
$pendingBankRequest = null;
if ($employeeDbId && $conn) {
    $bankStmt = $conn->prepare("SELECT * FROM employee_bank_details WHERE employee_id = ? LIMIT 1");
    if ($bankStmt) {
        $bankStmt->bind_param('i', $employeeDbId);
        $bankStmt->execute();
        $bankResult = $bankStmt->get_result();
        $bankDetails = $bankResult->fetch_assoc();
        $bankStmt->close();
    }
    $checkReqTable = $conn->query("SHOW TABLES LIKE 'bank_account_change_requests'");
    if ($checkReqTable && $checkReqTable->num_rows > 0) {
        $reqStmt = $conn->prepare("SELECT id, requested_at FROM bank_account_change_requests WHERE employee_id = ? AND status = 'Pending' ORDER BY requested_at DESC LIMIT 1");
        if ($reqStmt) {
            $reqStmt->bind_param('i', $employeeDbId);
            $reqStmt->execute();
            $reqResult = $reqStmt->get_result();
            $pendingBankRequest = $reqResult->fetch_assoc();
            $reqStmt->close();
        }
    }
}

// Get compensation details
$compensation = null;
$currentSalary = null; // Will be used for gross income calculation
if ($employeeDbId && $conn) {
    $compStmt = $conn->prepare("SELECT * FROM employee_compensation WHERE employee_id = ? LIMIT 1");
    if ($compStmt) {
        $compStmt->bind_param('i', $employeeDbId);
        $compStmt->execute();
        $compResult = $compStmt->get_result();
        $compensation = $compResult->fetch_assoc();
        $compStmt->close();
    }
    
    // Get latest salary adjustment to determine current salary
    $checkAdjTable = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
    if ($checkAdjTable && $checkAdjTable->num_rows > 0) {
        $adjStmt = $conn->prepare("SELECT new_salary FROM employee_salary_adjustments WHERE employee_id = ? ORDER BY date_approved DESC, created_at DESC LIMIT 1");
        if ($adjStmt) {
            $adjStmt->bind_param('i', $employeeDbId);
            $adjStmt->execute();
            $adjResult = $adjStmt->get_result();
            if ($adjRow = $adjResult->fetch_assoc()) {
                $currentSalary = $adjRow['new_salary'];
            }
            $adjStmt->close();
        }
    }
    
    // If no adjustment found, use compensation monthly salary
    if ($currentSalary === null && $compensation) {
        $currentSalary = $compensation['basic_salary_monthly'];
    }
}

// Get salary adjustment history
$salaryHistory = [];
if ($employeeDbId && $conn) {
    $histStmt = $conn->prepare("SELECT * FROM employee_salary_adjustments WHERE employee_id = ? ORDER BY date_approved DESC, created_at DESC");
    if ($histStmt) {
        $histStmt->bind_param('i', $employeeDbId);
        $histStmt->execute();
        $histResult = $histStmt->get_result();
        while ($row = $histResult->fetch_assoc()) {
            $salaryHistory[] = $row;
        }
        $histStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Compensation</title>
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
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white bg-white/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>My Compensation</span>
            </a>
            <a href="inventory.php"
               data-url="inventory.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2h-3V3H9v2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4m4 0v2m8-2v2" />
                </svg>
                <span>My Inventory</span>
            </a>
            <a href="progressive-discipline.php"
               data-url="progressive-discipline.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <span>Progressive Discipline</span>
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

    <!-- Main Content -->
    <main class="ml-64 min-h-screen p-8 overflow-y-auto">
        <div id="main-inner">
            <!-- Top Bar -->
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-2xl font-semibold text-slate-800">My Compensation</h1>
            </div>

            <!-- MY BANK DETAILS Section -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">My Bank Details</h2>
                    <div class="flex items-center gap-2">
                        <?php if ($pendingBankRequest): ?>
                        <span class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 text-sm font-medium">Request pending approval</span>
                        <?php else: ?>
                        <button id="editBankBtn" class="px-4 py-2 bg-[#FA9800] text-white text-sm font-medium rounded-lg hover:bg-[#d18a15] transition-colors">
                            <?php echo $bankDetails ? 'Request change of account' : 'Request bank account'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="p-6">
                    <?php if ($pendingBankRequest): ?>
                    <p class="text-amber-700 text-sm mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">You have a pending bank account change request. Admin will review and notify you.</p>
                    <?php endif; ?>
                    <?php if ($bankDetails): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Bank Name</label>
                                <p class="text-slate-800"><?php echo htmlspecialchars($bankDetails['bank_name']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Account Number</label>
                                <p class="text-slate-800"><?php echo htmlspecialchars($bankDetails['account_number']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Account Name</label>
                                <p class="text-slate-800"><?php echo htmlspecialchars($bankDetails['account_name']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Account Type</label>
                                <p class="text-slate-800"><?php echo htmlspecialchars($bankDetails['account_type']); ?></p>
                            </div>
                            <?php if (!empty($bankDetails['branch'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Branch</label>
                                <p class="text-slate-800"><?php echo htmlspecialchars($bankDetails['branch']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-500 text-sm">No bank details on file. Click "Request bank account" to submit a request for admin approval.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Basic Compensation Section -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Basic Compensation</h2>
                    <button type="button"
                            class="js-privacy-toggle inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
                            data-target="basicCompPrivacyBody"
                            aria-label="Hide card details">
                        <svg class="w-4 h-4 js-eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                        </svg>
                        <svg class="w-4 h-4 js-eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.943-9.543-7a9.965 9.965 0 012.11-3.592M6.223 6.223A9.956 9.956 0 0112 5c4.478 0 8.27 2.943 9.543 7a9.97 9.97 0 01-4.132 5.411M15 12a3 3 0 00-4.2-2.8M9.88 9.88A3 3 0 0014.12 14.12M3 3l18 18" />
                        </svg>
                        <span class="js-eye-label">Hide</span>
                    </button>
                </div>
                <div id="basicCompPrivacyBody" class="p-6">
                    <?php if ($compensation): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Basic Salary (Monthly)</label>
                                <p class="text-slate-800 text-lg font-semibold">₱<?php echo number_format($compensation['basic_salary_monthly'], 2); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Basic Salary (Daily)</label>
                                <p class="text-slate-800 text-lg font-semibold">₱<?php echo number_format($compensation['basic_salary_daily'], 2); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Basic Salary (Annually)</label>
                                <p class="text-slate-800 text-lg font-semibold">₱<?php echo number_format($compensation['basic_salary_annually'], 2); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Employment Type</label>
                                <p class="text-slate-800"><?php echo htmlspecialchars($compensation['employment_type']); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Effective Date</label>
                                <p class="text-slate-800"><?php echo date('M d, Y', strtotime($compensation['effective_date'])); ?></p>
                            </div>
                        </div>

                        <!-- Allowances -->
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <h3 class="text-md font-semibold text-slate-700 mb-4">Allowances</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Internet</label>
                                    <p class="text-slate-800">₱<?php echo number_format($compensation['allowance_internet'], 2); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Meal</label>
                                    <p class="text-slate-800">₱<?php echo number_format($compensation['allowance_meal'], 2); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Position / Representation</label>
                                    <p class="text-slate-800">₱<?php echo number_format($compensation['allowance_position'], 2); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Transportation</label>
                                    <p class="text-slate-800">₱<?php echo number_format($compensation['allowance_transportation'], 2); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Gross Income (based on new salary) -->
                        <?php if ($currentSalary): 
                            $monthlyGross = $currentSalary;
                            $dailyGross = $monthlyGross / 22; // Assuming 22 working days per month
                            $annualGross = $monthlyGross * 12;
                        ?>
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <h3 class="text-md font-semibold text-slate-700 mb-4">Gross Income</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Monthly Gross Income</label>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($monthlyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Based on current salary</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Daily Gross Income</label>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($dailyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Based on 22 working days</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Annual Gross Income</label>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($annualGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Monthly × 12 months</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-slate-500 text-sm">Compensation information not available.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Salary Adjustment / History Section -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-lg font-semibold text-slate-800">Salary Adjustment / History</h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($salaryHistory)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Previous Salary</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">New Salary</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reason</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Approved By</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date Approved</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($salaryHistory as $history): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 text-slate-700">₱<?php echo number_format($history['previous_salary'], 2); ?></td>
                                            <td class="px-4 py-3 text-slate-700 font-semibold">₱<?php echo number_format($history['new_salary'], 2); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($history['reason']); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($history['approved_by'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo date('M d, Y', strtotime($history['date_approved'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-500 text-sm">No salary adjustment history available.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Bank Details Modal (request for admin approval) -->
    <div id="bankModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800"><?php echo $bankDetails ? 'Request change of account' : 'Request bank account'; ?></h3>
                <button id="closeBankModal" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="bankForm" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-slate-700 mb-2">Bank Name *</label>
                        <input type="text" id="bank_name" name="bank_name" required
                               value="<?php echo $bankDetails ? htmlspecialchars($bankDetails['bank_name']) : ''; ?>"
                               class="w-full p-3 border-2 border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800] focus:border-[#FA9800]">
                    </div>
                    <div>
                        <label for="account_number" class="block text-sm font-medium text-slate-700 mb-2">Account Number *</label>
                        <input type="text" id="account_number" name="account_number" required
                               value="<?php echo $bankDetails ? htmlspecialchars($bankDetails['account_number']) : ''; ?>"
                               class="w-full p-3 border-2 border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800] focus:border-[#FA9800]">
                    </div>
                    <div>
                        <label for="account_name" class="block text-sm font-medium text-slate-700 mb-2">Account Name *</label>
                        <input type="text" id="account_name" name="account_name" required
                               value="<?php echo $bankDetails ? htmlspecialchars($bankDetails['account_name']) : ''; ?>"
                               class="w-full p-3 border-2 border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800] focus:border-[#FA9800]">
                    </div>
                    <div>
                        <label for="account_type" class="block text-sm font-medium text-slate-700 mb-2">Account Type *</label>
                        <select id="account_type" name="account_type" required
                                class="w-full p-3 border-2 border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800] focus:border-[#FA9800]">
                            <option value="Savings" <?php echo ($bankDetails && $bankDetails['account_type'] === 'Savings') ? 'selected' : ''; ?>>Savings</option>
                            <option value="Checking" <?php echo ($bankDetails && $bankDetails['account_type'] === 'Checking') ? 'selected' : ''; ?>>Checking</option>
                            <option value="Current" <?php echo ($bankDetails && $bankDetails['account_type'] === 'Current') ? 'selected' : ''; ?>>Current</option>
                        </select>
                    </div>
                    <div>
                        <label for="branch" class="block text-sm font-medium text-slate-700 mb-2">Branch</label>
                        <input type="text" id="branch" name="branch"
                               value="<?php echo $bankDetails ? htmlspecialchars($bankDetails['branch'] ?? '') : ''; ?>"
                               class="w-full p-3 border-2 border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FA9800] focus:border-[#FA9800]">
                    </div>
                </div>
                <p class="text-slate-500 text-sm mt-2">Changes require admin approval. You will see the update after it is approved.</p>
                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-[#FA9800] text-white font-medium rounded-lg hover:bg-[#d18a15] transition-colors">
                        Submit request
                    </button>
                    <button type="button" id="cancelBankBtn" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
                <div id="bankMessage" class="mt-4 hidden"></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            function setPrivacyState($button, isHidden) {
                const targetId = $button.data('target');
                if (!targetId) return;
                const $target = $('#' + targetId);
                $target.toggleClass('hidden', isHidden);
                $button.find('.js-eye-open').toggleClass('hidden', isHidden);
                $button.find('.js-eye-closed').toggleClass('hidden', !isHidden);
                $button.find('.js-eye-label').text(isHidden ? 'Show' : 'Hide');
                $button.attr('aria-label', isHidden ? 'Show card details' : 'Hide card details');
            }

            $('.js-privacy-toggle').on('click', function() {
                const $button = $(this);
                const targetId = $button.data('target');
                const $target = $('#' + targetId);
                const isHidden = !$target.hasClass('hidden');
                setPrivacyState($button, isHidden);
            });

            // Bank Details Modal
            $('#editBankBtn').on('click', function() {
                $('#bankModal').removeClass('hidden');
            });

            $('#closeBankModal, #cancelBankBtn').on('click', function() {
                $('#bankModal').addClass('hidden');
                $('#bankMessage').addClass('hidden').html('');
            });

            $('#bankForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $('#bankMessage').addClass('hidden').html('');
                $.ajax({
                    url: 'bank-account-request.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#bankMessage').removeClass('hidden').addClass('p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm').html(res.message);
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            $('#bankMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(res.message || 'Error submitting request');
                        }
                    },
                    error: function(xhr) {
                        var message = 'Error submitting request. Please try again.';
                        try { var res = JSON.parse(xhr.responseText); if (res.message) message = res.message; } catch(er) {}
                        $('#bankMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(message);
                    }
                });
            });

            // Sidebar navigation
            $('.js-side-link').on('click', function(e) {
                const url = $(this).data('url');
                if (!url) return;
                e.preventDefault();

                if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'inventory.php') {
                    window.location.href = url;
                    return;
                }

                $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#FA9800] font-medium rounded-l-none rounded-r-full');
                $('.js-side-link').addClass('rounded-lg');

                $('#main-inner').addClass('opacity-60 pointer-events-none');
                $('#main-inner').load(url + ' #main-inner > *', function() {
                    $('#main-inner').removeClass('opacity-60 pointer-events-none');
                });
            });
        });
    </script>
</body>
</html>
