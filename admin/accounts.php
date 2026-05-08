<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$accounts = [];
$msg = '';
$generatedPassword = '';
$generatedEmail = '';
$generatedMode = '';
$hasLastChange = false;
$userLoginIdColumn = 'id';

if (!function_exists('tableExists')) {
    function tableExists($conn, string $tableName): bool
    {
        if (!$conn) {
            return false;
        }
        $safeTable = $conn->real_escape_string($tableName);
        $res = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
        return ($res && $res->num_rows > 0);
    }
}

if (!function_exists('columnExists')) {
    function columnExists($conn, string $tableName, string $columnName): bool
    {
        if (!$conn) {
            return false;
        }
        $safeTable = $conn->real_escape_string($tableName);
        $safeColumn = $conn->real_escape_string($columnName);
        $res = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
        return ($res && $res->num_rows > 0);
    }
}

if ($conn) {
    try {
        $hasUserLoginTable = tableExists($conn, 'user_login');
        $hasEmployeesTable = tableExists($conn, 'employees');
        $hasUserId = $hasUserLoginTable && columnExists($conn, 'user_login', 'id');
        $hasUserUserId = $hasUserLoginTable && columnExists($conn, 'user_login', 'user_id');
        $hasUserEmail = $hasUserLoginTable && columnExists($conn, 'user_login', 'email');
        $hasUserRole = $hasUserLoginTable && columnExists($conn, 'user_login', 'role');
        $hasEmpEmail = $hasEmployeesTable && columnExists($conn, 'employees', 'email');
        $hasEmpId = $hasEmployeesTable && columnExists($conn, 'employees', 'id');
        $hasLastChange = $hasUserLoginTable && columnExists($conn, 'user_login', 'last_password_change');
        if ($hasUserUserId && !$hasUserId) {
            $userLoginIdColumn = 'user_id';
        }

        if ($hasUserLoginTable && $hasUserEmail) {
            $sel = "SELECT {$userLoginIdColumn} AS id, email";
            $sel .= $hasUserRole ? ", role" : ", 'employee' AS role";
            if ($hasLastChange) {
                $sel .= ", last_password_change";
            }
            $sel .= " FROM user_login ORDER BY email";

            $res = $conn->query($sel);
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $row['_has_last'] = $hasLastChange;
                    $row['_has_account'] = true;
                    $row['_employee_id'] = 0;
                    $accounts[] = $row;
                }
            }
        }

        if ($hasEmployeesTable && $hasEmpEmail && $hasEmpId && $hasUserLoginTable && $hasUserEmail && ($hasUserId || $hasUserUserId)) {
            $missing = $conn->query("SELECT e.id AS employee_id, e.email FROM employees e LEFT JOIN user_login u ON u.email = e.email WHERE u.{$userLoginIdColumn} IS NULL AND e.email <> '' ORDER BY e.email");
            if ($missing && $missing->num_rows > 0) {
                while ($row = $missing->fetch_assoc()) {
                    $accounts[] = [
                        'id' => 0,
                        'email' => $row['email'],
                        'role' => 'employee',
                        'last_password_change' => null,
                        '_has_last' => $hasLastChange,
                        '_has_account' => false,
                        '_employee_id' => (int)$row['employee_id'],
                    ];
                }
            }
        }

        if (!$hasUserLoginTable) {
            $msg = 'The user_login table is missing in this database.';
        } elseif (!$hasUserEmail) {
            $msg = 'The user_login.email column is missing in this database.';
        } elseif (!$hasUserId && !$hasUserUserId) {
            $msg = 'The user_login id column is missing (id/user_id).';
        }
    } catch (\Throwable $e) {
        error_log('Accounts page query failed: ' . $e->getMessage());
        if ($msg === '') {
            $msg = 'Unable to load some account records due to schema mismatch: ' . $e->getMessage();
        }
    }
}

if (isset($_SESSION['accounts_msg'])) {
    $msg = $_SESSION['accounts_msg'];
    unset($_SESSION['accounts_msg']);
}
if (isset($_SESSION['accounts_generated_password'])) {
    $generatedPassword = (string)$_SESSION['accounts_generated_password'];
    $generatedEmail = (string)($_SESSION['accounts_generated_email'] ?? '');
    $generatedMode = (string)($_SESSION['accounts_generated_mode'] ?? '');
    unset($_SESSION['accounts_generated_password'], $_SESSION['accounts_generated_email'], $_SESSION['accounts_generated_mode']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Accounts</h1>
                <p class="text-sm text-slate-500 mt-1">Create or manage employee login accounts</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') === 0 ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-amber-50 border border-amber-200 text-amber-700'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6 overflow-x-auto">
                <table id="accountsTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Email</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Last change password</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $a): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($a['email'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600">
                                <?php 
                                if (empty($a['_has_account'])) {
                                    echo 'Not created';
                                } elseif (!empty($a['_has_last']) && !empty($a['last_password_change'])) {
                                    echo date('M d, Y H:i', strtotime($a['last_password_change']));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (strtolower($a['role'] ?? '') === 'admin') ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700'; ?>">
                                    <?php echo !empty($a['_has_account']) ? htmlspecialchars($a['role'] ?? '—') : 'no account'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <?php if (empty($a['_has_account'])): ?>
                                    <form method="POST" action="accounts-action.php" onsubmit="return confirm('Create employee account and generate random password now?');" class="inline">
                                        <input type="hidden" name="action" value="create_employee_account">
                                        <input type="hidden" name="employee_id" value="<?php echo (int)$a['_employee_id']; ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition-colors" title="Create account">
                                            Create account
                                        </button>
                                    </form>
                                    <?php elseif (strtolower($a['role'] ?? '') === 'employee'): ?>
                                    <form method="POST" action="accounts-action.php" onsubmit="return confirm('Generate a new random password for this employee account?');" class="inline">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                        <button type="submit" class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="Reset password">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.343 3-3 3S6 12.657 6 11s1.343-3 3-3 3 1.343 3 3zm0 0V9a4 4 0 118 0v2m-6 0h6a2 2 0 012 2v5a2 2 0 01-2 2h-6a2 2 0 01-2-2v-5a2 2 0 012-2z" />
                                            </svg>
                                        </button>
                                    </form>
                                    <button type="button" class="edit-role-btn p-2 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white transition-colors" title="Edit role" data-id="<?php echo (int)$a['id']; ?>" data-email="<?php echo htmlspecialchars($a['email']); ?>" data-role="<?php echo htmlspecialchars($a['role'] ?? ''); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="edit-role-btn p-2 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white transition-colors" title="Edit role" data-id="<?php echo (int)$a['id']; ?>" data-email="<?php echo htmlspecialchars($a['email']); ?>" data-role="<?php echo htmlspecialchars($a['role'] ?? ''); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Edit role modal -->
    <div id="editRoleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Edit Role</h3>
            <p class="text-sm text-slate-500 mb-4" id="editRoleEmail"></p>
            <form method="POST" action="accounts-action.php" id="editRoleForm">
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="id" id="editRoleId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Role</label>
                    <select name="role" id="editRoleSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="editRoleCancel" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309]">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Generated password modal -->
    <div id="generatedPasswordModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-2">
                <?php echo $generatedMode === 'created' ? 'Account Created' : 'Password Reset'; ?>
            </h3>
            <p class="text-sm text-slate-500 mb-4">Copy this password now and give it to the employee.</p>
            <div class="mb-3">
                <p class="text-xs text-slate-500 mb-1">Email</p>
                <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($generatedEmail); ?></p>
            </div>
            <div class="mb-5">
                <p class="text-xs text-slate-500 mb-1">Generated Password</p>
                <div class="flex items-center gap-2">
                    <input id="generatedPasswordField" type="text" readonly value="<?php echo htmlspecialchars($generatedPassword); ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm">
                    <button type="button" id="copyGeneratedPasswordBtn" class="px-3 py-2 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Copy</button>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" id="generatedPasswordClose" class="px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309]">Done</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#accountsTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: { search: "", searchPlaceholder: "Search email...", emptyTable: "No accounts found." }
            });
        });

        var modal = document.getElementById('editRoleModal');
        var form = document.getElementById('editRoleForm');
        document.querySelectorAll('.edit-role-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('editRoleId').value = this.dataset.id;
                document.getElementById('editRoleEmail').textContent = this.dataset.email;
                document.getElementById('editRoleSelect').value = (this.dataset.role || 'employee').toLowerCase();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });
        document.getElementById('editRoleCancel').addEventListener('click', function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
        modal.addEventListener('click', function(e) {
            if (e.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        });

        var generatedModal = document.getElementById('generatedPasswordModal');
        var generatedPassword = <?php echo json_encode($generatedPassword); ?>;
        if (generatedModal && generatedPassword) {
            generatedModal.classList.remove('hidden');
            generatedModal.classList.add('flex');
        }
        var closeGeneratedModal = document.getElementById('generatedPasswordClose');
        if (closeGeneratedModal) {
            closeGeneratedModal.addEventListener('click', function() {
                generatedModal.classList.add('hidden');
                generatedModal.classList.remove('flex');
            });
        }
        var copyBtn = document.getElementById('copyGeneratedPasswordBtn');
        var passwordField = document.getElementById('generatedPasswordField');
        if (copyBtn && passwordField) {
            copyBtn.addEventListener('click', function() {
                passwordField.select();
                passwordField.setSelectionRange(0, 99999);
                try {
                    document.execCommand('copy');
                    copyBtn.textContent = 'Copied';
                    setTimeout(function() { copyBtn.textContent = 'Copy'; }, 1200);
                } catch (e) {}
            });
        }

        // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
