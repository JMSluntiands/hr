<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

if (strtolower($role) !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
require_once __DIR__ . '/../include/admin-permissions.php';
$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

$msg = '';
if (isset($_SESSION['document_archive_msg'])) {
    $msg = $_SESSION['document_archive_msg'];
    unset($_SESSION['document_archive_msg']);
}

$pending = [];
$archived = [];
$schemaOk = false;

if ($conn) {
    $col = $conn->query("SHOW COLUMNS FROM employee_document_uploads LIKE 'deletion_requested_at'");
    $tbl = $conn->query("SHOW TABLES LIKE 'document_archive'");
    $schemaOk = ($col && $col->num_rows > 0 && $tbl && $tbl->num_rows > 0);

    if ($schemaOk) {
        $q1 = "SELECT edu.id, edu.employee_id, edu.document_type, edu.file_path, edu.deletion_requested_at, edu.created_at,
                      e.full_name, e.employee_id AS emp_code
               FROM employee_document_uploads edu
               JOIN employees e ON edu.employee_id = e.id
               WHERE edu.status = 'Approved' AND edu.deletion_requested_at IS NOT NULL
               ORDER BY edu.deletion_requested_at DESC";
        $r1 = $conn->query($q1);
        if ($r1) {
            while ($row = $r1->fetch_assoc()) {
                $pending[] = $row;
            }
        }

        $q2 = "SELECT da.* FROM document_archive da ORDER BY da.archived_at DESC LIMIT 200";
        $r2 = $conn->query($q2);
        if ($r2) {
            while ($row = $r2->fetch_assoc()) {
                $archived[] = $row;
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
    <title>Document Archive - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Document Archive</h1>
            <p class="text-sm text-slate-500 mt-1">Staff removal requests land here. Approve to move the file into archive and remove it from the employee profile; reject to restore normal access.</p>
        </div>

        <?php if (!$schemaOk): ?>
        <div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-sm">
            Database setup required. Run <code class="bg-amber-100 px-1 rounded">database/setup_document_deletion_archive.php</code> once (browser or CLI).
        </div>
        <?php endif; ?>

        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($msg, '✓') !== false ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 mb-8">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Pending removal requests</h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <?php if (empty($pending)): ?>
                <p class="text-sm text-slate-500">No pending requests.</p>
                <?php else: ?>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Requested</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($p['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($p['emp_code'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($p['document_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($p['deletion_requested_at']) ? date('M d, Y H:i', strtotime($p['deletion_requested_at'])) : '—'; ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if (adminCanApproveEmployee($conn, $currentAdminId, 'approve_document_removal', (int)($p['employee_id'] ?? 0))): ?>
                                    <a href="document-archive-action.php?action=approve&amp;id=<?php echo (int)$p['id']; ?>"
                                       onclick="return confirm('Approve removal? File will be kept in archive only.');"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Approve</span>
                                    </a>
                                    <a href="document-archive-action.php?action=reject&amp;id=<?php echo (int)$p['id']; ?>"
                                       onclick="return confirm('Reject request? Employee will keep the document.');"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-700 text-xs font-semibold transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span>Reject</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-xs text-slate-400" title="No department permission">No access</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Archived files (after approved removal)</h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <?php if (empty($archived)): ?>
                <p class="text-sm text-slate-500">No archived documents yet.</p>
                <?php else: ?>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left">
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Document</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Archived</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">By</th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-600 uppercase">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived as $a): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700"><?php echo htmlspecialchars($a['employee_full_name'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($a['document_type'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($a['archived_at']) ? date('M d, Y H:i', strtotime($a['archived_at'])) : '—'; ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($a['archived_by_name'] ?? '—'); ?></td>
                            <td class="px-4 py-3">
                                <a href="document-archive-view.php?id=<?php echo (int)$a['id']; ?>" target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white text-xs font-semibold shadow-sm transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7v7m0-7L10 14m-4-4H5a2 2 0 00-2 2v7a2 2 0 002 2h7a2 2 0 002-2v-1" />
                                    </svg>
                                    <span>Open</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
