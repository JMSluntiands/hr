<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/employee_data.php';

$rows = [];
if ($conn && $employeeDbId) {
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
        status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        rejection_reason TEXT NULL,
        approved_by INT NULL,
        approved_by_name VARCHAR(150) NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_employee (employee_id),
        INDEX idx_status (status),
        INDEX idx_purchased_date (purchased_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $conn->prepare("SELECT * FROM reimbursements WHERE employee_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param('i', $employeeDbId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
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
    <title>My Reimbursement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate"><?php echo htmlspecialchars($employeeName); ?></span>
                <span class="text-[11px] text-white/80">Employee</span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </header>
    <?php require_once __DIR__ . '/../include/sidebar-scrollbar-once.php'; ?>
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-2">
            <a href="index.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">Dashboard</a>
            <a href="profile.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">My Profile</a>
            <a href="timeoff.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">My Leave Credits</a>
            <a href="request.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">My Request</a>
            <a href="reimbursement.php" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white/20 text-sm font-medium text-white">My Reimbursement</a>
            <a href="compensation.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">My Compensation</a>
            <?php include __DIR__ . '/include/sidebar-my-inventory-nav.php'; ?>
            <a href="progressive-discipline.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">Progressive Discipline</a>
            <?php include __DIR__ . '/include/sidebar-incident-nav.php'; ?>
            <a href="settings.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">Settings</a>
        </nav>
        <div class="shrink-0 border-t border-white/20 p-4">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
    </aside>
    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Reimbursement</h1>
                <p class="text-sm text-slate-500 mt-1">Fill up reimbursement request form and track status.</p>
            </div>
            <button id="openModalBtn" class="px-4 py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#d18a15]">New Request</button>
        </div>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Expense Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Amount</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Reason (if declined)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No reimbursement requests yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $status = (string)$row['status'];
                                $hasAdminReceipt = !empty($row['admin_receipt_path']);
                                $displayStatus = 'For Review';
                                $badge = 'bg-amber-100 text-amber-700';
                                if ($status === 'Rejected') {
                                    $displayStatus = 'Declined';
                                    $badge = 'bg-red-100 text-red-700';
                                } elseif ($status === 'Approved' && !$hasAdminReceipt) {
                                    $displayStatus = 'For Reimburse';
                                    $badge = 'bg-blue-100 text-blue-700';
                                } elseif ($status === 'Approved' && $hasAdminReceipt) {
                                    $displayStatus = 'Completed';
                                    $badge = 'bg-emerald-100 text-emerald-700';
                                }
                                ?>
                                <tr>
                                    <td class="px-4 py-3 text-slate-700"><?php echo date('M d, Y', strtotime((string)$row['created_at'])); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$row['expense_type']); ?></td>
                                    <td class="px-4 py-3 text-slate-700">PHP <?php echo number_format((float)$row['amount'], 2); ?></td>
                                    <td class="px-4 py-3"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge; ?>"><?php echo htmlspecialchars($displayStatus); ?></span></td>
                                    <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars((string)($row['rejection_reason'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div id="formModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800">Reimbursement Request Form</h2>
                <button id="closeModalBtn" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form id="reimbursementForm" class="p-6 space-y-4" enctype="multipart/form-data">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Expense Type <span class="text-red-500">*</span></label>
                    <select name="expense_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                        <option value="">Select Expense Type</option>
                        <option>Office Supplies</option>
                        <option>Travel</option>
                        <option>Training Materials</option>
                        <option>Equipment</option>
                        <option>Birthday Treat</option>
                        <option>Meal Treat</option>
                        <option>Miscellaneous</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Expense Description <span class="text-red-500">*</span></label>
                    <textarea name="expense_description" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Purchased Date <span class="text-red-500">*</span></label>
                        <input type="date" name="purchased_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Amount (PHP) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Upload Receipt / Photo (max 5GB)</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="cancelModalBtn" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg">Cancel</button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d18a15]">Submit</button>
                </div>
                <div id="formMessage" class="hidden text-sm rounded-lg px-3 py-2"></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="include/sidebar-employee.js"></script>
    <script>
    $(function () {
        function hideModal() {
            $('#formModal').removeClass('flex').addClass('hidden');
            $('#reimbursementForm')[0].reset();
        }

        $('#openModalBtn').on('click', function () {
            $('#formModal').removeClass('hidden').addClass('flex');
        });
        $('#closeModalBtn, #cancelModalBtn').on('click', hideModal);
        $('#formModal').on('click', function (e) {
            if (e.target.id === 'formModal') hideModal();
        });

        $('#reimbursementForm').on('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(this);
            $('#submitBtn').prop('disabled', true).text('Submitting...');
            $('#formMessage').addClass('hidden').removeClass('bg-red-50 text-red-700 bg-emerald-50 text-emerald-700').text('');

            $.ajax({
                url: 'submit-reimbursement.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    $('#submitBtn').prop('disabled', false).text('Submit');
                    if (res.status === 'success') {
                        $('#formMessage').removeClass('hidden').addClass('bg-emerald-50 text-emerald-700').text(res.message || 'Submitted.');
                        setTimeout(function () { window.location.reload(); }, 1000);
                    } else {
                        $('#formMessage').removeClass('hidden').addClass('bg-red-50 text-red-700').text(res.message || 'Submission failed.');
                    }
                },
                error: function () {
                    $('#submitBtn').prop('disabled', false).text('Submit');
                    $('#formMessage').removeClass('hidden').addClass('bg-red-50 text-red-700').text('Submission failed. Please try again.');
                }
            });
        });
    });
    </script>
</body>
</html>
