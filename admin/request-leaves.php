<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$msg = '';
if (isset($_SESSION['request_leaves_msg'])) {
    $msg = $_SESSION['request_leaves_msg'];
    unset($_SESSION['request_leaves_msg']);
}

$list = [];
if ($conn) {
    // Check if leave_requests table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $sql = "SELECT lr.*, e.full_name, e.employee_id,
                CASE 
                    WHEN lr.start_date = lr.end_date THEN 1
                    ELSE COALESCE(lr.days, DATEDIFF(lr.end_date, lr.start_date) + 1)
                END as calculated_days
                FROM leave_requests lr 
                JOIN employees e ON lr.employee_id = e.id 
                ORDER BY lr.created_at DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                // Ensure days is at least 1 if start and end are the same
                if ($row['start_date'] == $row['end_date']) {
                    $row['calculated_days'] = 1;
                }
                $list[] = $row;
            }
        }
    }
}

$hasApprovedByName = false;
if ($conn) {
    // Check if leave_requests table exists first
    $checkTable = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $chk = @$conn->query("SHOW COLUMNS FROM leave_requests LIKE 'approved_by_name'");
        $hasApprovedByName = $chk && $chk->num_rows > 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leaves - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Request Leaves</h1>
                <p class="text-sm text-slate-500 mt-1">View, approve or decline leave requests</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6">
                <table id="leaveRequestsTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Leave Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Start</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">End</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Days</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Approved By</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $r):
                            $approvedBy = '—';
                            if (!empty($r['approved_by'])) {
                                $approvedBy = ($hasApprovedByName && !empty($r['approved_by_name'])) 
                                    ? htmlspecialchars($r['approved_by_name']) 
                                    : 'User #' . (int)$r['approved_by'];
                            }
                            $status = $r['status'] ?? 'Pending';
                            $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50" data-id="<?php echo (int)$r['id']; ?>"
                            data-employee="<?php echo htmlspecialchars($r['full_name'] ?? ''); ?>"
                            data-type="<?php echo htmlspecialchars($r['leave_type'] ?? ''); ?>"
                            data-start="<?php echo htmlspecialchars($r['start_date'] ?? ''); ?>"
                            data-end="<?php echo htmlspecialchars($r['end_date'] ?? ''); ?>"
                            data-days="<?php 
                                $days = (int)($r['calculated_days'] ?? $r['days'] ?? 0);
                                if ($r['start_date'] == $r['end_date'] && $days == 0) $days = 1;
                                echo $days;
                            ?>"
                            data-reason="<?php echo htmlspecialchars($r['reason'] ?? ''); ?>"
                            data-status="<?php echo htmlspecialchars($status); ?>"
                            data-approved="<?php echo htmlspecialchars($approvedBy); ?>"
                            data-rejection="<?php echo htmlspecialchars($r['rejection_reason'] ?? ''); ?>">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['employee_id'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['leave_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['start_date']) ? date('M d, Y', strtotime($r['start_date'])) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['end_date']) ? date('M d, Y', strtotime($r['end_date'])) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo (int)($r['total_days'] ?? 0); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo $approvedBy; ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="view-leave-btn p-2 text-slate-600 hover:text-[#FA9800] hover:bg-[#FA9800]/10 rounded-lg transition-colors" title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <?php if ($status === 'Pending'): ?>
                                    <a href="request-leave-action.php?action=approve&id=<?php echo (int)$r['id']; ?>" class="p-2 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" title="Approve">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </a>
                                    <button type="button" class="decline-leave-btn p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Decline" data-id="<?php echo (int)$r['id']; ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
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

    <div id="viewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-slate-800">Leave Request Details</h2>
                <button type="button" id="closeViewModal" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div id="viewModalBody" class="p-6 space-y-3 text-sm"></div>
        </div>
    </div>

    <div id="declineModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Decline Leave Request</h2>
            </div>
            <form action="request-leave-action.php" method="post" class="p-6 space-y-4">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="id" id="declineId" value="">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDecline" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Decline</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        #request-dropdown-btn {
            position: relative;
            z-index: 10;
            -webkit-tap-highlight-color: transparent;
        }
        #request-dropdown-btn svg,
        #request-dropdown-btn span {
            pointer-events: none;
            user-select: none;
        }
    </style>
    <script>
        $(function() {
            $('#leaveRequestsTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                language: { search: '', searchPlaceholder: 'Search...', emptyTable: 'No leave requests found.' }
            });

            $(document).on('click', '.view-leave-btn', function() {
                var tr = $(this).closest('tr');
                var iso = { start: tr.data('start'), end: tr.data('end') };
                var start = iso.start ? new Date(iso.start).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
                var end = iso.end ? new Date(iso.end).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
                
                // Calculate days properly - if start and end are same, it's 1 day
                var days = parseInt(tr.data('days') || 0);
                if (iso.start && iso.end && iso.start === iso.end) {
                    days = 1;
                }
                
                var status = tr.data('status') || 'Pending';
                var statusClass = status === 'Approved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 
                                 (status === 'Rejected' ? 'bg-red-100 text-red-700 border-red-200' : 
                                 'bg-amber-100 text-amber-700 border-amber-200');
                
                var html = '<div class="space-y-4">' +
                    '<div class="grid grid-cols-2 gap-4">' +
                    '<div class="bg-slate-50 rounded-lg p-4 border border-slate-200">' +
                    '<div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Employee</div>' +
                    '<div class="text-sm font-semibold text-slate-800">' + (tr.data('employee') || '—') + '</div>' +
                    '</div>' +
                    '<div class="bg-slate-50 rounded-lg p-4 border border-slate-200">' +
                    '<div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Leave Type</div>' +
                    '<div class="text-sm font-semibold text-slate-800">' + (tr.data('type') || '—') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="grid grid-cols-2 gap-4">' +
                    '<div class="bg-blue-50 rounded-lg p-4 border border-blue-200">' +
                    '<div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Start Date</div>' +
                    '<div class="text-sm font-semibold text-blue-800">' + start + '</div>' +
                    '</div>' +
                    '<div class="bg-blue-50 rounded-lg p-4 border border-blue-200">' +
                    '<div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">End Date</div>' +
                    '<div class="text-sm font-semibold text-blue-800">' + end + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="bg-purple-50 rounded-lg p-4 border border-purple-200">' +
                    '<div class="text-xs font-medium text-purple-600 uppercase tracking-wide mb-1">Total Days</div>' +
                    '<div class="text-2xl font-bold text-purple-800">' + days + ' <span class="text-sm font-normal">day' + (days !== 1 ? 's' : '') + '</span></div>' +
                    '</div>' +
                    '<div class="bg-slate-50 rounded-lg p-4 border border-slate-200">' +
                    '<div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Reason</div>' +
                    '<div class="text-sm text-slate-700 leading-relaxed">' + (tr.data('reason') || '—') + '</div>' +
                    '</div>' +
                    '<div class="grid grid-cols-2 gap-4">' +
                    '<div class="bg-slate-50 rounded-lg p-4 border border-slate-200">' +
                    '<div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Status</div>' +
                    '<span class="inline-flex px-3 py-1 rounded-full text-xs font-medium ' + statusClass + '">' + status + '</span>' +
                    '</div>' +
                    '<div class="bg-slate-50 rounded-lg p-4 border border-slate-200">' +
                    '<div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Approved By</div>' +
                    '<div class="text-sm font-semibold text-slate-800">' + (tr.data('approved') || '—') + '</div>' +
                    '</div>' +
                    '</div>';
                var rej = (tr.data('rejection') || '').trim();
                if (rej) {
                    html += '<div class="bg-red-50 rounded-lg p-4 border border-red-200">' +
                        '<div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-2">Rejection Reason</div>' +
                        '<div class="text-sm text-red-700 leading-relaxed">' + rej + '</div>' +
                        '</div>';
                }
                html += '</div>';
                $('#viewModalBody').html(html);
                $('#viewModal').removeClass('hidden');
            });

            $('#closeViewModal').on('click', function() { $('#viewModal').addClass('hidden'); });
            $('#viewModal').on('click', function(e) { if (e.target === this) $('#viewModal').addClass('hidden'); });

            $(document).on('click', '.decline-leave-btn', function() {
                $('#declineId').val($(this).data('id'));
                $('#declineModal form textarea').val('');
                $('#declineModal').removeClass('hidden');
            });
            $('#cancelDecline').on('click', function() { $('#declineModal').addClass('hidden'); });
            $('#declineModal').on('click', function(e) { if (e.target === this) $('#declineModal').addClass('hidden'); });

            // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js
        });
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
