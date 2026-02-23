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

if ($conn) {
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM user_login");
    if ($r) {
        while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
    }
    $hasLastChange = in_array('last_password_change', $cols);
    $hasLocked = in_array('locked', $cols);
    $hasUnlockReq = in_array('unlock_requested', $cols);

    $sel = "SELECT id, email, role";
    if ($hasLastChange) $sel .= ", last_password_change";
    if ($hasLocked) $sel .= ", locked, locked_at";
    if ($hasUnlockReq) $sel .= ", unlock_requested";
    $sel .= " FROM user_login ORDER BY email";

    $res = $conn->query($sel);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $row['_has_last'] = $hasLastChange;
            $row['_has_locked'] = $hasLocked;
            $row['_has_unlock_req'] = $hasUnlockReq;
            $accounts[] = $row;
        }
    }
}

if (isset($_SESSION['accounts_msg'])) {
    $msg = $_SESSION['accounts_msg'];
    unset($_SESSION['accounts_msg']);
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

    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Accounts</h1>
                <p class="text-sm text-slate-500 mt-1">User login accounts — edit role, unlock locked accounts</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') === 0 ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-amber-50 border border-amber-200 text-amber-700'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6">
                <table id="accountsTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Email</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Last change password</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $a): 
                            $isLocked = !empty($a['_has_locked']) && !empty($a['locked']);
                            $unlockReq = !empty($a['_has_unlock_req']) && !empty($a['unlock_requested']);
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($a['email'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600">
                                <?php 
                                if (!empty($a['_has_last']) && !empty($a['last_password_change'])) {
                                    echo date('M d, Y H:i', strtotime($a['last_password_change']));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (strtolower($a['role'] ?? '') === 'admin') ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700'; ?>">
                                    <?php echo htmlspecialchars($a['role'] ?? '—'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($isLocked): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        Locked
                                        <?php if ($unlockReq): ?><span class="text-red-500" title="Unlock requested">•</span><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="edit-role-btn p-2 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white transition-colors" title="Edit role" data-id="<?php echo (int)$a['id']; ?>" data-email="<?php echo htmlspecialchars($a['email']); ?>" data-role="<?php echo htmlspecialchars($a['role'] ?? ''); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <?php if ($isLocked): ?>
                                    <a href="accounts-action?action=unlock&id=<?php echo (int)$a['id']; ?>" class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="Unlock" onclick="return confirm('Unlock this account?');">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                                    </a>
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
            <form method="POST" action="accounts-action" id="editRoleForm">
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

        // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
