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
$uploadList = [];
if ($conn) {
    // Document requests (certificates like COE, SSS, etc.)
    $checkDocReq = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($checkDocReq && $checkDocReq->num_rows > 0) {
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
    
    // Employee document uploads (HRIS 201 file - Birth Certificate, IDs, etc.)
    $checkDocUpload = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($checkDocUpload && $checkDocUpload->num_rows > 0) {
        $uploadSql = "SELECT edu.*, e.full_name, e.employee_id 
                      FROM employee_document_uploads edu 
                      JOIN employees e ON edu.employee_id = e.id 
                      WHERE edu.status = 'Pending'
                      ORDER BY edu.created_at DESC";
        $uploadRes = $conn->query($uploadSql);
        if ($uploadRes && $uploadRes->num_rows > 0) {
            while ($row = $uploadRes->fetch_assoc()) {
                $uploadList[] = $row;
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
    <title>Request Document - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Request Document</h1>
                <p class="text-sm text-slate-500 mt-1">Approve or decline document requests and HRIS 201 file uploads</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <!-- Document Requests (Certificates) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-6">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Certificate Requests</h2>
            </div>
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

        <!-- HRIS 201 File Uploads (Employee Document Uploads) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">HRIS 201 File Uploads</h2>
            </div>
            <div class="p-6">
                <table id="docUploadsTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Uploaded</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploadList as $u): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($u['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($u['employee_id'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($u['document_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($u['created_at']) ? date('M d, Y H:i', strtotime($u['created_at'])) : '—'; ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pending</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="../employee/document-view.php?id=<?php echo (int)$u['id']; ?>" target="_blank" class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <a href="request-document-file-action.php?action=approve&id=<?php echo (int)$u['id']; ?>" class="p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="Approve">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </a>
                                    <button type="button" class="decline-upload-btn p-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors" data-id="<?php echo (int)$u['id']; ?>" title="Decline">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Decline Document Request Modal -->
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

    <!-- Decline Upload Modal -->
    <div id="declineUploadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-800">Decline Document Upload</h2>
            </div>
            <form action="request-document-file-action.php" method="post" class="p-6 space-y-4">
                <input type="hidden" name="action" value="decline">
                <input type="hidden" name="id" id="declineUploadId" value="">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Reason for declining <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelDeclineUpload" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
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
                language: { search: '', searchPlaceholder: 'Search...', emptyTable: 'No certificate requests found.' }
            });
            $('#docUploadsTable').DataTable({
                pageLength: 10,
                order: [[2, 'desc']],
                language: { search: '', searchPlaceholder: 'Search...', emptyTable: 'No pending document uploads found.' }
            });
            $(document).on('click', '.decline-doc-btn', function() {
                $('#declineDocId').val($(this).data('id'));
                $('#declineDocModal form textarea').val('');
                $('#declineDocModal').removeClass('hidden');
            });
            $(document).on('click', '.decline-upload-btn', function() {
                $('#declineUploadId').val($(this).data('id'));
                $('#declineUploadModal form textarea').val('');
                $('#declineUploadModal').removeClass('hidden');
            });
            $('#cancelDeclineDoc').on('click', function() { $('#declineDocModal').addClass('hidden'); });
            $('#cancelDeclineUpload').on('click', function() { $('#declineUploadModal').addClass('hidden'); });
            $('#declineDocModal').on('click', function(e) { if (e.target === this) $('#declineDocModal').addClass('hidden'); });
            $('#declineUploadModal').on('click', function(e) { if (e.target === this) $('#declineUploadModal').addClass('hidden'); });

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
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
