<?php
if (! defined('HR_LEGACY_EMBEDDED')) {
    session_start();
}

require_once __DIR__ . '/include/require_performance_review_access.php';
if (! hr_employee_is_performance_review_supervisor($conn, (int) $employeeDbId)) {
    header('Location: '.hr_performance_employee_url('performance-my-reviews.php', 'performance/my-reviews'));
    exit;
}

$newReviewFormUrl = hr_performance_employee_url('performance-form-review.php', 'performance/form-review');

$rows = [];
if ($conn && $employeeDbId) {
    $stmt = $conn->prepare(
        'SELECT id, review_date, staff_name, supervisor_name,
                accuracy_rating, cross_ref_rating, comprehension_rating,
                teamwork_support_rating, initiative_learning_rating, daily_output_rating,
                task_management_rating, communication_delays_rating,
                created_at
         FROM staff_performance_reviews WHERE employee_id = ? ORDER BY created_at DESC'
    );
    if ($stmt) {
        $stmt->bind_param('i', $employeeDbId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
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
    <title>Reviews you submitted — Performance</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] }, colors: { luntianBlue: '#FA9800' } } } };
    </script>
</head>
<body class="font-inter min-h-screen bg-gradient-to-b from-slate-100 via-[#f1f5f9] to-slate-100/90">
<?php include __DIR__ . '/include/performance-pages-body-shell.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:ml-64 md:p-8 md:pt-8">
        <div class="mx-auto max-w-5xl space-y-8">

        <header class="relative overflow-hidden rounded-3xl border border-white/60 bg-white p-6 shadow-xl shadow-slate-200/40 ring-1 ring-slate-200/50 sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-gradient-to-br from-amber-200/35 to-orange-200/15 blur-2xl"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700/80">Performance · List of review</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Reviews you submitted</h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-[15px]">
                        Performance review forms you completed while logged in. To see evaluations <strong class="text-slate-800">about you</strong>, open <strong class="text-slate-800">My performance review</strong> in the sidebar.
                    </p>
                </div>
                <a href="<?php echo htmlspecialchars($newReviewFormUrl, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#FA9800] to-orange-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:from-orange-500 hover:to-orange-700 focus:outline-none focus:ring-4 focus:ring-amber-400/40">
                    <svg class="h-5 w-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New review form
                </a>
            </div>
        </header>

        <section class="overflow-hidden rounded-3xl border border-dashed border-slate-200/90 bg-white/95 shadow-lg shadow-slate-200/30 ring-1 ring-slate-100/80">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50/90 to-white px-6 py-5 sm:px-8">
                <h2 class="text-lg font-bold text-slate-900">Submission history</h2>
                <p class="mt-1 text-sm text-slate-500">Newest first.</p>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($rows)): ?>
                    <div class="px-6 py-14 text-center sm:px-8">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No submissions yet</p>
                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Use <strong class="text-slate-600">New review form</strong> to add your first review.</p>
                    </div>
                <?php else: ?>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/95 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-3.5 sm:px-8">Date</th>
                                <th class="px-6 py-3.5 sm:px-8">Staff</th>
                                <th class="px-6 py-3.5 sm:px-8">Supervisor</th>
                                <th class="px-6 py-3.5 sm:px-8">Ratings (8×)</th>
                                <th class="px-6 py-3.5 sm:px-8">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($rows as $r): ?>
                                <tr class="transition-colors hover:bg-slate-50/90">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-800 tabular-nums sm:px-8"><?php echo htmlspecialchars($r['review_date'] ?? ''); ?></td>
                                    <td class="px-6 py-4 text-slate-800 sm:px-8"><?php echo htmlspecialchars($r['staff_name'] ?? ''); ?></td>
                                    <td class="px-6 py-4 text-slate-700 sm:px-8"><?php echo htmlspecialchars($r['supervisor_name'] ?? ''); ?></td>
                                    <td class="px-6 py-4 text-slate-600 sm:px-8 tabular-nums"><?php
                                    $keys = [
                                        'accuracy_rating', 'cross_ref_rating', 'comprehension_rating',
                                        'teamwork_support_rating', 'initiative_learning_rating', 'daily_output_rating',
                                        'task_management_rating', 'communication_delays_rating',
                                    ];
                                    $cells = [];
                                    foreach ($keys as $k) {
                                        $v = hr_performance_review_rating_or_null($r[$k] ?? null);
                                        $cells[] = $v !== null ? (string)$v : '—';
                                    }
                                    echo htmlspecialchars(implode(' / ', $cells));
                                    ?></td>
                                    <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap sm:px-8"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string)($r['created_at'] ?? 'now')))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>

        </div>
    </main>
    <script src="/assets/js/sidebar-dropdown.js"></script>
</body>
</html>
