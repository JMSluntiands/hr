<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';
require_once __DIR__ . '/../include/admin-permissions.php';
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/../include/ensure_document_requests_coe_columns.php';
require_once __DIR__ . '/../include/ensure_document_files_request_link.php';
if ($conn) {
    ensure_document_requests_coe_columns($conn);
    ensure_document_files_request_link($conn);
}

// Same-page POST: avoids broken action URLs (extensionless routes, wrong base path)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_action'])) {
    require_once __DIR__ . '/request-document-handler.inc.php';
    exit;
}

$msg = '';
if (isset($_SESSION['request_document_msg'])) {
    $msg = $_SESSION['request_document_msg'];
    unset($_SESSION['request_document_msg']);
}

$list = [];
if ($conn) {
    // Document requests (certificates like COE, SSS, etc.)
    $checkDocReq = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($checkDocReq && $checkDocReq->num_rows > 0) {
        $sql = "SELECT dr.*, e.full_name, e.employee_id AS emp_staff_code
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Request Document</h1>
                <p class="text-sm text-slate-500 mt-1">Staff requests for issued documents (COE, SSS forms, etc.). Approve or decline each request here.</p>
                <p class="text-xs text-slate-600 mt-2 max-w-3xl"><strong>COE:</strong> awtomatikong PDF mula sa employee records + request (purpose, salary on/off); i-edit ang letterhead sa <code class="bg-slate-100 px-1 rounded">config/coe_pdf_branding.php</code>. <strong>Iba pang sertipiko:</strong> optional na master PDF sa <code class="bg-slate-100 px-1 rounded">uploads/certificate_templates/</code> (slug ng type + <code class="bg-slate-100 px-1 rounded">.pdf</code>). Sa <strong>Approve</strong>, na-link ang file sa My Request.</p>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <!-- Document Requests (Certificates) -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Document requests</h2>
                <p class="text-xs text-slate-500 mt-1">For profile file uploads (IDs, 201), use Request Upload in the sidebar.</p>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="docRequestsTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">COE purpose</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Salary on COE</th>
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
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['emp_staff_code'] ?? $r['employee_id'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['document_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo ($r['document_type'] ?? '') === 'COE' && !empty($r['coe_purpose']) ? htmlspecialchars($r['coe_purpose']) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo ($r['document_type'] ?? '') === 'COE' && !empty($r['coe_include_salary']) ? htmlspecialchars($r['coe_include_salary']) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['created_at']) ? date('M d, Y H:i', strtotime($r['created_at'])) : '—'; ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php
                                $canApproveDocReq = ($status === 'Pending') && adminCanApproveEmployee($conn, $currentAdminId, 'approve_document_request', (int)($r['employee_id'] ?? 0));
                                if ($canApproveDocReq):
                                ?>
                                <div class="flex items-center gap-2">
                                    <form method="post" action="" class="inline" onsubmit="return confirm('Approve this document request?');">
                                        <input type="hidden" name="req_action" value="approve">
                                        <input type="hidden" name="document_request_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                        <button type="submit" class="text-emerald-600 hover:text-emerald-700 bg-transparent border-0 p-0 cursor-pointer text-sm underline-offset-2 hover:underline">Approve</button>
                                    </form>
                                    <button type="button" class="decline-doc-btn text-red-600 hover:text-red-700" data-id="<?php echo (int)($r['id'] ?? 0); ?>">Decline</button>
                                </div>
                                <?php elseif ($status === 'Pending'): ?>
                                <span class="text-xs text-slate-400" title="No department permission">No access</span>
                                <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
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
            <form action="" method="post" class="p-6 space-y-4">
                <input type="hidden" name="req_action" value="decline">
                <input type="hidden" name="document_request_id" id="declineDocId" value="">
                <input type="hidden" name="id" id="declineDocIdLegacy" value="">
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
            $(document).on('click', '.decline-doc-btn', function() {
                const rid = parseInt($(this).data('id'), 10) || 0;
                $('#declineDocId').val(rid);
                $('#declineDocIdLegacy').val(rid);
                $('#declineDocModal form textarea').val('');
                $('#declineDocModal').removeClass('hidden');
            });
            $('#cancelDeclineDoc').on('click', function() {
                $('#declineDocModal').addClass('hidden');
                $('#declineDocId, #declineDocIdLegacy').val('');
            });
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
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
