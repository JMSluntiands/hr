<?php
if (! defined('HR_LEGACY_EMBEDDED')) {
    session_start();
}

require_once __DIR__ . '/include/require_performance_review_access.php';

$newReviewFormUrl = hr_performance_employee_url('performance.php', 'performance');

$formReviewDeniedNote = '';
if (!empty($_SESSION['performance_form_review_denied'])) {
    unset($_SESSION['performance_form_review_denied']);
    $formReviewDeniedNote = 'Form Review is only available for accounts marked as a performance supervisor in HR. Use “New review form” below to submit your own review, or contact HR if you should have supervisor access.';
}

$nameKey = hr_performance_review_normalize_name((string)$employeeName);

$receivedRows = [];
if ($conn && $nameKey !== '') {
    $stmt = $conn->prepare(
        'SELECT id, review_date, supervisor_name
         FROM staff_performance_reviews
         WHERE LOWER(TRIM(staff_name)) = ?
         ORDER BY review_date DESC, created_at DESC'
    );
    if ($stmt) {
        $stmt->bind_param('s', $nameKey);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $receivedRows[] = $row;
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
    <title>My Performance Review</title>
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

        <?php if ($formReviewDeniedNote !== ''): ?>
            <div class="flex gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/90 px-4 py-4 text-sm text-amber-900 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-200/60 text-amber-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div class="min-w-0 pt-0.5 font-medium leading-relaxed"><?php echo htmlspecialchars($formReviewDeniedNote); ?></div>
            </div>
        <?php endif; ?>

        <header class="relative overflow-hidden rounded-3xl border border-white/60 bg-white p-6 shadow-xl shadow-slate-200/40 ring-1 ring-slate-200/50 sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-gradient-to-br from-amber-200/40 to-orange-200/20 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-10 left-1/4 h-28 w-56 rounded-full bg-amber-100/35 blur-2xl"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700/80">Performance</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">My performance review</h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-[15px]">
                        Evaluations <strong class="font-semibold text-slate-800">about you</strong> appear when your name matches <strong class="font-semibold text-slate-800">Name of staff</strong> on a submitted form.
                        Your profile name: <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-medium text-slate-800"><?php echo htmlspecialchars($employeeName); ?></span>
                    </p>
                    <?php if (hr_employee_is_performance_review_supervisor($conn, $employeeDbId)): ?>
                        <p class="mt-2 text-sm text-slate-600">Supervisors: use <strong class="text-slate-800">Form Review</strong> in the sidebar to evaluate your team.</p>
                    <?php endif; ?>
                    <?php if (hr_employee_is_performance_review_supervisor($conn, $employeeDbId)): ?>
                        <p class="mt-2 text-sm text-slate-600">Forms you submitted are listed under <strong class="text-slate-800">Performance → Reviews Submitted</strong>.</p>
                    <?php endif; ?>
                </div>
                <a href="<?php echo htmlspecialchars($newReviewFormUrl, ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#FA9800] to-orange-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:from-orange-500 hover:to-orange-700 hover:shadow-xl hover:shadow-amber-500/30 focus:outline-none focus:ring-4 focus:ring-amber-400/40">
                    <svg class="h-5 w-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New review form
                </a>
            </div>
        </header>

        <!-- Primary: reviews you received -->
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/35 ring-1 ring-slate-100/80">
            <div class="h-1 bg-gradient-to-r from-amber-400 via-[#FA9800] to-orange-500"></div>
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50/90 to-white px-6 py-5 sm:px-8">
                <h2 class="text-lg font-bold text-slate-900">Reviews about you</h2>
                <p class="mt-1 text-sm text-slate-500">Supervisor evaluations where you are the staff member reviewed.</p>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($receivedRows)): ?>
                    <div class="px-6 py-14 text-center sm:px-8">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No reviews yet</p>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-500">When someone submits a review with your name as <strong class="text-slate-600">staff</strong>, it will show in this table.</p>
                    </div>
                <?php else: ?>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/95 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-3.5 sm:px-8">Supervisor name</th>
                                <th class="px-6 py-3.5 sm:px-8">Date review</th>
                                <th class="px-6 py-3.5 sm:px-8 text-right sm:text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($receivedRows as $rr): ?>
                                <tr class="transition-colors hover:bg-amber-50/40">
                                    <td class="px-6 py-4 font-medium text-slate-900 sm:px-8"><?php echo htmlspecialchars($rr['supervisor_name'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-600 tabular-nums sm:px-8"><?php echo htmlspecialchars($rr['review_date'] ?? ''); ?></td>
                                    <td class="px-6 py-4 sm:px-8">
                                        <a href="<?php echo htmlspecialchars(hr_performance_employee_url('performance-review-received.php', 'performance-review-received.php', 'id='.(int) ($rr['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200/90 bg-amber-50/80 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-amber-900 transition hover:border-amber-300 hover:bg-amber-100">
                                            View
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </td>
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
