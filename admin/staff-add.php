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
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $sss = trim($_POST['sss'] ?? '');
    $philhealth = trim($_POST['philhealth'] ?? '');
    $pagibig = trim($_POST['pagibig'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    
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
            $stmt = $conn->prepare("INSERT INTO employees (employee_id, full_name, email, phone, position, department, date_hired, status, address, birthdate, gender, sss, philhealth, pagibig, tin, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception('Database prepare error: ' . $conn->error);
            }
            
            $stmt->bind_param("sssssssssssssss", $employeeId, $fullName, $email, $phone, $position, $department, $dateHired, $status, $address, $birthdate, $gender, $sss, $philhealth, $pagibig, $tin);
            
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
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#d97706] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($adminName); ?></div>
                <div class="text-xs text-white/80">Administrator</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- Employees Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="employees-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Employees</span>
                    <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="employees-dropdown" class="hidden space-y-1 mt-1">
                    <a href="staff-add" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
                        Add New Employee
                    </a>
                    <a href="staff" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
                        List of Employee
                    </a>
                </div>
            </div>
            <!-- Leaves Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="leaves-dropdown" class="hidden space-y-1 mt-1">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
                        Allocation of Leave
                    </a>
                    <a href="leaves-summary" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
                        Leave Summary per Employee
                    </a>
                </div>
            </div>
            <!-- Request Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="request-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Request</span>
                    <svg id="request-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="request-dropdown" class="hidden space-y-1 mt-1">
                    <a href="request-leaves" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">Request Leaves</a>
                    <a href="request-document" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">Request Document</a>
                </div>
            </div>
            <a href="activity-log" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Activity Log</span>
            </a>
            <a href="announcement" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>Announcements</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
                <span>Role</span>
                <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen overflow-y-auto p-8">
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
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <form method="POST" action="" class="space-y-6" id="employeeForm" novalidate>
                <!-- Personal Information Section -->
                <div class="border-b border-slate-200 pb-4 mb-6">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" id="full_name" required 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                   minlength="3">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-2 text-slate-500">09</span>
                                <input type="tel" name="phone" id="phone" required 
                                       value="<?php echo htmlspecialchars(preg_replace('/^09/', '', $_POST['phone'] ?? '')); ?>"
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
                                   value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Gender</label>
                            <select name="gender" id="gender" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Address</label>
                            <textarea name="address" id="address" rows="2"
                                      class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
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
                                   value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Department <span class="text-red-500">*</span></label>
                            <select name="department" id="department" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                                <option value="">Select Department</option>
                                <option value="IT Department" <?php echo (isset($_POST['department']) && $_POST['department'] === 'IT Department') ? 'selected' : ''; ?>>IT Department</option>
                                <option value="Human Resources" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Human Resources') ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Sales" <?php echo (isset($_POST['department']) && $_POST['department'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                            </select>
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Date Hired <span class="text-red-500">*</span></label>
                            <input type="date" name="date_hired" id="date_hired" required 
                                   value="<?php echo htmlspecialchars($_POST['date_hired'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <span class="error-message text-red-500 text-xs mt-1 hidden"></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Status <span class="text-red-500">*</span></label>
                            <select name="status" id="status" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                                <option value="Active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
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
                                   value="<?php echo htmlspecialchars($_POST['sss'] ?? ''); ?>"
                                   pattern="[0-9]{2}-[0-9]{7}-[0-9]" placeholder="XX-XXXXXXX-X" maxlength="13"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">10 digits (format: XX-XXXXXXX-X)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">PhilHealth Number</label>
                            <input type="text" name="philhealth" id="philhealth" 
                                   value="<?php echo htmlspecialchars($_POST['philhealth'] ?? ''); ?>"
                                   pattern="[0-9]{2}-[0-9]{9}-[0-9]" placeholder="XX-XXXXXXXXX-X" maxlength="14"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">12 digits (format: XX-XXXXXXXXX-X)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Pag-IBIG (HDMF) Number</label>
                            <input type="text" name="pagibig" id="pagibig" 
                                   value="<?php echo htmlspecialchars($_POST['pagibig'] ?? ''); ?>"
                                   pattern="[0-9]{4}-[0-9]{4}-[0-9]{4}" placeholder="XXXX-XXXX-XXXX" maxlength="14"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">12 digits (format: XXXX-XXXX-XXXX)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">TIN Number</label>
                            <input type="text" name="tin" id="tin" 
                                   value="<?php echo htmlspecialchars($_POST['tin'] ?? ''); ?>"
                                   pattern="[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}" placeholder="XXX-XXX-XXX-XXX" maxlength="15"
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <p class="text-xs text-slate-500 mt-1">12 digits (format: XXX-XXX-XXX-XXX)</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="staff" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium">
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
