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

// Fetch employee data
$employee = null;
$documents = [];
$compensation = null;
$latestAdjustment = null;
$bankDetails = null;
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
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">View Employee</h1>
                <p class="text-sm text-slate-500 mt-1">Employee details and information</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="staff-edit.php?id=<?php echo $employeeId; ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Employee
                </a>
                <a href="staff" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        <!-- Employee Details -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <div class="flex items-start gap-6 mb-6">
                <div class="w-24 h-24 rounded-full overflow-hidden bg-slate-200 flex items-center justify-center flex-shrink-0">
                    <?php 
                    $photo = !empty($employee['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $employee['profile_picture']) ? $employee['profile_picture'] : null;
                    if ($photo): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-3xl font-semibold text-slate-500"><?php echo strtoupper(substr($employee['full_name'] ?? '?', 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-semibold text-slate-800 mb-1"><?php echo htmlspecialchars($employee['full_name'] ?? ''); ?></h2>
                    <p class="text-sm text-slate-500 mb-2"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($employee['status'] ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo htmlspecialchars($employee['status'] ?? 'Active'); ?>
                        </span>
                    </div>
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

        <!-- Compensation Details Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Compensation Details</h3>
                <a href="compensation?employee_id=<?php echo $employeeId; ?>" class="px-4 py-2 bg-[#FA9800] text-white text-sm font-medium rounded-lg hover:bg-[#d97706] transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Add Salary Adjustment</span>
                </a>
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
                            $totalAllowances = ($compensation['allowance_internet'] ?? 0) + 
                                             ($compensation['allowance_meal'] ?? 0) + 
                                             ($compensation['allowance_position'] ?? 0) + 
                                             ($compensation['allowance_transportation'] ?? 0);
                            $monthlyGross = $currentSalary + $totalAllowances;
                            $dailyGross = $monthlyGross / 22; // Assuming 22 working days per month
                            $annualGross = $monthlyGross * 12;
                        ?>
                        <div class="md:col-span-2 border-slate-200">
                            <h4 class="text-md font-semibold text-slate-700 mb-4">Gross Income (Based on New Salary)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Monthly Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($monthlyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">New Salary + Allowances</p>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <p class="text-sm font-medium text-slate-600 mb-1">Daily Gross Income</p>
                                    <p class="text-slate-800 text-xl font-bold">₱<?php echo number_format($dailyGross, 2); ?></p>
                                    <p class="text-xs text-slate-500 mt-1">Based on 22 working days</p>
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
                        <a href="compensation?employee_id=<?php echo $employeeId; ?>" class="px-4 py-2 bg-[#FA9800] text-white text-sm font-medium rounded-lg hover:bg-[#d97706] transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add Salary Adjustment</span>
                        </a>
                        <a href="compensation" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span>View All Adjustments</span>
                        </a>
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

        <!-- Documents Section -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Employee Documents</h3>
                    <p class="text-sm text-slate-500 mt-1">Documents uploaded by employee</p>
                </div>
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
