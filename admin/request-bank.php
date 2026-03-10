<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$msg = '';
if (isset($_SESSION['request_bank_msg'])) {
    $msg = $_SESSION['request_bank_msg'];
    unset($_SESSION['request_bank_msg']);
}

$bankRequests = [];
if ($conn) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'bank_account_change_requests'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $sql = "SELECT r.*, e.full_name, e.employee_id 
                FROM bank_account_change_requests r 
                JOIN employees e ON e.id = r.employee_id 
                WHERE r.status = 'Pending' 
                ORDER BY r.requested_at DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $bankRequests[] = $row;
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
    <title>Request Bank - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Request Bank</h1>
                <p class="text-sm text-slate-500 mt-1">Approve or reject employee bank account change requests (from Compensation Details)</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Bank account change requests</h2>
                <p class="text-xs text-slate-500 mt-1">Employees request changes from My Compensation. Approve to update their bank details.</p>
            </div>
            <div class="p-6 overflow-x-auto">
                <?php if (empty($bankRequests)): ?>
                <div class="text-center py-12 text-slate-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <p class="text-sm">No pending bank account change requests.</p>
                </div>
                <?php else: ?>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Requested</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">New bank details</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bankRequests as $r): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['employee_id'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['requested_at']) ? date('M d, Y H:i', strtotime($r['requested_at'])) : '—'; ?></td>
                            <td class="px-4 py-3">
                                <div class="space-y-0.5 text-slate-700">
                                    <div><span class="text-slate-500">Bank:</span> <?php echo htmlspecialchars($r['bank_name'] ?? '—'); ?></div>
                                    <div><span class="text-slate-500">Account #:</span> <?php echo htmlspecialchars($r['account_number'] ?? '—'); ?></div>
                                    <div><span class="text-slate-500">Account name:</span> <?php echo htmlspecialchars($r['account_name'] ?? '—'); ?></div>
                                    <div><span class="text-slate-500">Type:</span> <?php echo htmlspecialchars($r['account_type'] ?? '—'); ?><?php if (!empty($r['branch'])): ?> · <?php echo htmlspecialchars($r['branch']); ?><?php endif; ?></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="request-bank-action.php?action=approve&id=<?php echo (int)$r['id']; ?>" class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="Approve">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </a>
                                    <button type="button" class="decline-bank-btn p-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors" data-id="<?php echo (int)$r['id']; ?>" title="Decline">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Decline Modal -->
    <div id="declineBankModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Decline bank account change</h2>
            </div>
            <form action="request-bank-action.php" method="post" class="p-6 space-y-4">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="id" id="declineBankId" value="">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDeclineBank" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Decline</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(function() {
            $(document).on('click', '.decline-bank-btn', function() {
                $('#declineBankId').val($(this).data('id'));
                $('#declineBankModal form textarea').val('');
                $('#declineBankModal').removeClass('hidden');
            });
            $('#cancelDeclineBank').on('click', function() { $('#declineBankModal').addClass('hidden'); });
            $('#declineBankModal').on('click', function(e) { if (e.target === this) $('#declineBankModal').addClass('hidden'); });
        });
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
