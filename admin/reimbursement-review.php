<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';
include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../include/admin-permissions.php';
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

$msg = $_SESSION['reimbursement_msg'] ?? '';
unset($_SESSION['reimbursement_msg']);

$list = [];
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS reimbursements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        expense_type VARCHAR(100) NOT NULL,
        expense_description TEXT NOT NULL,
        purchased_date DATE NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        notes TEXT NULL,
        receipt_path VARCHAR(255) NULL,
        receipt_original_name VARCHAR(255) NULL,
        admin_receipt_path VARCHAR(255) NULL,
        admin_receipt_original_name VARCHAR(255) NULL,
        reimbursed_at DATETIME NULL,
        status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        rejection_reason TEXT NULL,
        approved_by INT NULL,
        approved_by_name VARCHAR(150) NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $sql = "SELECT r.*, e.full_name, e.employee_id AS emp_code
            FROM reimbursements r
            JOIN employees e ON e.id = r.employee_id
            WHERE r.status = 'Pending'
            ORDER BY r.created_at DESC";
    $res = $conn->query($sql);
    if ($res) while ($row = $res->fetch_assoc()) $list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For Review Reimbursement</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <h1 class="text-2xl font-semibold text-slate-800 mb-2">For Review of Reimbursement</h1>
        <p class="text-sm text-slate-500 mb-6">All reimbursement requests from employees appear here.</p>
        <?php if ($msg): ?><div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Employee</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Expense Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Purchased Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Amount</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Receipt</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (empty($list)): ?>
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No pending reimbursement requests.</td></tr>
                <?php else: foreach ($list as $r): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-700"><?php echo htmlspecialchars((string)$r['full_name']); ?></div>
                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars((string)$r['emp_code']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$r['expense_type']); ?></td>
                        <td class="px-4 py-3 text-slate-700"><?php echo date('M d, Y', strtotime((string)$r['purchased_date'])); ?></td>
                        <td class="px-4 py-3 text-slate-700">PHP <?php echo number_format((float)$r['amount'], 2); ?></td>
                        <td class="px-4 py-3 text-slate-700">
                            <?php if (!empty($r['receipt_path'])): ?>
                                <a class="text-[#FA9800] hover:underline" target="_blank" href="../uploads/<?php echo htmlspecialchars((string)$r['receipt_path']); ?>">View</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <?php if (adminCanApproveEmployee($conn, $currentAdminId, 'approve_reimbursement', (int)($r['employee_id'] ?? 0))): ?>
                                <a href="reimbursement-action.php?action=approve&id=<?php echo (int)$r['id']; ?>" class="px-3 py-1.5 rounded bg-emerald-600 text-white text-xs">Approve</a>
                                <button type="button" class="declineBtn px-3 py-1.5 rounded bg-red-600 text-white text-xs" data-id="<?php echo (int)$r['id']; ?>">Decline</button>
                                <?php else: ?>
                                <span class="text-xs text-slate-400" title="No department permission">No access</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <div id="declineModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <form action="reimbursement-action.php" method="post" class="p-6 space-y-4">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="id" id="declineId">
                <label class="block text-sm font-medium text-slate-700">Reason for declining</label>
                <textarea name="rejection_reason" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDeclineBtn" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Decline</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.querySelectorAll('.declineBtn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('declineId').value = btn.getAttribute('data-id');
            document.getElementById('declineModal').classList.remove('hidden');
            document.getElementById('declineModal').classList.add('flex');
        });
    });
    document.getElementById('cancelDeclineBtn')?.addEventListener('click', function () {
        document.getElementById('declineModal').classList.remove('flex');
        document.getElementById('declineModal').classList.add('hidden');
    });
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
