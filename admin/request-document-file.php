<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$documents = [];
$autoMigrated = false;
if ($conn) {
    // Check if document_files table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'document_files'");
    if ($checkTable && $checkTable->num_rows > 0) {
        // Fetch all document files
        $sql = "SELECT df.*, e.full_name, e.employee_id 
                FROM document_files df 
                JOIN employees e ON df.employee_id = e.id 
                ORDER BY df.created_at DESC";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $documents[] = $row;
            }
        }
        
        // Auto-migrate if empty and there are approved documents
        if (empty($documents)) {
            $approvedUploads = $conn->query("SELECT COUNT(*) as cnt FROM employee_document_uploads WHERE status = 'Approved'");
            $approvedReqs = $conn->query("SELECT COUNT(*) as cnt FROM document_requests WHERE status = 'Approved'");
            $hasApproved = false;
            $uploadCnt = 0;
            $reqCnt = 0;
            if ($approvedUploads && $row = $approvedUploads->fetch_assoc()) {
                $uploadCnt = (int)$row['cnt'];
                if ($uploadCnt > 0) $hasApproved = true;
            }
            if ($approvedReqs && $row = $approvedReqs->fetch_assoc()) {
                $reqCnt = (int)$row['cnt'];
                if ($reqCnt > 0) $hasApproved = true;
            }
            
            if ($hasApproved) {
                // Auto-migrate approved employee_document_uploads
                if ($uploadCnt > 0) {
                    $migrateSql = "SELECT id, employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at 
                                  FROM employee_document_uploads 
                                  WHERE status = 'Approved'";
                    $migrateRes = $conn->query($migrateSql);
                    if ($migrateRes && $migrateRes->num_rows > 0) {
                        while ($mRow = $migrateRes->fetch_assoc()) {
                            // Check if exists
                            $chk = $conn->prepare("SELECT id FROM document_files WHERE employee_id = ? AND document_type = ? AND file_path = ? LIMIT 1");
                            $chk->bind_param('iss', $mRow['employee_id'], $mRow['document_type'], $mRow['file_path']);
                            $chk->execute();
                            $chkRes = $chk->get_result();
                            $chk->close();
                            
                            if (!$chkRes || $chkRes->num_rows === 0) {
                                $ins = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $filePath = $mRow['file_path'] ?? '';
                                $approvedBy = $mRow['approved_by'] ?? null;
                                $approvedByName = $mRow['approved_by_name'] ?? null;
                                $approvedAt = $mRow['approved_at'] ?? null;
                                $createdAt = $mRow['created_at'] ?? date('Y-m-d H:i:s');
                                $ins->bind_param('issssss', $mRow['employee_id'], $mRow['document_type'], $filePath, $approvedBy, $approvedByName, $approvedAt, $createdAt);
                                if ($ins->execute()) {
                                    $autoMigrated = true;
                                }
                                $ins->close();
                            }
                        }
                    }
                }
                
                // Auto-migrate approved document_requests
                if ($reqCnt > 0) {
                    $migrateSql2 = "SELECT id, employee_id, document_type, approved_by, approved_by_name, approved_at, created_at 
                                   FROM document_requests 
                                   WHERE status = 'Approved'";
                    $migrateRes2 = $conn->query($migrateSql2);
                    if ($migrateRes2 && $migrateRes2->num_rows > 0) {
                        while ($mRow2 = $migrateRes2->fetch_assoc()) {
                            // Check if exists
                            $chk2 = $conn->prepare("SELECT id FROM document_files WHERE employee_id = ? AND document_type = ? LIMIT 1");
                            $chk2->bind_param('is', $mRow2['employee_id'], $mRow2['document_type']);
                            $chk2->execute();
                            $chkRes2 = $chk2->get_result();
                            $chk2->close();
                            
                            if (!$chkRes2 || $chkRes2->num_rows === 0) {
                                // Try to find related file
                                $filePath2 = '';
                                $fileChk = $conn->prepare("SELECT file_path FROM employee_document_uploads WHERE employee_id = ? AND document_type = ? AND status = 'Approved' ORDER BY created_at DESC LIMIT 1");
                                if ($fileChk) {
                                    $fileChk->bind_param('is', $mRow2['employee_id'], $mRow2['document_type']);
                                    $fileChk->execute();
                                    $fileRes = $fileChk->get_result();
                                    if ($fileRow = $fileRes->fetch_assoc()) {
                                        $filePath2 = $fileRow['file_path'] ?? '';
                                    }
                                    $fileChk->close();
                                }
                                
                                $ins2 = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $approvedBy2 = $mRow2['approved_by'] ?? null;
                                $approvedByName2 = $mRow2['approved_by_name'] ?? null;
                                $approvedAt2 = $mRow2['approved_at'] ?? null;
                                $createdAt2 = $mRow2['created_at'] ?? date('Y-m-d H:i:s');
                                $ins2->bind_param('issssss', $mRow2['employee_id'], $mRow2['document_type'], $filePath2, $approvedBy2, $approvedByName2, $approvedAt2, $createdAt2);
                                if ($ins2->execute()) {
                                    $autoMigrated = true;
                                }
                                $ins2->close();
                            }
                        }
                    }
                }
                
                // Re-fetch documents after migration
                if ($autoMigrated) {
                    $res2 = $conn->query($sql);
                    if ($res2 && $res2->num_rows > 0) {
                        $documents = [];
                        while ($row = $res2->fetch_assoc()) {
                            $documents[] = $row;
                        }
                    }
                }
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
    <title>Document File - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#FA9800] text-white flex flex-col">
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
            <!-- Employees Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="employees-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Employees</span>
                    <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="employees-dropdown" class="hidden space-y-1 mt-1">
                    <a href="staff-add" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Add New Employee
                    </a>
                    <a href="staff" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        List of Employee
                    </a>
                </div>
            </div>
            <!-- Leaves Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="leaves-dropdown" class="hidden space-y-1 mt-1">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Allocation of Leave
                    </a>
                    <a href="leaves-summary" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Leave Summary per Employee
                    </a>
                </div>
            </div>
            <!-- Request Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="request-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Request</span>
                    <svg id="request-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="request-dropdown" class="hidden space-y-1 mt-1">
                    <a href="request-leaves" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">Request Leaves</a>
                    <a href="request-document" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">Request Document</a>
                </div>
            </div>
            <a href="activity-log" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Activity Log</span>
            </a>
            <a href="announcement" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>Announcements</span>
            </a>
            <a href="compensation" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Compensation</span>
            </a>
            <a href="accounts" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>Accounts</span>
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
                <h1 class="text-2xl font-semibold text-slate-800">Document File</h1>
                <p class="text-sm text-slate-500 mt-1">View all approved document files</p>
            </div>
            <a href="check-documents-debug.php" target="_blank" class="text-xs text-slate-500 hover:text-slate-700">Debug</a>
        </div>

        <?php if ($autoMigrated): ?>
        <div class="mb-6 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700">
            ✓ Auto-migrated approved documents to document files.
        </div>
        <?php endif; ?>

        <!-- Document Files Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Approved Documents</h2>
            </div>
            <div class="p-6">
                <table id="docFilesTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Approved By</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Approved At</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($documents)): ?>
                        <?php foreach ($documents as $doc): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($doc['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($doc['employee_id'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($doc['document_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($doc['approved_by_name'] ?? ($doc['approved_by'] ?? 'N/A')); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($doc['approved_at']) ? date('M d, Y H:i', strtotime($doc['approved_at'])) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($doc['created_at']) ? date('M d, Y H:i', strtotime($doc['created_at'])) : '—'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        $(function() {
            // Initialize DataTable only if table has data or structure is correct
            if ($('#docFilesTable tbody tr').length > 0 || $('#docFilesTable tbody').html().trim() === '') {
                $('#docFilesTable').DataTable({
                    pageLength: 10,
                    order: [[4, 'desc']],
                    language: { 
                        search: '', 
                        searchPlaceholder: 'Search...', 
                        emptyTable: 'No document files found.' 
                    }
                });
            }

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

            if (employeesBtn) {
                employeesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleEmployeesDropdown(); });
            }
            if (leavesBtn) {
                leavesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleLeavesDropdown(); });
            }
            if (requestBtn) {
                requestBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleRequestDropdown(); });
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
