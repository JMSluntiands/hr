<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';
include __DIR__ . '/../database/db.php';

$msg = $_SESSION['reimbursement_list_msg'] ?? '';
unset($_SESSION['reimbursement_list_msg']);

$list = [];
if ($conn) {
    $conn->query("ALTER TABLE reimbursements ADD COLUMN admin_receipt_path VARCHAR(255) NULL");
    $conn->query("ALTER TABLE reimbursements ADD COLUMN admin_receipt_original_name VARCHAR(255) NULL");
    $conn->query("ALTER TABLE reimbursements ADD COLUMN reimbursed_at DATETIME NULL");

    $sql = "SELECT r.*, e.full_name, e.employee_id AS emp_code
            FROM reimbursements r
            JOIN employees e ON e.id = r.employee_id
            WHERE r.status = 'Approved'
            ORDER BY r.approved_at DESC, r.created_at DESC";
    $res = $conn->query($sql);
    if ($res) while ($row = $res->fetch_assoc()) $list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Reimbursement</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <h1 class="text-2xl font-semibold text-slate-800 mb-2">List of Reimbursement</h1>
        <p class="text-sm text-slate-500 mb-4">Approved reimbursements after admin review.</p>
        <?php if ($msg): ?>
            <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Employee</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Expense Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Purchased Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Amount</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Approved By</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Reimbursement Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No approved reimbursements yet.</td></tr>
                    <?php else: foreach ($list as $row): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$row['full_name']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$row['expense_type']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo date('M d, Y', strtotime((string)$row['purchased_date'])); ?></td>
                            <td class="px-4 py-3 text-slate-700">PHP <?php echo number_format((float)$row['amount'], 2); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)($row['approved_by_name'] ?: 'Admin')); ?></td>
                            <td class="px-4 py-3 text-slate-700">
                                <?php if (!empty($row['admin_receipt_path'])): ?>
                                    <a class="text-[#FA9800] hover:underline" href="../uploads/<?php echo htmlspecialchars((string)$row['admin_receipt_path']); ?>" target="_blank">View Attached</a>
                                    <div class="text-xs text-slate-500 mt-1">Attached <?php echo !empty($row['reimbursed_at']) ? date('M d, Y h:i A', strtotime((string)$row['reimbursed_at'])) : ''; ?></div>
                                <?php else: ?>
                                    <form action="reimbursement-attach-receipt.php" method="post" enctype="multipart/form-data" class="flex items-center gap-2">
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="file" name="admin_receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="block w-full text-xs">
                                        <button type="submit" class="px-3 py-1.5 rounded bg-[#FA9800] text-white text-xs whitespace-nowrap">Attach</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
