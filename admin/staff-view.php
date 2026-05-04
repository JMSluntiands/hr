<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

// Include database connection
include '../database/db.php';

// Get employee ID from URL
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$employeeId) {
    header('Location: staff.php');
    exit;
}

$staffDocumentAdded = !empty($_SESSION['staff_document_added']);
if ($staffDocumentAdded) {
    unset($_SESSION['staff_document_added']);
}

// Fetch employee data
$employee = null;
$documents = [];
$compensation = null;
$latestAdjustment = null;
$salaryAdjustments = [];
$bankDetails = null;
$employmentTypeName = null;
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
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();
    
    if (!$employee) {
        header('Location: staff.php');
        exit;
    }

    // Resolve employment type name from master table
    if (!empty($employee['employment_type_id'])) {
        $typeStmt = $conn->prepare("SELECT name FROM employment_types WHERE id = ? LIMIT 1");
        if ($typeStmt) {
            $typeStmt->bind_param('i', $employee['employment_type_id']);
            $typeStmt->execute();
            $typeRes = $typeStmt->get_result();
            if ($typeRow = $typeRes->fetch_assoc()) {
                $employmentTypeName = $typeRow['name'] ?? null;
            }
            $typeStmt->close();
        }
    }
    
    // Fetch employee documents
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $docStmt = $conn->prepare("SELECT id, document_type, file_path, status, created_at, updated_at 
                                   FROM employee_document_uploads 
                                   WHERE employee_id = ? 
                                   ORDER BY document_type, created_at DESC");
        if ($docStmt) {
            $docStmt->bind_param('i', $employeeId);
            $docStmt->execute();
            $docResult = $docStmt->get_result();
            while ($row = $docResult->fetch_assoc()) {
                $documents[] = $row;
            }
            $docStmt->close();
        }
    }
    
    // Fetch compensation details
    $checkCompTable = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    if ($checkCompTable && $checkCompTable->num_rows > 0) {
        $compStmt = $conn->prepare("SELECT * FROM employee_compensation WHERE employee_id = ? LIMIT 1");
        if ($compStmt) {
            $compStmt->bind_param('i', $employeeId);
            $compStmt->execute();
            $compResult = $compStmt->get_result();
            $compensation = $compResult->fetch_assoc();
            $compStmt->close();
        }
    }
    
    // Fetch latest salary adjustment
    $checkAdjTable = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
    if ($checkAdjTable && $checkAdjTable->num_rows > 0) {
        $adjStmt = $conn->prepare("SELECT * FROM employee_salary_adjustments WHERE employee_id = ? ORDER BY date_approved DESC, created_at DESC LIMIT 1");
        if ($adjStmt) {
            $adjStmt->bind_param('i', $employeeId);
            $adjStmt->execute();
            $adjResult = $adjStmt->get_result();
            $latestAdjustment = $adjResult->fetch_assoc();
            $adjStmt->close();
        }

        $adjAllStmt = $conn->prepare("SELECT previous_salary, new_salary, reason, approved_by, date_approved, created_at FROM employee_salary_adjustments WHERE employee_id = ? ORDER BY date_approved DESC, created_at DESC");
        if ($adjAllStmt) {
            $adjAllStmt->bind_param('i', $employeeId);
            $adjAllStmt->execute();
            $adjAllResult = $adjAllStmt->get_result();
            while ($adjRow = $adjAllResult->fetch_assoc()) {
                $salaryAdjustments[] = $adjRow;
            }
            $adjAllStmt->close();
        }
    }
    
    // Fetch bank details
    $checkBankTable = $conn->query("SHOW TABLES LIKE 'employee_bank_details'");
    if ($checkBankTable && $checkBankTable->num_rows > 0) {
        $bankStmt = $conn->prepare("SELECT * FROM employee_bank_details WHERE employee_id = ? LIMIT 1");
        if ($bankStmt) {
            $bankStmt->bind_param('i', $employeeId);
            $bankStmt->execute();
            $bankResult = $bankStmt->get_result();
            $bankDetails = $bankResult->fetch_assoc();
            $bankStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employee - Admin</title>
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
<body class="font-inter bg-gradient-to-b from-slate-100 to-slate-50 min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="min-h-screen overflow-y-auto pt-16 md:pt-8 md:ml-64 px-4 md:px-8 pb-10">
        <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-2">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">View Employee</h1>
                <p class="text-sm text-slate-500 mt-1">Full employee profile and records</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="staff-edit.php?id=<?php echo $employeeId; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Employee
                </a>
                <a href="staff" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 font-medium text-sm border border-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        <?php if ($staffDocumentAdded): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex items-center gap-3 text-emerald-800 text-sm mb-4">
            <svg class="w-5 h-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium">Document added successfully.</span>
        </div>
        <?php endif; ?>

        <!-- Hero Card: core identity -->
        <div class="bg-white rounded-2xl shadow-md border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-20 md:h-24"></div>
            <div class="px-6 md:px-8 pb-6 -mt-10 md:-mt-12 relative">
                <div class="flex flex-col md:flex-row md:items-end gap-6">
                    <div class="flex items-center gap-4 md:gap-5">
                        <div class="w-24 h-24 md:w-28 md:h-28 rounded-2xl overflow-hidden bg-white border-4 border-white shadow-lg flex items-center justify-center flex-shrink-0">
                            <?php 
                            $photo = !empty($employee['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $employee['profile_picture']) ? $employee['profile_picture'] : null;
                            if ($photo): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-3xl md:text-4xl font-bold text-amber-600"><?php echo strtoupper(substr($employee['full_name'] ?? '?', 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-left">
                            <h2 class="text-xl md:text-2xl font-bold text-slate-800 mb-1"><?php echo htmlspecialchars($employee['full_name'] ?? ''); ?></h2>
                            <p class="text-xs md:text-sm text-slate-500 font-mono mb-2"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></p>
                            <div class="flex flex-wrap items-center gap-2 text-xs md:text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium <?php echo ($employee['status'] ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo htmlspecialchars($employee['status'] ?? 'Active'); ?>
                                </span>
                                <?php if (!empty($employee['position'])): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                        <?php echo htmlspecialchars($employee['position']); ?>
                                        <?php if (!empty($employee['department'])): ?>
                                            <span class="mx-1 text-slate-400">·</span><?php echo htmlspecialchars($employee['department']); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal & employment info card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Personal Information -->
            <div class="p-6 md:p-8 bg-gradient-to-b from-slate-50 to-white border-b border-slate-100">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Personal Information</h3>
                        <p class="text-sm text-slate-500">Contact details and work locations</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Email</p>
                        <p class="font-medium text-slate-800 truncate" title="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Phone</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Birthdate</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['birthdate']) ? date('M d, Y', strtotime($employee['birthdate'])) : 'N/A'; ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Gender</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['gender'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100 sm:col-span-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Primary Workplace</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['address'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-white border border-slate-100 sm:col-span-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Secondary Workplace</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['secondary_workplace'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-100 sm:col-span-2 lg:col-span-3">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Emergency Contact</p>
                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($employee['emergency_contact_name'] ?? 'N/A'); ?></p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php echo htmlspecialchars($employee['emergency_contact_relationship'] ?? ''); ?>
                            <?php if (!empty($employee['emergency_contact_phone'])): ?>
                                · <?php echo htmlspecialchars($employee['emergency_contact_phone']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="p-6 md:p-8 border-t border-slate-100">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z\"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Employment</h3>
                        <p class="text-sm text-slate-500">Role, department and status</p>
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
                        <p class="font-medium text-slate-800">
                            <?php
                            if ($employmentTypeName) {
                                echo htmlspecialchars($employmentTypeName);
                            } elseif (!empty($compensation['employment_type'])) {
                                echo htmlspecialchars($compensation['employment_type']);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Date Hired</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['date_hired']) ? date('M d, Y', strtotime($employee['date_hired'])) : 'N/A'; ?></p>
                    </div>
                    <?php if (($employee['status'] ?? '') === 'Inactive'): ?>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Date inactive</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['date_inactive']) ? date('M d, Y', strtotime($employee['date_inactive'])) : 'N/A'; ?></p>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Resignation letter</p>
                        <?php if (!empty($employee['resignation_letter_path']) && file_exists(__DIR__ . '/../uploads/' . $employee['resignation_letter_path'])): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($employee['resignation_letter_path']); ?>" target="_blank" rel="noopener" class="font-medium text-amber-700 hover:underline">View file</a>
                        <?php else: ?>
                            <p class="font-medium text-slate-600">No file on record</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Created At</p>
                        <p class="font-medium text-slate-800"><?php echo !empty($employee['created_at']) ? date('M d, Y H:i', strtotime($employee['created_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Government Information -->
            <div class="p-6 md:p-8 border-t border-slate-100 bg-slate-50/40">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Government IDs</h3>
                        <p class="text-sm text-slate-500">SSS, PhilHealth, Pag-IBIG, TIN & clearances</p>
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

        <!-- Compensation Details Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Compensation Details</h3>
            </div>
            <div class="p-6">
                <?php 
                // Get current salary (from latest adjustment or compensation)
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
                        
                        <!-- Gross Income based on New Salary -->
                        <?php if ($currentSalary): 
                            $monthlyGross = $currentSalary;
                            $dailyGross = $monthlyGross / 26;
                            $annualGross = $monthlyGross * 12;
                        ?>
                        <div class="md:col-span-2 border-slate-200">
                            <h4 class="text-md font-semibold text-slate-700 mb-4">Gross Income (Based on New Salary)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Monthly Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($monthlyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Based on current salary</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Daily Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($dailyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Monthly ÷ 26</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Annual Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($annualGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Monthly × 12 months</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($compensation): ?>
                            <div class="md:col-span-2 mt-4 pt-4 border-t border-slate-200">
                                <h4 class="text-md font-semibold text-slate-700 mb-3">Allowances</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Internet</p>
                                        <p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_internet'] ?? 0, 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Meal</p>
                                        <p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_meal'] ?? 0, 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Position/Representation</p>
                                        <p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_position'] ?? 0, 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Transportation</p>
                                        <p class="font-medium text-slate-800">₱<?php echo number_format($compensation['allowance_transportation'] ?? 0, 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-slate-500 text-sm">No compensation information available.</p>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <h4 class="text-md font-semibold text-slate-700 mb-4">Quick Actions</h4>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" id="viewAdjustmentsBtn" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>View All Adjustments</span>
                        </button>
                    </div>
                </div>
                
                <!-- Bank Details Section -->
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
                        <p class="text-slate-500 text-sm">No bank details added by employee yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Salary Adjustments Modal -->
        <div id="adjustmentsModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Salary Adjustment History</h3>
                    <button type="button" id="closeAdjustmentsModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[75vh]">
                    <?php if (empty($salaryAdjustments)): ?>
                        <p class="text-sm text-slate-500">No salary adjustment history available.</p>
                    <?php else: ?>
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
                                    <?php foreach ($salaryAdjustments as $adjustment): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 text-slate-700">₱<?php echo number_format((float)($adjustment['previous_salary'] ?? 0), 2); ?></td>
                                            <td class="px-4 py-3 text-slate-700 font-semibold">₱<?php echo number_format((float)($adjustment['new_salary'] ?? 0), 2); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($adjustment['reason'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($adjustment['approved_by'] ?? 'N/A'); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo !empty($adjustment['date_approved']) ? date('M d, Y', strtotime($adjustment['date_approved'])) : 'N/A'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Employee Documents</h3>
                    <p class="text-sm text-slate-500 mt-1">Checklist and files uploaded by employee</p>
                </div>
                <a href="staff-add-document.php?id=<?php echo (int)$employeeId; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Document
                </a>
            </div>
            <div class="p-6 space-y-6">
                <?php
                $documentsByType = [];
                foreach ($documents as $d) {
                    if (!isset($documentsByType[$d['document_type']])) {
                        $documentsByType[$d['document_type']] = $d;
                    }
                }
                ?>
                <!-- Checklist -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Document Checklist</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs md:text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="w-10 text-center px-3 py-2 font-semibold text-slate-500 uppercase tracking-wide">Done</th>
                                    <th class="text-left px-4 py-2 font-semibold text-slate-500 uppercase tracking-wide">Document Type</th>
                                    <th class="text-left px-4 py-2 font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                                    <th class="text-left px-4 py-2 font-semibold text-slate-500 uppercase tracking-wide">Last Updated</th>
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
                                        $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' :
                                                      ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                        $statusText = $status === 'Approved' ? 'Approved' :
                                                      ($status === 'Rejected' ? 'Rejected' : 'Pending validation');
                                    }
                                    $lastUpdated = $doc && isset($doc['updated_at']) && $doc['updated_at']
                                        ? date('M d, Y', strtotime($doc['updated_at']))
                                        : ($doc && !empty($doc['created_at']) ? date('M d, Y', strtotime($doc['created_at'])) : '—');
                                    $isApproved = $status === 'Approved';
                                ?>
                                <tr>
                                    <td class="px-3 py-2 text-center">
                                        <?php if ($isApproved): ?>
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </span>
                                        <?php elseif ($hasFile): ?>
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-amber-300 text-amber-400" title="Uploaded but not approved yet">
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
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($statusText); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-slate-500 text-xs"><?php echo $lastUpdated; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Uploaded files grid -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Uploaded Files</h4>
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
                                $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : 
                                              ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                            ?>
                            <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition-colors">
                                <div class="flex items-start justify-between mb-3">
                                    <h4 class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($doc['document_type'] ?? 'Document'); ?></h4>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </div>
                                
                                <?php if ($fileUrl && file_exists(__DIR__ . '/../uploads/' . $doc['file_path'])): ?>
                                    <?php if ($isImage): ?>
                                        <div class="mb-3 rounded border border-slate-200 overflow-hidden">
                                            <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($doc['document_type'] ?? ''); ?>" class="w-full h-32 object-cover">
                                        </div>
                                    <?php elseif ($isPdf): ?>
                                        <div class="mb-3 p-4 bg-red-50 rounded border border-red-200 text-center">
                                            <svg class="w-12 h-12 mx-auto text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-xs text-red-700 font-medium">PDF Document</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center">
                                            <svg class="w-12 h-12 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-xs text-slate-700 font-medium">Document File</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo $fileUrl; ?>" target="_blank" class="inline-flex items-center gap-2 text-sm text-[#d97706] hover:text-[#b45309] font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        View/Download
                                    </a>
                                <?php else: ?>
                                    <div class="mb-3 p-4 bg-slate-50 rounded border border-slate-200 text-center">
                                        <p class="text-xs text-slate-500">File not found</p>
                                    </div>
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
        </div>
    </main>

    <script>
        // Dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Declare all variables first
            const employeesBtn = document.getElementById('employees-dropdown-btn');
            const employeesDropdown = document.getElementById('employees-dropdown');
            const employeesArrow = document.getElementById('employees-arrow');
            const leavesBtn = document.getElementById('leaves-dropdown-btn');
            const leavesDropdown = document.getElementById('leaves-dropdown');
            const leavesArrow = document.getElementById('leaves-arrow');
            const requestBtn = document.getElementById('request-dropdown-btn');
            const requestDropdown = document.getElementById('request-dropdown');
            const requestArrow = document.getElementById('request-arrow');
            const viewAdjustmentsBtn = document.getElementById('viewAdjustmentsBtn');
            const adjustmentsModal = document.getElementById('adjustmentsModal');
            const closeAdjustmentsModal = document.getElementById('closeAdjustmentsModal');

            function closeOtherDropdowns(exclude) {
                if (exclude !== 'employees' && employeesDropdown) { employeesDropdown.classList.add('hidden'); if (employeesArrow) employeesArrow.style.transform = 'rotate(0deg)'; }
                if (exclude !== 'leaves' && leavesDropdown) { leavesDropdown.classList.add('hidden'); if (leavesArrow) leavesArrow.style.transform = 'rotate(0deg)'; }
                if (exclude !== 'request' && requestDropdown) { requestDropdown.classList.add('hidden'); if (requestArrow) requestArrow.style.transform = 'rotate(0deg)'; }
            }

            function toggleEmployeesDropdown() {
                if (!employeesDropdown || !employeesBtn) return;
                closeOtherDropdowns('employees');
                const isHidden = employeesDropdown.classList.contains('hidden');
                employeesDropdown.classList.toggle('hidden');
                if (isHidden) {
                    if (employeesArrow) employeesArrow.style.transform = 'rotate(180deg)';
                } else {
                    if (employeesArrow) employeesArrow.style.transform = 'rotate(0deg)';
                }
            }

            function toggleLeavesDropdown() {
                if (!leavesDropdown || !leavesBtn) return;
                closeOtherDropdowns('leaves');
                const isHidden = leavesDropdown.classList.contains('hidden');
                leavesDropdown.classList.toggle('hidden');
                if (isHidden) {
                    if (leavesArrow) leavesArrow.style.transform = 'rotate(180deg)';
                } else {
                    if (leavesArrow) leavesArrow.style.transform = 'rotate(0deg)';
                }
            }

            function toggleRequestDropdown() {
                if (!requestDropdown || !requestBtn) return;
                closeOtherDropdowns('request');
                const isHidden = requestDropdown.classList.contains('hidden');
                requestDropdown.classList.toggle('hidden');
                if (isHidden) {
                    if (requestArrow) requestArrow.style.transform = 'rotate(180deg)';
                } else {
                    if (requestArrow) requestArrow.style.transform = 'rotate(0deg)';
                }
            }

            if (employeesBtn) {
                employeesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleEmployeesDropdown(); });
            }
            if (leavesBtn) {
                leavesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleLeavesDropdown(); });
            }
            if (requestBtn) {
                requestBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleRequestDropdown(); });
            }

            if (viewAdjustmentsBtn && adjustmentsModal) {
                viewAdjustmentsBtn.addEventListener('click', function() {
                    adjustmentsModal.classList.remove('hidden');
                    adjustmentsModal.classList.add('flex');
                });
            }
            if (closeAdjustmentsModal && adjustmentsModal) {
                closeAdjustmentsModal.addEventListener('click', function() {
                    adjustmentsModal.classList.remove('flex');
                    adjustmentsModal.classList.add('hidden');
                });
            }
            if (adjustmentsModal) {
                adjustmentsModal.addEventListener('click', function(e) {
                    if (e.target === adjustmentsModal) {
                        adjustmentsModal.classList.remove('flex');
                        adjustmentsModal.classList.add('hidden');
                    }
                });
            }

            document.addEventListener('click', function(e) {
                if (employeesBtn && employeesDropdown && !employeesBtn.contains(e.target) && !employeesDropdown.contains(e.target)) {
                    employeesDropdown.classList.add('hidden');
                    if (employeesArrow) employeesArrow.style.transform = 'rotate(0deg)';
                }
                if (leavesBtn && leavesDropdown && !leavesBtn.contains(e.target) && !leavesDropdown.contains(e.target)) {
                    leavesDropdown.classList.add('hidden');
                    if (leavesArrow) leavesArrow.style.transform = 'rotate(0deg)';
                }
                if (requestBtn && requestDropdown && !requestBtn.contains(e.target) && !requestDropdown.contains(e.target)) {
                    requestDropdown.classList.add('hidden');
                    if (requestArrow) requestArrow.style.transform = 'rotate(0deg)';
                }
            });
        });
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
