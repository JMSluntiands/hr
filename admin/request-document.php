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
if (isset($_SESSION['request_document_msg'])) {
    $msg = $_SESSION['request_document_msg'];
    unset($_SESSION['request_document_msg']);
}

$list = [];
if ($conn) {
    $sql = "SELECT dr.*, e.full_name, e.employee_id 
            FROM document_requests dr 
            JOIN employees e ON dr.employee_id = e.id 
            ORDER BY dr.created_at DESC";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $list[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Document - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#d97706] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($adminName, 0, 1)); ?></span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($adminName); ?></div>
                <div class="text-xs text-white/80">Administrator</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Dashboard</span>
            </a>
            <div class="dropdown-container">
                <button type="button" id="employees-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span>Employees</span>
                    <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="employees-dropdown" class="hidden space-y-1 mt-1">
                    <a href="staff-add" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg">Add New Employee</a>
                    <a href="staff" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg">List of Employee</a>
                </div>
            </div>
            <div class="dropdown-container">
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="leaves-dropdown" class="hidden space-y-1 mt-1">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg">Allocation of Leave</a>
                    <a href="leaves-summary" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg">Leave Summary per Employee</a>
                </div>
            </div>
            <div class="dropdown-container">
                <button type="button" id="request-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors relative z-10 select-none" style="pointer-events: auto;">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <span class="flex-1 text-left">Request</span>
                    <svg id="request-arrow" class="w-4 h-4 flex-shrink-0 ml-auto transition-transform pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="request-dropdown" class="hidden space-y-1 mt-1">
                    <a href="request-leaves" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg">Request Leaves</a>
                    <a href="request-document" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white bg-white/10 rounded-lg">Request Document</a>
                </div>
            </div>
            <a href="activity-log" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <span>Activity Log</span>
            </a>
            <a href="announcement" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                <span>Announcements</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
                <span>Role</span>
                <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium"><?php echo htmlspecialchars($role); ?></span>
            </div>
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Request Document</h1>
                <p class="text-sm text-slate-500 mt-1">Approve or decline document requests</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6">
                <table id="docRequestsTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $r):
                            $status = $r['status'] ?? 'Pending';
                            $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['employee_id'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['document_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['created_at']) ? date('M d, Y H:i', strtotime($r['created_at'])) : '—'; ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($status === 'Pending'): ?>
                                <div class="flex items-center gap-2">
                                    <a href="request-document-action.php?action=approve&id=<?php echo (int)$r['id']; ?>" class="text-emerald-600 hover:text-emerald-700">Approve</a>
                                    <button type="button" class="decline-doc-btn text-red-600 hover:text-red-700" data-id="<?php echo (int)$r['id']; ?>">Decline</button>
                                </div>
                                <?php else: ?>
                                —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="declineDocModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Decline Document Request</h2>
            </div>
            <form action="request-document-action.php" method="post" class="p-6 space-y-4">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="id" id="declineDocId" value="">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDeclineDoc" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
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
            $('#docRequestsTable').DataTable({
                pageLength: 10,
                order: [[2, 'desc']],
                language: { search: '', searchPlaceholder: 'Search...', emptyTable: 'No document requests found.' }
            });
            $(document).on('click', '.decline-doc-btn', function() {
                $('#declineDocId').val($(this).data('id'));
                $('#declineDocModal form textarea').val('');
                $('#declineDocModal').removeClass('hidden');
            });
            $('#cancelDeclineDoc').on('click', function() { $('#declineDocModal').addClass('hidden'); });
            $('#declineDocModal').on('click', function(e) { if (e.target === this) $('#declineDocModal').addClass('hidden'); });

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

            if (employeesBtn) {
                $(employeesBtn).on('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleEmployeesDropdown(); });
            }
            if (leavesBtn) {
                $(leavesBtn).on('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleLeavesDropdown(); });
            }
            if (requestBtn) {
                $(requestBtn).on('click', function(e) { 
                    e.preventDefault(); 
                    e.stopPropagation(); 
                    toggleRequestDropdown(); 
                });
                // Also handle clicks on the button using vanilla JS as fallback
                requestBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleRequestDropdown();
                }, true);
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
