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
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
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
            <div class="p-6 overflow-x-auto">
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
