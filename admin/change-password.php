<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif ($currentPassword === $newPassword) {
        $error = 'New password must be different from current password';
    } else {
        // Verify current password
        $hashedCurrentPassword = md5($currentPassword);
        $stmt = $conn->prepare("SELECT id, password FROM user_login WHERE id = ? AND password = ?");
        $stmt->bind_param('is', $userId, $hashedCurrentPassword);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            $error = 'Current password is incorrect';
        } else {
            // Update password
            $hashedNewPassword = md5($newPassword);
            $cols = [];
            $cr = $conn->query("SHOW COLUMNS FROM user_login");
            if ($cr) { while ($r = $cr->fetch_assoc()) $cols[] = $r['Field']; }
            $hasLast = in_array('last_password_change', $cols);
            $sql = $hasLast ? "UPDATE user_login SET password = ?, last_password_change = NOW() WHERE id = ?" : "UPDATE user_login SET password = ? WHERE id = ?";
            $updateStmt = $conn->prepare($sql);
            
            if ($updateStmt) {
                $updateStmt->bind_param('si', $hashedNewPassword, $userId);
                
                if ($updateStmt->execute()) {
                    // Clear default password flag from session
                    unset($_SESSION['is_default_password']);
                    $success = 'Password changed successfully!';
                    $_POST = []; // Clear form
                } else {
                    $error = 'Failed to update password: ' . $updateStmt->error;
                }
                $updateStmt->close();
            } else {
                $error = 'Database error: ' . $conn->error;
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
    <title>Change Password - Admin</title>
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
                <h1 class="text-2xl font-semibold text-slate-800">Change Password</h1>
                <p class="text-sm text-slate-500 mt-1">Update your account password</p>
            </div>
            <a href="index" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Change Password Form -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 max-w-2xl">
            <form method="POST" action="" class="space-y-6" id="changePasswordForm">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Current Password <span class="text-red-500">*</span></label>
                    <input type="password" name="current_password" id="current_password" required 
                           class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">New Password <span class="text-red-500">*</span></label>
                    <input type="password" name="new_password" id="new_password" required minlength="6"
                           class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                    <p class="text-xs text-slate-500 mt-1">Password must be at least 6 characters long</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Confirm New Password <span class="text-red-500">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                           class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <a href="index" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('changePasswordForm');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (newPassword.value !== confirmPassword.value) {
                        e.preventDefault();
                        alert('New passwords do not match');
                        confirmPassword.focus();
                        return false;
                    }
                    
                    if (newPassword.value.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long');
                        newPassword.focus();
                        return false;
                    }
                });
            }

            // Real-time password match validation
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (this.value && this.value !== newPassword.value) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
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
</body>
</html>
