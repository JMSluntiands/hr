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

// Load department options from departments master table
$departmentOptions = [];
// Load employment type options from employment_types master table
$employmentTypeOptions = [];
if ($conn) {
    $checkDept = $conn->query("SHOW TABLES LIKE 'departments'");
    if ($checkDept && $checkDept->num_rows > 0) {
        $deptRes = $conn->query("SELECT name FROM departments ORDER BY name");
        if ($deptRes && $deptRes->num_rows > 0) {
            while ($d = $deptRes->fetch_assoc()) {
                if (!empty($d['name'])) {
                    $departmentOptions[] = $d['name'];
                }
            }
        }
    }

    $checkTypes = $conn->query("SHOW TABLES LIKE 'employment_types'");
    if ($checkTypes && $checkTypes->num_rows > 0) {
        $typeRes = $conn->query("SELECT id, name FROM employment_types ORDER BY name");
        if ($typeRes && $typeRes->num_rows > 0) {
            while ($t = $typeRes->fetch_assoc()) {
                if (!empty($t['name'])) {
                    $employmentTypeOptions[] = $t;
                }
            }
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
    $secondaryWorkplace = trim($_POST['secondary_workplace'] ?? '');
    $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');
    $emergencyContactRelationship = trim($_POST['emergency_contact_relationship'] ?? '');
    if (!empty($emergencyContactPhone) && !preg_match('/^09/', $emergencyContactPhone)) {
        $emergencyContactPhone = '09' . $emergencyContactPhone;
    }
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $sss = trim($_POST['sss'] ?? '');
    $philhealth = trim($_POST['philhealth'] ?? '');
    $pagibig = trim($_POST['pagibig'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $nbiClearance = trim($_POST['nbi_clearance'] ?? '');
    $policeClearance = trim($_POST['police_clearance'] ?? '');
    $basicSalaryDailyInput = trim((string)($_POST['basic_salary_daily'] ?? ''));
    $basicSalaryDaily = 0.00;
    $basicSalaryMonthly = 0.00;
    $basicSalaryAnnual = 0.00;
    $allowanceInternetInput = trim((string)($_POST['allowance_internet'] ?? '0'));
    $allowanceMealInput = trim((string)($_POST['allowance_meal'] ?? '0'));
    $allowancePositionInput = trim((string)($_POST['allowance_position'] ?? '0'));
    $allowanceTransportationInput = trim((string)($_POST['allowance_transportation'] ?? '0'));
    $employmentTypeId = isset($_POST['employment_type']) ? (int)$_POST['employment_type'] : 0;
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

    if ($basicSalaryDailyInput !== '') {
        if (!is_numeric($basicSalaryDailyInput)) {
            $errors[] = 'Daily compensation must be a valid number';
        } else {
            $basicSalaryDaily = (float)$basicSalaryDailyInput;
            if ($basicSalaryDaily < 0) {
                $errors[] = 'Daily compensation cannot be negative';
            } else {
                $basicSalaryMonthly = round($basicSalaryDaily * 26, 2);
                $basicSalaryAnnual = round($basicSalaryMonthly * 12, 2);
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
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
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

    if (!$employmentTypeId) {
        $errors[] = 'Employment type is required';
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

    // Check if email already exists in user_login
    if (empty($errors)) {
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
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Generate employee ID (EMP-YYYYMMDD-XXX)
        $employeeId = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Check if employee ID already exists (very unlikely but check anyway)
        $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
        $stmt->bind_param("s", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $employeeId = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
        $stmt->close();
        
        // Start transaction for data consistency
        $conn->begin_transaction();
        
        try {
            // Insert into employees table
            $stmt = $conn->prepare("INSERT INTO employees (employee_id, full_name, email, phone, position, department, employment_type_id, date_hired, status, address, secondary_workplace, emergency_contact_name, emergency_contact_phone, emergency_contact_relationship, birthdate, gender, sss, philhealth, pagibig, tin, nbi_clearance, police_clearance, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            $bindTypes = 'ssssssi' . str_repeat('s', 15); // 22 params: 6s + 1i + 15s
            $stmt->bind_param($bindTypes, $employeeId, $fullName, $email, $phone, $position, $department, $employmentTypeId, $dateHired, $status, $address, $secondaryWorkplace, $emergencyContactName, $emergencyContactPhone, $emergencyContactRelationship, $birthdate, $gender, $sss, $philhealth, $pagibig, $tin, $nbiClearance, $policeClearance);
            
            if (!$stmt->execute()) {
                throw new Exception('Error adding employee: ' . $stmt->error);
            }
            $newEmployeeId = $conn->insert_id;
            $stmt->close();
            
            // Also create a login account for the employee
            $defaultPassword = md5('PASSWORD'); // Default password
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

            // Ensure compensation table exists and save initial compensation values
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

            // monthly and annual already computed from daily above
            $compEffectiveDate = !empty($dateHired) ? $dateHired : date('Y-m-d');

            // Map master employment type name to compensation enum
            $employmentType = 'Regular';
            if (!empty($employmentTypeOptions)) {
                foreach ($employmentTypeOptions as $opt) {
                    if ((int)$opt['id'] === $employmentTypeId) {
                        $nameLower = strtolower($opt['name']);
                        if (strpos($nameLower, 'contract') !== false) {
                            $employmentType = 'Contractual';
                        } elseif (strpos($nameLower, 'probation') !== false) {
                            $employmentType = 'Probationary';
                        } elseif (strpos($nameLower, 'part') !== false) {
                            $employmentType = 'Part-time';
                        } else {
                            $employmentType = 'Regular';
                        }
                        break;
                    }
                }
            }

            $compStmt = $conn->prepare("INSERT INTO employee_compensation (employee_id, basic_salary_monthly, basic_salary_daily, basic_salary_annually, employment_type, effective_date, allowance_internet, allowance_meal, allowance_position, allowance_transportation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$compStmt) {
                throw new Exception('Error preparing compensation insert: ' . $conn->error);
            }

            $compStmt->bind_param("idddssdddd", $newEmployeeId, $basicSalaryMonthly, $basicSalaryDaily, $basicSalaryAnnual, $employmentType, $compEffectiveDate, $allowanceInternet, $allowanceMeal, $allowancePosition, $allowanceTransportation);
            if (!$compStmt->execute()) {
                throw new Exception('Error saving compensation details: ' . $compStmt->error);
            }
            $compStmt->close();
            
            // Commit transaction if everything succeeds
            $conn->commit();
            
            // Log activity
            logActivity($conn, 'Add Employee', 'Employee', $newEmployeeId, "Added employee: $fullName (ID: $employeeId)");
            
            $success = 'Employee added successfully! Employee ID: ' . $employeeId . '. Login account created with default password: PASSWORD';
            // Clear form data after successful submission
            $_POST = [];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Next Employee ID for display (auto-filled on form)
$nextEmployeeId = '';
if ($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'employees'");
    if ($check && $check->num_rows > 0) {
        $nextEmployeeId = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
        $stmt->bind_param('s', $nextEmployeeId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $nextEmployeeId = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee - Admin</title>
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
                <h1 class="text-2xl font-semibold text-slate-800">Add New Employee</h1>
                <p class="text-sm text-slate-500 mt-1">Create a new employee account in the system</p>
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
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <form method="POST" action="" class="space-y-0" id="employeeForm" novalidate>
                <!-- Personal Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-gradient-to-b from-slate-50/80 to-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Personal Information</h2>
                            <p class="text-sm text-slate-500">Basic details and contact information</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Employee ID</label>
                            <input type="text" value="<?php echo htmlspecialchars($nextEmployeeId); ?>" readonly
                                   class="w-full max-w-xs px-4 py-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 font-mono text-sm cursor-not-allowed">
                            <p class="text-xs text-slate-500 mt-1.5">Auto-generated when you submit.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Full Name <span class="text-amber-600">*</span></label>
                            <input type="text" name="full_name" id="full_name" required 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                   minlength="3" placeholder="Juan Dela Cruz">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-amber-600">*</span></label>
                            <input type="email" name="email" id="email" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                   placeholder="name@company.com">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number <span class="text-amber-600">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">09</span>
                                <input type="tel" name="phone" id="phone" required 
                                       value="<?php echo htmlspecialchars(preg_replace('/^09/', '', $_POST['phone'] ?? '')); ?>"
                                       pattern="[0-9]{9}" placeholder="123456789" maxlength="9"
                                       class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length > 9) this.value = this.value.slice(0, 9);">
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5">09 + 9 digits</p>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Birthdate</label>
                            <input type="date" name="birthdate" id="birthdate"
                                   value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Gender</label>
                            <select name="gender" id="gender" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <!-- Workplaces -->
                        <div class="md:col-span-3 p-4 rounded-lg bg-slate-50 border border-slate-100">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">Work Locations</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Primary Workplace</label>
                                    <textarea name="address" id="address" rows="2"
                                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow resize-none"
                                              placeholder="Primary work location / address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Secondary Workplace</label>
                                    <textarea name="secondary_workplace" id="secondary_workplace" rows="2"
                                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow resize-none"
                                              placeholder="Optional second work location"><?php echo htmlspecialchars($_POST['secondary_workplace'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- Emergency Contact -->
                        <div class="md:col-span-3 p-4 rounded-lg bg-slate-50 border border-slate-100">
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3">Emergency Contact</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Person</label>
                                    <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                           value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>"
                                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                           placeholder="Full name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Relationship</label>
                                    <select name="emergency_contact_relationship" id="emergency_contact_relationship" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                        <option value="">Select</option>
                                        <option value="Spouse" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Spouse') ? 'selected' : ''; ?>>Spouse</option>
                                        <option value="Parent" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Parent') ? 'selected' : ''; ?>>Parent</option>
                                        <option value="Sibling" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Sibling') ? 'selected' : ''; ?>>Sibling</option>
                                        <option value="Child" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Child') ? 'selected' : ''; ?>>Child</option>
                                        <option value="Friend" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Friend') ? 'selected' : ''; ?>>Friend</option>
                                        <option value="Other" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contact Number</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">09</span>
                                        <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                                               value="<?php echo htmlspecialchars(preg_replace('/^09/', '', $_POST['emergency_contact_phone'] ?? '')); ?>"
                                               pattern="[0-9]{9}" placeholder="123456789" maxlength="9"
                                               class="w-full pl-11 pr-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow"
                                               oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length > 9) this.value = this.value.slice(0, 9);">
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1.5">09 + 9 digits (optional)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Employment Information</h2>
                            <p class="text-sm text-slate-500">Position, department, and start date</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Position <span class="text-amber-600">*</span></label>
                            <input type="text" name="position" id="position" required 
                                   value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Department <span class="text-amber-600">*</span></label>
                            <select name="department" id="department" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="">Select Department</option>
                                <?php
                                $selectedDept = $_POST['department'] ?? '';
                                foreach ($departmentOptions as $deptName):
                                ?>
                                    <option value="<?php echo htmlspecialchars($deptName); ?>" <?php echo ($selectedDept === $deptName) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($deptName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Employment Type <span class="text-amber-600">*</span></label>
                            <select name="employment_type" id="employment_type" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="">Select Employment Type</option>
                                <?php
                                $selectedTypeId = isset($_POST['employment_type']) ? (int)$_POST['employment_type'] : 0;
                                foreach ($employmentTypeOptions as $type):
                                ?>
                                    <option value="<?php echo (int)$type['id']; ?>" <?php echo ($selectedTypeId === (int)$type['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Date Hired <span class="text-amber-600">*</span></label>
                            <input type="date" name="date_hired" id="date_hired" required 
                                   value="<?php echo htmlspecialchars($_POST['date_hired'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Status <span class="text-amber-600">*</span></label>
                            <select name="status" id="status" required class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow bg-white">
                                <option value="Active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Government Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Government Information</h2>
                            <p class="text-sm text-slate-500">SSS, PhilHealth, Pag-IBIG, TIN, and clearances</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">SSS Number</label>
                            <input type="text" name="sss" id="sss" 
                                   value="<?php echo htmlspecialchars($_POST['sss'] ?? ''); ?>"
                                   pattern="[0-9]{2}-[0-9]{7}-[0-9]" placeholder="XX-XXXXXXX-X" maxlength="13"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XX-XXXXXXX-X</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">PhilHealth Number</label>
                            <input type="text" name="philhealth" id="philhealth" 
                                   value="<?php echo htmlspecialchars($_POST['philhealth'] ?? ''); ?>"
                                   pattern="[0-9]{2}-[0-9]{9}-[0-9]" placeholder="XX-XXXXXXXXX-X" maxlength="14"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XX-XXXXXXXXX-X</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Pag-IBIG (HDMF) Number</label>
                            <input type="text" name="pagibig" id="pagibig" 
                                   value="<?php echo htmlspecialchars($_POST['pagibig'] ?? ''); ?>"
                                   pattern="[0-9]{4}-[0-9]{4}-[0-9]{4}" placeholder="XXXX-XXXX-XXXX" maxlength="14"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XXXX-XXXX-XXXX</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">TIN Number</label>
                            <input type="text" name="tin" id="tin" 
                                   value="<?php echo htmlspecialchars($_POST['tin'] ?? ''); ?>"
                                   pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}" placeholder="XXX-XXX-XXX-XXX" maxlength="15"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                            <p class="text-xs text-slate-500 mt-1.5">Format: XXX-XXX-XXX-XXX</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">NBI Clearance</label>
                            <input type="text" name="nbi_clearance" id="nbi_clearance" 
                                   value="<?php echo htmlspecialchars($_POST['nbi_clearance'] ?? ''); ?>"
                                   placeholder="Clearance number or reference"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Police Clearance</label>
                            <input type="text" name="police_clearance" id="police_clearance" 
                                   value="<?php echo htmlspecialchars($_POST['police_clearance'] ?? ''); ?>"
                                   placeholder="Clearance number or reference"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                    </div>
                </div>

                <!-- Compensation Information Section -->
                <div class="p-6 md:p-8 border-b border-slate-200 bg-gradient-to-b from-slate-50/50 to-white">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Compensation Information</h2>
                            <p class="text-sm text-slate-500">Daily rate, allowances, and gross figures</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Daily Compensation</label>
                            <input type="number" name="basic_salary_daily" id="basic_salary_daily"
                                   value="<?php echo htmlspecialchars($_POST['basic_salary_daily'] ?? '0'); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Internet</label>
                            <input type="number" name="allowance_internet" id="allowance_internet"
                                   value="<?php echo htmlspecialchars($_POST['allowance_internet'] ?? '0'); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Meal</label>
                            <input type="number" name="allowance_meal" id="allowance_meal"
                                   value="<?php echo htmlspecialchars($_POST['allowance_meal'] ?? '0'); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Position</label>
                            <input type="number" name="allowance_position" id="allowance_position"
                                   value="<?php echo htmlspecialchars($_POST['allowance_position'] ?? '0'); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Allowance - Transportation</label>
                            <input type="number" name="allowance_transportation" id="allowance_transportation"
                                   value="<?php echo htmlspecialchars($_POST['allowance_transportation'] ?? '0'); ?>"
                                   min="0" step="0.01" placeholder="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Monthly Gross (Auto)</label>
                            <input type="text" id="basic_salary_monthly_preview" readonly
                                   value="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 cursor-not-allowed font-medium">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Annual Gross (Auto)</label>
                            <input type="text" id="basic_salary_annual_preview" readonly
                                   value="0.00"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 cursor-not-allowed font-medium">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">Auto-compute: Monthly = Daily × 26, Annual = Monthly × 12. Allowances are saved separately.</p>
                </div>

                <div class="p-6 md:p-8 flex flex-col sm:flex-row justify-end gap-3 bg-slate-50/80 border-t border-slate-200">
                    <a href="staff" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-white hover:border-slate-400 font-medium transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 font-medium shadow-sm hover:shadow transition-all">
                        Add Employee
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
            const dailyCompInput = document.getElementById('basic_salary_daily');
            const monthlyGrossPreview = document.getElementById('basic_salary_monthly_preview');
            const annualGrossPreview = document.getElementById('basic_salary_annual_preview');

            function updateCompensationPreview() {
                if (!dailyCompInput || !monthlyGrossPreview || !annualGrossPreview) return;
                const daily = parseFloat(dailyCompInput.value);
                const safeDaily = Number.isFinite(daily) && daily >= 0 ? daily : 0;
                const monthly = safeDaily * 26;
                const annual = monthly * 12;
                monthlyGrossPreview.value = monthly.toFixed(2);
                annualGrossPreview.value = annual.toFixed(2);
            }

            if (dailyCompInput) {
                dailyCompInput.addEventListener('input', updateCompensationPreview);
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
