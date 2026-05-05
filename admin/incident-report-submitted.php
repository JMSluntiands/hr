<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/incident_reports_schema.php';

$tableReady = $conn && ensureIncidentReportsTable($conn);
$flash = $_SESSION['incident_report_flash'] ?? '';
unset($_SESSION['incident_report_flash']);

$statusFilter = trim($_GET['status'] ?? 'Pending');
$allowedStatuses = ['Pending', 'Approved', 'Declined'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'Pending';
}

$employeeQ = trim($_GET['employee'] ?? '');
$typeFilter = trim($_GET['incident_type'] ?? '');
$allowedTypes = incidentReportAllowedTypes();
if ($typeFilter !== '' && !in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = '';
}

$reports = [];
if ($tableReady) {
    $parts = ['ir.id IS NOT NULL'];
    $types = '';
    $params = [];

    if ($statusFilter !== '') {
        $parts[] = 'ir.review_status = ?';
        $types .= 's';
        $params[] = $statusFilter;
    }
    if ($employeeQ !== '') {
        $parts[] = 'ir.employee_name LIKE ?';
        $types .= 's';
        $params[] = '%' . $employeeQ . '%';
    }
    if ($typeFilter !== '') {
        $parts[] = 'ir.incident_type = ?';
        $types .= 's';
        $params[] = $typeFilter;
    }

    $sql = "SELECT ir.*, COALESCE(e.full_name, ul.email) AS submitter_display
            FROM incident_reports ir
            INNER JOIN user_login ul ON ul.id = ir.submitted_by_user_id
            LEFT JOIN employees e ON e.email = ul.email
            WHERE " . implode(' AND ', $parts) . '
            ORDER BY ir.created_at DESC
            LIMIT 500';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($types !== '') {
            $refs = [];
            $refs[] = &$types;
            foreach ($params as $k => $v) {
                $refs[] = &$params[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $reports[] = $row;
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
    <title>Incident Submitted by Employee - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Incident submitted by employee</h1>
                <p class="text-sm text-slate-500 mt-1">Review employee-submitted incidents here before they appear in the approved list.</p>
            </div>
            <a href="incident-report-list" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Go to List of incident</a>
        </div>

        <?php if (!$tableReady): ?>
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Run <code class="bg-white/80 px-1 rounded">database/setup_incident_reports_table.php</code> once, then refresh.
            </div>
        <?php endif; ?>

        <?php if ($flash !== ''): ?>
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <?php if ($tableReady): ?>
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Filters</h2>
                <form method="get" action="incident-report-submitted" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Review status</label>
                        <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25 bg-white">
                            <?php foreach ($allowedStatuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>"<?php echo $statusFilter === $status ? ' selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Employee name (on form)</label>
                        <input type="text" name="employee" value="<?php echo htmlspecialchars($employeeQ); ?>" placeholder="Search name..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Incident type</label>
                        <select name="incident_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25 bg-white">
                            <option value="">All types</option>
                            <?php foreach ($allowedTypes as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"<?php echo $typeFilter === $t ? ' selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-1 flex flex-wrap items-end gap-2">
                        <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Apply</button>
                        <a href="incident-report-submitted" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                    </div>
                </form>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Results</h2>
                    <span class="text-xs text-slate-500"><?php echo count($reports); ?> row(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">ID</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Incident</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Type</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee (form)</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Submitted by</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No reports match your filters.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reports as $r): ?>
                                    <?php $status = (string)($r['review_status'] ?? 'Pending'); ?>
                                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                                        <td class="px-4 py-3 font-mono text-slate-600"><?php echo (int)$r['id']; ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['incident_date']) ? htmlspecialchars(date('M j, Y', strtotime($r['incident_date']))) : '-'; ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['incident_type'] ?? '-'); ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['employee_name'] ?? '-'); ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['submitter_display'] ?? '-'); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Declined' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'); ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="incident-report-edit?id=<?php echo (int)$r['id']; ?>" class="inline-flex px-2.5 py-1 rounded bg-amber-600 text-white text-xs font-medium hover:bg-amber-700">Edit</a>
                                                <?php if ($status === 'Pending'): ?>
                                                    <form method="post" action="incident-report-action.php" class="inline" onsubmit="return confirm('Approve this incident?');">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                                        <button type="submit" class="inline-flex px-2.5 py-1 rounded border border-emerald-300 text-emerald-700 text-xs font-medium hover:bg-emerald-50">Approve</button>
                                                    </form>
                                                    <form method="post" action="incident-report-action.php" class="inline" onsubmit="return confirm('Decline this incident?');">
                                                        <input type="hidden" name="action" value="decline">
                                                        <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                                        <button type="submit" class="inline-flex px-2.5 py-1 rounded border border-red-300 text-red-700 text-xs font-medium hover:bg-red-50">Decline</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
