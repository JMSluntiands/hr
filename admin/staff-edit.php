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
include 'include/activity-logger.php';

$success = '';
$error = '';

// Get employee ID from URL or from POST (when form is submitted)
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && (int)$_POST['id'] > 0) {
    $employeeId = (int)$_POST['id'];
}

if (!$employeeId) {
    header('Location: staff.php');
    exit;
}

// Fetch existing employee data
$employee = null;
$employeeCompensation = [
    'basic_salary_monthly' => 0.00,
    'basic_salary_daily' => 0.00,
    'basic_salary_annually' => 0.00,
    'allowance_internet' => 0.00,
    'allowance_meal' => 0.00,
    'allowance_position' => 0.00,
    'allowance_transportation' => 0.00
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

    $checkCompTable = $conn->query("SHOW TABLES LIKE 'employee_compensation'");
    if ($checkCompTable && $checkCompTable->num_rows > 0) {
        $compStmt = $conn->prepare("SELECT basic_salary_monthly, basic_salary_daily, basic_salary_annually, allowance_internet, allowance_meal, allowance_position, allowance_transportation FROM employee_compensation WHERE employee_id = ? LIMIT 1");
        if ($compStmt) {
            $compStmt->bind_param("i", $employeeId);
            $compStmt->execute();
            $compResult = $compStmt->get_result();
            if ($compRow = $compResult->fetch_assoc()) {
                $employeeCompensation = array_merge($employeeCompensation, $compRow);
            }
            $compStmt->close();
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    // Ensure phone starts with 09 (prefix is shown but not included in input)
    if (!empty($phone) && !preg_match('/^09/', $phone)) {
        $phone = '09' . $phone;
    }
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $dateHired = $_POST['date_hired'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $address = trim($_POST['address'] ?? '');
    $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');
    if (!empty($emergencyContactPhone) && !preg_match('/^09/', $emergencyContactPhone)) {
        $emergencyContactPhone = '09' . $emergencyContactPhone;
    }
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $sss = trim($_POST['sss'] ?? '');
    $philhealth = trim($_POST['philhealth'] ?? '');
    $pagibig = trim($_POST['pagibig'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $basicSalaryMonthlyInput = trim((string)($_POST['basic_salary_monthly'] ?? ''));
    $basicSalaryMonthly = 0.00;
    $allowanceInternetInput = trim((string)($_POST['allowance_internet'] ?? '0'));
    $allowanceMealInput = trim((string)($_POST['allowance_meal'] ?? '0'));
    $allowancePositionInput = trim((string)($_POST['allowance_position'] ?? '0'));
    $allowanceTransportationInput = trim((string)($_POST['allowance_transportation'] ?? '0'));
    $allowanceInternet = 0.00;
    $allowanceMeal = 0.00;
    $allowancePosition = 0.00;
    $allowanceTransportation = 0.00;
    
    // Initialize errors array
    $errors = [];
    
    // Validate government IDs if provided
    if (!empty($sss)) {
        // Remove dashes for validation, SSS should be 10 digits: XX-XXXXXXX-X
        $sssDigits = preg_replace('/[^0-9]/', '', $sss);
        if (strlen($sssDigits) !== 10) {
            $errors[] = 'SSS number must be 10 digits (format: XX-XXXXXXX-X)';
        }
    }
    
    if (!empty($pagibig)) {
        // Remove dashes for validation, Pag-IBIG should be 12 digits: XXXX-XXXX-XXXX
        $pagibigDigits = preg_replace('/[^0-9]/', '', $pagibig);
        if (strlen($pagibigDigits) !== 12) {
            $errors[] = 'Pag-IBIG number must be 12 digits (format: XXXX-XXXX-XXXX)';
        }
    }
    
    if (!empty($philhealth)) {
        // Remove dashes for validation, PhilHealth should be 12 digits: XX-XXXXXXXXX-X
        $philhealthDigits = preg_replace('/[^0-9]/', '', $philhealth);
        if (strlen($philhealthDigits) !== 12) {
            $errors[] = 'PhilHealth number must be 12 digits (format: XX-XXXXXXXXX-X)';
        }
    }
    
    if (!empty($tin)) {
        // Remove dashes for validation, TIN should be 12 digits: XXX-XXX-XXX-XXX
        $tinDigits = preg_replace('/[^0-9]/', '', $tin);
        if (strlen($tinDigits) !== 12) {
            $errors[] = 'TIN number must be 12 digits (format: XXX-XXX-XXX-XXX)';
        }
    }

    if ($basicSalaryMonthlyInput !== '') {
        if (!is_numeric($basicSalaryMonthlyInput)) {
            $errors[] = 'Compensation monthly must be a valid number';
        } else {
            $basicSalaryMonthly = (float)$basicSalaryMonthlyInput;
            if ($basicSalaryMonthly < 0) {
                $errors[] = 'Compensation monthly cannot be negative';
            }
        }
    }

    $allowanceFields = [
        'Internet allowance' => $allowanceInternetInput,
        'Meal allowance' => $allowanceMealInput,
        'Position allowance' => $allowancePositionInput,
        'Transportation allowance' => $allowanceTransportationInput
    ];
    foreach ($allowanceFields as $label => $value) {
        if ($value !== '' && !is_numeric($value)) {
            $errors[] = $label . ' must be a valid number';
        } elseif ((float)$value < 0) {
            $errors[] = $label . ' cannot be negative';
        }
    }
    $allowanceInternet = (float)$allowanceInternetInput;
    $allowanceMeal = (float)$allowanceMealInput;
    $allowancePosition = (float)$allowancePositionInput;
    $allowanceTransportation = (float)$allowanceTransportationInput;
    
    if (empty($fullName)) {
        $errors[] = 'Full Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        // Check if email already exists (excluding current employee)
        $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = 'Email already exists';
        }
        $stmt->close();
    }
    
    if (empty($position)) {
        $errors[] = 'Position is required';
    }
    
    if (empty($department)) {
        $errors[] = 'Department is required';
    }
    
    if (empty($dateHired)) {
        $errors[] = 'Date Hired is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } else {
        // Remove any spaces or dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);
        // If doesn't start with 09, add it
        if (!preg_match('/^09/', $phone)) {
            $phone = '09' . $phone;
        }
        // Validate: must start with 09 and have exactly 11 digits (09 + 9 digits)
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            $errors[] = 'Phone number must start with 09 and have 9 digits after (e.g., 09123456789)';
        }
    }

    // Check if email already exists in user_login (when adding new, or when editing and email was changed)
    $isEdit = !empty($_POST['id']) && (int)$_POST['id'] > 0;
    $currentEmployeeEmail = $employee['email'] ?? '';
    if (empty($errors) && (!$isEdit || $email !== $currentEmployeeEmail)) {
        $stmt = $conn->prepare("SELECT id FROM user_login WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = 'Email already exists in user accounts';
            }
            $stmt->close();
        }
    }
    
    // If no errors, update or insert
    if (empty($errors)) {
        if ($isEdit && $employee) {
            // UPDATE existing employee
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("UPDATE employees SET full_name=?, email=?, phone=?, position=?, department=?, date_hired=?, status=?, address=?, emergency_contact_name=?, emergency_contact_phone=?, birthdate=?, gender=?, sss=?, philhealth=?, pagibig=?, tin=? WHERE id=?");
                if (!$stmt) {
                    throw new Exception('Database prepare error: ' . $conn->error);
                }
                $stmt->bind_param("ssssssssssssssssi", $fullName, $email, $phone, $position, $department, $dateHired, $status, $address, $emergencyContactName, $emergencyContactPhone, $birthdate, $gender, $sss, $philhealth, $pagibig, $tin, $employeeId);
                if (!$stmt->execute()) {
                    throw new Exception('Error updating employee: ' . $stmt->error);
                }
                $stmt->close();
                // Update user_login email if it changed (so login still works)
                if ($email !== $currentEmployeeEmail) {
                    $loginStmt = $conn->prepare("UPDATE user_login SET email = ? WHERE email = ?");
                    if ($loginStmt) {
                        $loginStmt->bind_param("ss", $email, $currentEmployeeEmail);
                        $loginStmt->execute();
                        $loginStmt->close();
                    }
                }

                // Ensure compensation table exists
                $createCompTableSql = "CREATE TABLE IF NOT EXISTS `employee_compensation` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                if (!$conn->query($createCompTableSql)) {
                    throw new Exception('Error preparing compensation table: ' . $conn->error);
                }

                // Ensure salary adjustments table exists
                $createAdjustmentTableSql = "CREATE TABLE IF NOT EXISTS `employee_salary_adjustments` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                if (!$conn->query($createAdjustmentTableSql)) {
                    throw new Exception('Error preparing salary adjustments table: ' . $conn->error);
                }

                $previousSalary = (float)($employeeCompensation['basic_salary_monthly'] ?? 0);
                $basicSalaryDaily = round($basicSalaryMonthly / 22, 2);
                $basicSalaryAnnual = round($basicSalaryMonthly * 12, 2);
                $compEffectiveDate = !empty($dateHired) ? $dateHired : date('Y-m-d');

                // Upsert compensation row
                $checkCompStmt = $conn->prepare("SELECT id FROM employee_compensation WHERE employee_id = ? LIMIT 1");
                if (!$checkCompStmt) {
                    throw new Exception('Error checking compensation record: ' . $conn->error);
                }
                $checkCompStmt->bind_param("i", $employeeId);
                $checkCompStmt->execute();
                $compExists = $checkCompStmt->get_result()->num_rows > 0;
                $checkCompStmt->close();

                if ($compExists) {
                    $updateCompStmt = $conn->prepare("UPDATE employee_compensation SET basic_salary_monthly = ?, basic_salary_daily = ?, basic_salary_annually = ?, effective_date = ?, allowance_internet = ?, allowance_meal = ?, allowance_position = ?, allowance_transportation = ?, updated_at = NOW() WHERE employee_id = ?");
                    if (!$updateCompStmt) {
                        throw new Exception('Error updating compensation details: ' . $conn->error);
                    }
                    $updateCompStmt->bind_param("dddsddddi", $basicSalaryMonthly, $basicSalaryDaily, $basicSalaryAnnual, $compEffectiveDate, $allowanceInternet, $allowanceMeal, $allowancePosition, $allowanceTransportation, $employeeId);
                    if (!$updateCompStmt->execute()) {
                        throw new Exception('Error updating compensation details: ' . $updateCompStmt->error);
                    }
                    $updateCompStmt->close();
                } else {
                    $insertCompStmt = $conn->prepare("INSERT INTO employee_compensation (employee_id, basic_salary_monthly, basic_salary_daily, basic_salary_annually, employment_type, effective_date, allowance_internet, allowance_meal, allowance_position, allowance_transportation) VALUES (?, ?, ?, ?, 'Regular', ?, ?, ?, ?, ?)");
                    if (!$insertCompStmt) {
                        throw new Exception('Error creating compensation details: ' . $conn->error);
                    }
                    $insertCompStmt->bind_param("idddsdddd", $employeeId, $basicSalaryMonthly, $basicSalaryDaily, $basicSalaryAnnual, $compEffectiveDate, $allowanceInternet, $allowanceMeal, $allowancePosition, $allowanceTransportation);
                    if (!$insertCompStmt->execute()) {
                        throw new Exception('Error creating compensation details: ' . $insertCompStmt->error);
                    }
                    $insertCompStmt->close();
                }

                // Add adjustment log when monthly salary changes
                if (abs($basicSalaryMonthly - $previousSalary) > 0.009) {
                    $adjustmentStmt = $conn->prepare("INSERT INTO employee_salary_adjustments (employee_id, previous_salary, new_salary, reason, approved_by, date_approved) VALUES (?, ?, ?, 'Adjustment', ?, ?)");
                    if (!$adjustmentStmt) {
                        throw new Exception('Error creating salary adjustment log: ' . $conn->error);
                    }
                    $approvedBy = $adminName;
                    $dateApproved = date('Y-m-d');
                    $adjustmentStmt->bind_param("iddss", $employeeId, $previousSalary, $basicSalaryMonthly, $approvedBy, $dateApproved);
                    if (!$adjustmentStmt->execute()) {
                        throw new Exception('Error creating salary adjustment log: ' . $adjustmentStmt->error);
                    }
                    $adjustmentStmt->close();
                }

                $conn->commit();
                logActivity($conn, 'Edit Employee', 'Employee', $employeeId, "Updated employee: $fullName");
                $success = 'Employee updated successfully.';
                $employee = array_merge($employee, [
                    'full_name' => $fullName, 'email' => $email, 'phone' => $phone, 'position' => $position,
                    'department' => $department, 'date_hired' => $dateHired, 'status' => $status, 'address' => $address,
                    'emergency_contact_name' => $emergencyContactName, 'emergency_contact_phone' => $emergencyContactPhone,
                    'birthdate' => $birthdate, 'gender' => $gender, 'sss' => $sss, 'philhealth' => $philhealth, 'pagibig' => $pagibig, 'tin' => $tin
                ]);
                $employeeCompensation = [
                    'basic_salary_monthly' => $basicSalaryMonthly,
                    'basic_salary_daily' => $basicSalaryDaily,
                    'basic_salary_annually' => $basicSalaryAnnual,
                    'allowance_internet' => $allowanceInternet,
                    'allowance_meal' => $allowanceMeal,
                    'allowance_position' => $allowancePosition,
                    'allowance_transportation' => $allowanceTransportation
                ];
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        } else {
            // INSERT new employee (only if this page is used for add, e.g. from a different entry point)
            $newEmployeeIdStr = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
            $stmt->bind_param("s", $newEmployeeIdStr);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $newEmployeeIdStr = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
            $stmt->close();
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO employees (employee_id, full_name, email, phone, position, department, date_hired, status, address, emergency_contact_name, emergency_contact_phone, birthdate, gender, sss, philhealth, pagibig, tin, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                if (!$stmt) {
                    throw new Exception('Database prepare error: ' . $conn->error);
                }
                $stmt->bind_param("sssssssssssssssss", $newEmployeeIdStr, $fullName, $email, $phone, $position, $department, $dateHired, $status, $address, $emergencyContactName, $emergencyContactPhone, $birthdate, $gender, $sss, $philhealth, $pagibig, $tin);
                if (!$stmt->execute()) {
                    throw new Exception('Error adding employee: ' . $stmt->error);
                }
                $newEmployeeId = $conn->insert_id;
                $stmt->close();
                $defaultPassword = md5('PASSWORD');
                $employeeRole = 'employee';
                $loginStmt = $conn->prepare("INSERT INTO user_login (email, password, role) VALUES (?, ?, ?)");
                if (!$loginStmt) {
                    throw new Exception('Error preparing user_login insert: ' . $conn->error);
                }
                $loginStmt->bind_param("sss", $email, $defaultPassword, $employeeRole);
                if (!$loginStmt->execute()) {
                    throw new Exception('Error creating user login: ' . $loginStmt->error);
                }
                $loginStmt->close();
                $conn->commit();
                logActivity($conn, 'Add Employee', 'Employee', $newEmployeeId, "Added employee: $fullName (ID: $newEmployeeIdStr)");
                $success = 'Employee added successfully! Employee ID: ' . $newEmployeeIdStr . '. Login account created with default password: PASSWORD';
                $_POST = [];
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Admin</title>
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
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800"><?php echo !empty($employee) ? 'Edit Employee' : 'Add New Employee'; ?></h1>
                <p class="text-sm text-slate-500 mt-1"><?php echo !empty($employee) ? 'Update employee information' : 'Create a new employee account in the system'; ?></p>
            </div>
            <a href="staff" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <form method="POST" action="" class="space-y-6" id="employeeForm" novalidate>
                <?php if (!empty($employee['id'])): ?>
                <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">
                <?php endif; ?>
                <!-- Personal Information Section -->
                <div class="border-b border-slate-200 pb-4 mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" id="full_name" required 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ($employee['full_name'] ?? '')); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                   minlength="3">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ($employee['email'] ?? '')); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-2 text-slate-500">09</span>
                                <input type="tel" name="phone" id="phone" required 
                                       value="<?php echo htmlspecialchars(preg_replace('/^09/', '', $_POST['phone'] ?? ($employee['phone'] ?? ''))); ?>"
                                       pattern="[0-9]{9}" placeholder="123456789" maxlength="9"
                                       class="w-full pl-12 pr-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length > 9) this.value = this.value.slice(0, 9);">
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Format: 09 + 9 digits (e.g., 09123456789)</p>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Birthdate</label>
                            <input type="date" name="birthdate" id="birthdate"
                                   value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ($employee['birthdate'] ?? '')); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Gender</label>
                            <select name="gender" id="gender" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ((isset($_POST['gender']) && $_POST['gender'] === 'Male') || (!isset($_POST['gender']) && ($employee['gender'] ?? '') === 'Male')) ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ((isset($_POST['gender']) && $_POST['gender'] === 'Female') || (!isset($_POST['gender']) && ($employee['gender'] ?? '') === 'Female')) ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Address</label>
                            <textarea name="address" id="address" rows="2"
                                      class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"><?php echo htmlspecialchars($_POST['address'] ?? ($employee['address'] ?? '')); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Emergency Contact Person</label>
                            <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                   value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ($employee['emergency_contact_name'] ?? '')); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]" placeholder="Full name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Emergency Contact Number</label>
                            <div class="relative">
                                <span class="absolute left-4 top-2 text-slate-500 text-sm">09</span>
                                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                       value="<?php echo htmlspecialchars(preg_replace('/^09/', '', $_POST['emergency_contact_phone'] ?? ($employee['emergency_contact_phone'] ?? ''))); ?>"
                                       pattern="[0-9]{9}" placeholder="123456789" maxlength="9"
                                       class="w-full pl-12 pr-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length > 9) this.value = this.value.slice(0, 9);">
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Format: 09 + 9 digits (optional)</p>
                        </div>
                    </div>
                </div>

                <!-- Employment Information Section -->
                <div class="border-b border-slate-200 pb-4 mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Employment Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Position <span class="text-red-500">*</span></label>
                            <input type="text" name="position" id="position" required 
                                   value="<?php echo htmlspecialchars($_POST['position'] ?? ($employee['position'] ?? '')); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Department <span class="text-red-500">*</span></label>
                            <select name="department" id="department" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                                <option value="">Select Department</option>
                                <?php
                                $selDept = isset($_POST['department']) ? $_POST['department'] : ($employee['department'] ?? '');
                                $depts = ['IT Department', 'Human Resources', 'Finance', 'Marketing', 'Sales'];
                                foreach ($depts as $d) {
                                    echo '<option value="' . htmlspecialchars($d) . '"' . ($selDept === $d ? ' selected' : '') . '>' . htmlspecialchars($d) . '</option>';
                                }
                                ?>
                            </select>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Date Hired <span class="text-red-500">*</span></label>
                            <input type="date" name="date_hired" id="date_hired" required 
                                   value="<?php echo htmlspecialchars($_POST['date_hired'] ?? ($employee['date_hired'] ?? '')); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Status <span class="text-red-500">*</span></label>
                            <select name="status" id="status" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                                <?php $selStatus = isset($_POST['status']) ? $_POST['status'] : ($employee['status'] ?? 'Active'); ?>
                                <option value="Active" <?php echo ($selStatus === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($selStatus === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Government Information Section -->
                <div class="pb-4 mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Government Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">SSS Number</label>
                            <input type="text" name="sss" id="sss" 
                                   value="<?php echo htmlspecialchars($_POST['sss'] ?? ($employee['sss'] ?? '')); ?>"
                                   pattern="[0-9]{2}-[0-9]{7}-[0-9]" placeholder="XX-XXXXXXX-X" maxlength="13"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">10 digits (format: XX-XXXXXXX-X)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">PhilHealth Number</label>
                            <input type="text" name="philhealth" id="philhealth" 
                                   value="<?php echo htmlspecialchars($_POST['philhealth'] ?? ($employee['philhealth'] ?? '')); ?>"
                                   pattern="[0-9]{2}-[0-9]{9}-[0-9]" placeholder="XX-XXXXXXXXX-X" maxlength="14"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">12 digits (format: XX-XXXXXXXXX-X)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Pag-IBIG (HDMF) Number</label>
                            <input type="text" name="pagibig" id="pagibig" 
                                   value="<?php echo htmlspecialchars($_POST['pagibig'] ?? ($employee['pagibig'] ?? '')); ?>"
                                   pattern="[0-9]{4}-[0-9]{4}-[0-9]{4}" placeholder="XXXX-XXXX-XXXX" maxlength="14"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">12 digits (format: XXXX-XXXX-XXXX)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">TIN Number</label>
                            <input type="text" name="tin" id="tin" 
                                   value="<?php echo htmlspecialchars($_POST['tin'] ?? ($employee['tin'] ?? '')); ?>"
                                   pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}" placeholder="XXX-XXX-XXX-XXX" maxlength="15"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">12 digits (format: XXX-XXX-XXX-XXX)</p>
                        </div>
                    </div>
                </div>

                <!-- Compensation Information Section -->
                <div class="pb-4 mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Compensation Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Compensation Monthly</label>
                            <input type="number" name="basic_salary_monthly" id="basic_salary_monthly"
                                   value="<?php echo htmlspecialchars($_POST['basic_salary_monthly'] ?? ($employeeCompensation['basic_salary_monthly'] ?? 0)); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Allowance - Internet</label>
                            <input type="number" name="allowance_internet" id="allowance_internet"
                                   value="<?php echo htmlspecialchars($_POST['allowance_internet'] ?? ($employeeCompensation['allowance_internet'] ?? 0)); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Allowance - Meal</label>
                            <input type="number" name="allowance_meal" id="allowance_meal"
                                   value="<?php echo htmlspecialchars($_POST['allowance_meal'] ?? ($employeeCompensation['allowance_meal'] ?? 0)); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Allowance - Position</label>
                            <input type="number" name="allowance_position" id="allowance_position"
                                   value="<?php echo htmlspecialchars($_POST['allowance_position'] ?? ($employeeCompensation['allowance_position'] ?? 0)); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Allowance - Transportation</label>
                            <input type="number" name="allowance_transportation" id="allowance_transportation"
                                   value="<?php echo htmlspecialchars($_POST['allowance_transportation'] ?? ($employeeCompensation['allowance_transportation'] ?? 0)); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Daily Gross (Auto)</label>
                            <input type="text" id="basic_salary_daily_preview" readonly
                                   value="<?php echo number_format((float)($employeeCompensation['basic_salary_daily'] ?? 0), 2, '.', ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-700 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Annual Gross (Auto)</label>
                            <input type="text" id="basic_salary_annual_preview" readonly
                                   value="<?php echo number_format((float)($employeeCompensation['basic_salary_annually'] ?? 0), 2, '.', ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-700 cursor-not-allowed">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">Auto-compute formula: Daily Gross = Monthly / 22, Annual Gross = Monthly x 12. Allowances are saved separately. Kapag nagbago ang monthly, automatic maglalagay ng salary adjustment record.</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="staff" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium">
                        Update Employee
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Form validation and phone number handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('employeeForm');
            const phoneInput = document.getElementById('phone');
            const monthlyCompInput = document.getElementById('basic_salary_monthly');
            const dailyGrossPreview = document.getElementById('basic_salary_daily_preview');
            const annualGrossPreview = document.getElementById('basic_salary_annual_preview');

            function updateCompensationPreview() {
                if (!monthlyCompInput || !dailyGrossPreview || !annualGrossPreview) return;
                const monthly = parseFloat(monthlyCompInput.value);
                const safeMonthly = Number.isFinite(monthly) && monthly >= 0 ? monthly : 0;
                dailyGrossPreview.value = (safeMonthly / 22).toFixed(2);
                annualGrossPreview.value = (safeMonthly * 12).toFixed(2);
            }

            if (monthlyCompInput) {
                monthlyCompInput.addEventListener('input', updateCompensationPreview);
                updateCompensationPreview();
            }
            
            // Ensure phone number is properly formatted on submit
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Remove any non-numeric characters
                    phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '');
                    
                    // Validate phone has exactly 9 digits (09 prefix is added in PHP)
                    if (phoneInput.value.length !== 9) {
                        e.preventDefault();
                        alert('Phone number must be exactly 9 digits after 09 (e.g., 123456789)');
                        phoneInput.focus();
                        return false;
                    }
                    
                    // Create hidden input with full phone number (09 + 9 digits)
                    const fullPhoneInput = document.createElement('input');
                    fullPhoneInput.type = 'hidden';
                    fullPhoneInput.name = 'phone';
                    fullPhoneInput.value = '09' + phoneInput.value;
                    form.appendChild(fullPhoneInput);
                    
                    // Temporarily disable original input to avoid duplication
                    phoneInput.disabled = true;
                });
            }
            
            // Phone input validation - only numbers, max 9 digits
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    // Remove any non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                    // Limit to 9 digits
                    if (this.value.length > 9) {
                        this.value = this.value.slice(0, 9);
                    }
                });
                
                // Prevent pasting invalid data
                phoneInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numbers = paste.replace(/[^0-9]/g, '').slice(0, 9);
                    this.value = numbers;
                });
            }
            
            // SSS Number validation and formatting (10 digits: XX-XXXXXXX-X)
            const sssInput = document.getElementById('sss');
            if (sssInput) {
                sssInput.addEventListener('input', function(e) {
                    let value = this.value.replace(/[^0-9]/g, ''); // Remove non-digits
                    if (value.length > 10) value = value.slice(0, 10); // Limit to 10 digits
                    
                    // Format: XX-XXXXXXX-X
                    if (value.length > 0) {
                        if (value.length <= 2) {
                            this.value = value;
                        } else if (value.length <= 9) {
                            this.value = value.slice(0, 2) + '-' + value.slice(2);
                        } else {
                            this.value = value.slice(0, 2) + '-' + value.slice(2, 9) + '-' + value.slice(9);
                        }
                    } else {
                        this.value = '';
                    }
                });
            }
            
            // PhilHealth Number validation and formatting (12 digits: XX-XXXXXXXXX-X)
            const philhealthInput = document.getElementById('philhealth');
            if (philhealthInput) {
                philhealthInput.addEventListener('input', function(e) {
                    let value = this.value.replace(/[^0-9]/g, ''); // Remove non-digits
                    if (value.length > 12) value = value.slice(0, 12); // Limit to 12 digits
                    
                    // Format: XX-XXXXXXXXX-X
                    if (value.length > 0) {
                        if (value.length <= 2) {
                            this.value = value;
                        } else if (value.length <= 11) {
                            this.value = value.slice(0, 2) + '-' + value.slice(2);
                        } else {
                            this.value = value.slice(0, 2) + '-' + value.slice(2, 11) + '-' + value.slice(11);
                        }
                    } else {
                        this.value = '';
                    }
                });
            }
            
            // Pag-IBIG Number validation and formatting (12 digits: XXXX-XXXX-XXXX)
            const pagibigInput = document.getElementById('pagibig');
            if (pagibigInput) {
                pagibigInput.addEventListener('input', function(e) {
                    let value = this.value.replace(/[^0-9]/g, ''); // Remove non-digits
                    if (value.length > 12) value = value.slice(0, 12); // Limit to 12 digits
                    
                    // Format: XXXX-XXXX-XXXX
                    if (value.length > 0) {
                        if (value.length <= 4) {
                            this.value = value;
                        } else if (value.length <= 8) {
                            this.value = value.slice(0, 4) + '-' + value.slice(4);
                        } else {
                            this.value = value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8);
                        }
                    } else {
                        this.value = '';
                    }
                });
            }
            
            // TIN Number validation and formatting (12 digits: XXX-XXX-XXX-XXX)
            const tinInput = document.getElementById('tin');
            if (tinInput) {
                tinInput.addEventListener('input', function(e) {
                    let value = this.value.replace(/[^0-9]/g, ''); // Remove non-digits
                    if (value.length > 12) value = value.slice(0, 12); // Limit to 12 digits
                    
                    // Format: XXX-XXX-XXX-XXX
                    if (value.length > 0) {
                        if (value.length <= 3) {
                            this.value = value;
                        } else if (value.length <= 6) {
                            this.value = value.slice(0, 3) + '-' + value.slice(3);
                        } else if (value.length <= 9) {
                            this.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6);
                        } else {
                            this.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 9) + '-' + value.slice(9);
                        }
                    } else {
                        this.value = '';
                    }
                });
            }
            
            // Dropdown functionality
            const employeesBtn = document.getElementById('employees-dropdown-btn');
            const employeesDropdown = document.getElementById('employees-dropdown');
            const employeesArrow = document.getElementById('employees-arrow');
            const leavesBtn = document.getElementById('leaves-dropdown-btn');
            const leavesDropdown = document.getElementById('leaves-dropdown');
            const leavesArrow = document.getElementById('leaves-arrow');

            function toggleEmployeesDropdown() {
                const isHidden = employeesDropdown.classList.contains('hidden');
                if (!leavesDropdown.classList.contains('hidden')) {
                    leavesDropdown.classList.add('hidden');
                    leavesArrow.style.transform = 'rotate(0deg)';
                }
                employeesDropdown.classList.toggle('hidden');
                if (isHidden) {
                    employeesArrow.style.transform = 'rotate(180deg)';
                } else {
                    employeesArrow.style.transform = 'rotate(0deg)';
                }
            }

            function toggleLeavesDropdown() {
                const isHidden = leavesDropdown.classList.contains('hidden');
                if (!employeesDropdown.classList.contains('hidden')) {
                    employeesDropdown.classList.add('hidden');
                    employeesArrow.style.transform = 'rotate(0deg)';
                }
                leavesDropdown.classList.toggle('hidden');
                if (isHidden) {
                    leavesArrow.style.transform = 'rotate(180deg)';
                } else {
                    leavesArrow.style.transform = 'rotate(0deg)';
                }
            }

            if (employeesBtn) {
                employeesBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleEmployeesDropdown();
                });
            }

            if (leavesBtn) {
                leavesBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleLeavesDropdown();
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (employeesBtn && employeesDropdown && 
                    !employeesBtn.contains(e.target) && !employeesDropdown.contains(e.target)) {
                    employeesDropdown.classList.add('hidden');
                    employeesArrow.style.transform = 'rotate(0deg)';
                }
                if (leavesBtn && leavesDropdown && 
                    !leavesBtn.contains(e.target) && !leavesDropdown.contains(e.target)) {
                    leavesDropdown.classList.add('hidden');
                    leavesArrow.style.transform = 'rotate(0deg)';
                }
            });
        });
    </script>
</body>
</html>
