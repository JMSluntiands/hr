<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/employee_data.php';
require_once __DIR__ . '/../database/incident_reports_schema.php';

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
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate"><?php echo htmlspecialchars($employeeName); ?></span>
                <span class="text-[11px] text-white/80">Employee</span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </header>

    <?php require_once __DIR__ . '/../include/sidebar-scrollbar-once.php'; ?>
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-2">
            <a href="index.php" data-url="index.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" data-url="profile.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>My Profile</span>
            </a>
            <a href="timeoff.php" data-url="timeoff.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span>My Leave Credits</span>
            </a>
            <a href="request.php" data-url="request.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <span>My Request</span>
            </a>
            <a href="compensation.php" data-url="compensation.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>My Compensation</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-my-inventory-nav.php'; ?>
            <a href="progressive-discipline.php" data-url="progressive-discipline.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
                <span>Progressive Discipline</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-incident-nav.php'; ?>
            <a href="settings.php" data-url="settings.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="shrink-0 border-t border-white/20 p-4">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
    </aside>

    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 overflow-y-auto">
        <div id="main-inner">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">List of incident reports</h1>
                    <p class="text-sm text-slate-500 mt-1">Your submitted reports. Use filters to narrow results.</p>
                </div>
                <a href="incident-report-add.php" class="inline-flex items-center rounded-xl bg-[#FA9800] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-amber-600/20 hover:bg-amber-600">Add incident</a>
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
                    <form method="get" action="incident-report-list.php" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Injured?</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Filed</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">File</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No reports match your filters.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $r): ?>
                                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($r['incident_date']) ? htmlspecialchars(date('M j, Y', strtotime($r['incident_date']))) : '—'; ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['incident_type'] ?? '—'); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['employee_name'] ?? '—'); ?></td>
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
    <script src="include/sidebar-employee.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();
          const pathOnly = (url || '').split('#')[0].split('?')[0];
          const irPages = ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'];
          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || pathOnly === 'inventory.php' || irPages.indexOf(pathOnly) !== -1 || url === 'index.php') {
            window.location.href = url;
            return;
          }
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });
      });
    </script>
</body>
</html>
