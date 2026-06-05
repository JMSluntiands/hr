<?php
if (! defined('HR_LEGACY_EMBEDDED')) {
    session_start();
    if (! isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
} elseif (! isset($_SESSION['user_id']) || (int) ($_SESSION['user_id'] ?? 0) <= 0) {
    header('Location: '.(defined('HR_APP_URL') ? HR_APP_URL : '/'));
    exit;
}

require_once __DIR__ . '/../controller/session_timeout.php';

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/employee_data.php';
require_once __DIR__ . '/../database/incident_reports_schema.php';

$irEmbedded = defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED;
$irAppBase = defined('HR_APP_URL') ? rtrim(HR_APP_URL, '/') : '';
$irListHref = $irEmbedded && $irAppBase !== ''
    ? $irAppBase.'/employee/incident-reports'
    : 'incident-report-list.php';
$irCreateHref = $irEmbedded && $irAppBase !== ''
    ? $irAppBase.'/employee/incident-reports/create'
    : 'incident-report-add.php';

$tableReady = $conn && ensureIncidentReportsTable($conn);

$flash = $_SESSION['incident_report_flash'] ?? '';
unset($_SESSION['incident_report_flash']);

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$employeeQ = trim($_GET['employee'] ?? '');
$typeFilter = trim($_GET['incident_type'] ?? '');
$allowedTypes = incidentReportAllowedTypes();
if ($typeFilter !== '' && !in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = '';
}

$reports = [];
if ($tableReady) {
    $uid = (int)$_SESSION['user_id'];
    $parts = ['submitted_by_user_id = ?'];
    $types = 'i';
    $params = [$uid];

    if ($dateFrom !== '') {
        $parts[] = 'incident_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $parts[] = 'incident_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }
    if ($employeeQ !== '') {
        $parts[] = 'employee_name LIKE ?';
        $types .= 's';
        $params[] = '%' . $employeeQ . '%';
    }
    if ($typeFilter !== '') {
        $parts[] = 'incident_type = ?';
        $types .= 's';
        $params[] = $typeFilter;
    }

    $sql = 'SELECT * FROM incident_reports WHERE ' . implode(' AND ', $parts) . ' ORDER BY created_at DESC LIMIT 500';
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
    <title>List of Incident Reports</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: { luntianBlue: '#FA9800', luntianLight: '#f3f4ff' }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 overflow-y-auto">
        <div id="main-inner">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">List of incident reports</h1>
                    <p class="text-sm text-slate-500 mt-1">Your submitted reports and their review status.</p>
                </div>
                <a href="<?php echo htmlspecialchars($irCreateHref, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center rounded-xl bg-[#FA9800] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-amber-600/20 hover:bg-amber-600">Add incident</a>
            </div>

            <?php if (!$tableReady): ?>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Database table could not be verified. Run <code class="bg-white/80 px-1 rounded">database/setup_incident_reports_table.php</code> once, then refresh.
                </div>
            <?php endif; ?>

            <?php if ($flash !== ''): ?>
                <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700"><?php echo htmlspecialchars($flash); ?></div>
            <?php endif; ?>

            <?php if ($tableReady): ?>
                <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-800 mb-4">Filters</h2>
                    <form method="get" action="<?php echo htmlspecialchars($irListHref, ENT_QUOTES, 'UTF-8'); ?>" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Incident date from</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Incident date to</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Employee name (on form)</label>
                            <input type="text" name="employee" value="<?php echo htmlspecialchars($employeeQ); ?>" placeholder="Search name…" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
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
                        <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap gap-2">
                            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Apply filters</button>
                            <a href="incident-report-list.php" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
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
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Incident date</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Type</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee (form)</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Review status</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Injured?</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Filed</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">File</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No reports match your filters.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $r): ?>
                                        <?php $status = (string)($r['review_status'] ?? 'Pending'); ?>
                                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['incident_date']) ? htmlspecialchars(date('M j, Y', strtotime($r['incident_date']))) : '—'; ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['incident_type'] ?? '—'); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['employee_name'] ?? '—'); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Declined' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'); ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3"><?php echo htmlspecialchars($r['anyone_injured'] ?? 'No'); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['created_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($r['created_at']))) : '—'; ?></td>
                                            <td class="px-4 py-3">
                                                <?php if (!empty($r['attachment_path'])): ?>
                                                    <a class="text-amber-700 hover:underline" href="../<?php echo htmlspecialchars($r['attachment_path']); ?>" target="_blank" rel="noopener">View</a>
                                                <?php else: ?>
                                                    <span class="text-slate-400">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/sidebar-dropdown.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();
          const pathOnly = (url || '').split('#')[0].split('?')[0];
          const irPages = ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'];
          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'reimbursement.php' || url === 'request.php' || pathOnly === 'inventory.php' || ['performance.php', 'performance-my-reviews.php', 'performance-form-review.php', 'performance-review-received.php', 'performance-review-submissions.php'].indexOf(pathOnly) !== -1 || irPages.indexOf(pathOnly) !== -1 || url === 'index.php') {
            window.location.href = url;
            return;
          }
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });
      });
    </script>
</body>
</html>
