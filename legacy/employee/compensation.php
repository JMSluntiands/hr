<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

// Prevent caching so bank details show immediately after admin approval
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



    <!-- Main Content -->
    <main class="min-h-screen p-8 overflow-y-auto md:ml-64 md:pt-8 pt-16">
        <div id="main-inner">
            <!-- Top Bar -->
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-2xl font-semibold text-slate-800">My Compensation</h1>
            </div>

            <!-- MY BANK DETAILS Section -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-800">My Bank Details</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <?php
                        $compPrivacyTarget = 'bankPrivacyBody';
                        $compPrivacyPlaceholder = 'bankPrivacyPlaceholder';
                        include __DIR__ . '/include/compensation-privacy-snippet.php';
                        ?>
                        <?php if ($pendingBankRequest): ?>
                        <span class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 text-sm font-medium">Request pending approval</span>
                        <?php else: ?>
                        <button id="editBankBtn" class="px-4 py-2 bg-[#FA9800] text-white text-sm font-medium rounded-lg hover:bg-[#d18a15] transition-colors">
                            <?php echo $bankDetails ? 'Request change of account' : 'Request bank account'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="bankPrivacyPlaceholder" class="px-6 py-8 text-center text-sm text-slate-500">
                    <p>Bank details are hidden for privacy.</p>
                    <p class="text-xs mt-1 text-slate-400">Click <strong>View</strong> to show account information.</p>
                </div>
                <div id="bankPrivacyBody" class="p-6 hidden">
                    <?php if ($pendingBankRequest): ?>
                    <p class="text-amber-700 text-sm mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">You have a pending bank account change request. Admin will review and notify you. After approval, refresh this page to see your bank details.</p>
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
                        <p class="text-slate-500 text-xs mt-2">If your request was just approved, <a href="compensation.php" class="text-[#FA9800] font-medium hover:underline">refresh this page</a> to see your bank details.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Basic Compensation Section -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-800">Basic Compensation</h2>
                    <?php
                    $compPrivacyTarget = 'basicCompPrivacyBody';
                    $compPrivacyPlaceholder = 'basicCompPrivacyPlaceholder';
                    include __DIR__ . '/include/compensation-privacy-snippet.php';
                    ?>
                </div>
                <div id="basicCompPrivacyPlaceholder" class="px-6 py-8 text-center text-sm text-slate-500">
                    <p>Compensation details are hidden for privacy.</p>
                    <p class="text-xs mt-1 text-slate-400">Click <strong>View</strong> to show salary and allowances.</p>
                </div>
                <div id="basicCompPrivacyBody" class="p-6 hidden">
                    <?php if ($compensation): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-600 mb-1">Basic Salary (Daily)</label>
                                <p class="text-slate-800 text-lg font-semibold">₱<?php echo number_format($compensation['basic_salary_daily'], 2); ?></p>
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
                            $dailyGross = !empty($compensation['basic_salary_daily'])
                                ? (float)$compensation['basic_salary_daily']
                                : ((float)$currentSalary / 26);
                        ?>
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <h3 class="text-md font-semibold text-slate-700 mb-4">Gross Income</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-slate-600 mb-1">Daily Gross Income</label>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($dailyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Daily compensation basis</p>
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
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-800">Salary Adjustment / History</h2>
                    <?php
                    $compPrivacyTarget = 'salaryHistoryPrivacyBody';
                    $compPrivacyPlaceholder = 'salaryHistoryPrivacyPlaceholder';
                    include __DIR__ . '/include/compensation-privacy-snippet.php';
                    ?>
                </div>
                <div id="salaryHistoryPrivacyPlaceholder" class="px-6 py-8 text-center text-sm text-slate-500">
                    <p>Salary history is hidden for privacy.</p>
                    <p class="text-xs mt-1 text-slate-400">Click <strong>View</strong> to show past adjustments.</p>
                </div>
                <div id="salaryHistoryPrivacyBody" class="p-6 hidden">
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/sidebar-dropdown.js"></script>
    <script src="/assets/js/compensation-privacy.js"></script>
    <script>
        $(function() {
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

                const pathOnly = (url || '').split('#')[0].split('?')[0];
                if (url === 'index.php' || url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'reimbursement.php' || url === 'request.php' || pathOnly === 'inventory.php' || ['performance.php', 'performance-my-reviews.php', 'performance-form-review.php', 'performance-review-received.php', 'performance-review-submissions.php'].indexOf(pathOnly) !== -1 || ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'].indexOf(pathOnly) !== -1) {
                    window.location.href = url;
                    return;
                }

                $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#FA9800] font-medium rounded-l-none rounded-r-full');
                $('.js-side-link').addClass('rounded-lg');

                $('#main-inner').addClass('opacity-60 pointer-events-none');
                $('#main-inner').load(url + ' #main-inner', function() {
                    $('#main-inner').removeClass('opacity-60 pointer-events-none');
                });
            });
        });
    </script>
</body>
</html>
