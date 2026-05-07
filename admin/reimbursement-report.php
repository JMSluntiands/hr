<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';
include __DIR__ . '/../database/db.php';

$from = trim((string)($_GET['from'] ?? ''));
$to = trim((string)($_GET['to'] ?? ''));
$rows = [];
$total = 0.00;

if ($conn && $from !== '' && $to !== '') {
    $conn->query("ALTER TABLE reimbursements ADD COLUMN admin_receipt_path VARCHAR(255) NULL");
    $conn->query("ALTER TABLE reimbursements ADD COLUMN admin_receipt_original_name VARCHAR(255) NULL");
    $conn->query("ALTER TABLE reimbursements ADD COLUMN reimbursed_at DATETIME NULL");

    $stmt = $conn->prepare(
        "SELECT r.*, e.full_name
         FROM reimbursements r
         JOIN employees e ON e.id = r.employee_id
         WHERE r.status = 'Approved'
         AND r.admin_receipt_path IS NOT NULL
         AND r.admin_receipt_path <> ''
         AND DATE(r.reimbursed_at) BETWEEN ? AND ?
         ORDER BY r.reimbursed_at ASC"
    );
    if ($stmt) {
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
            $total += (float)$row['amount'];
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
    <title>Reimbursement Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <h1 class="text-2xl font-semibold text-slate-800 mb-2">Report for Reimbursement</h1>
        <p class="text-sm text-slate-500 mb-6">Filter by reimbursed date then view total reimbursements with attached admin receipt proof.</p>
        <form method="get" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">From (Reimbursed Date)</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">To (Reimbursed Date)</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="px-3 py-2 border border-slate-300 rounded-lg">
            </div>
            <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg">Filter</button>
        </form>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-6">
            <p class="text-sm text-slate-500">Total Reimbursement</p>
            <p class="text-3xl font-semibold text-emerald-600">PHP <?php echo number_format($total, 2); ?></p>
        </section>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Reimbursed Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Employee</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Expense Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Amount</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold uppercase">Proof Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No reimbursed records for selected date range.</td></tr>
                    <?php else: foreach ($rows as $row): ?>
                        <tr>
                            <td class="px-4 py-3 text-slate-700"><?php echo !empty($row['reimbursed_at']) ? date('M d, Y', strtotime((string)$row['reimbursed_at'])) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$row['full_name']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$row['expense_type']); ?></td>
                            <td class="px-4 py-3 text-slate-700">PHP <?php echo number_format((float)$row['amount'], 2); ?></td>
                            <td class="px-4 py-3 text-slate-700">
                                <a class="text-[#FA9800] hover:underline" href="../uploads/<?php echo htmlspecialchars((string)$row['admin_receipt_path']); ?>" target="_blank">View</a>
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
