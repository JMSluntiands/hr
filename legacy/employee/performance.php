<?php
if (! defined('HR_LEGACY_EMBEDDED')) {
    session_start();
}

require_once __DIR__ . '/include/require_performance_review_access.php';
$isPerformanceSupervisor = hr_employee_is_performance_review_supervisor($conn, (int) $employeeDbId);

$formError = '';
$formSuccess = isset($_GET['saved']) ? 'Your performance review was submitted.' : '';
$postResult = hr_performance_review_handle_post(
    $conn,
    $employeeDbId,
    (string) $department,
    hr_performance_employee_url('performance.php', 'performance', 'saved=1')
);
if ($postResult !== null) {
    $formError = $postResult;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self Performance Review</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#FA9800',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
<?php include __DIR__ . '/include/performance-pages-body-shell.php'; ?>

    <main class="min-h-screen overflow-y-auto bg-gradient-to-b from-slate-100 via-[#f1f5f9] to-slate-100/90 p-4 pt-16 md:ml-64 md:p-8 md:pt-8">
        <div class="mx-auto max-w-4xl space-y-8">
        <header class="relative overflow-hidden rounded-3xl border border-white/60 bg-white p-6 shadow-xl shadow-slate-200/40 ring-1 ring-slate-200/50 sm:p-8">
            <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-gradient-to-br from-amber-200/35 to-transparent blur-2xl"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700/80">Performance</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Self performance review</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-[15px]">
                        Complete your self-assessment across <strong class="text-slate-800">eight</strong> competency areas, from <strong class="text-slate-800">1</strong> (needs improvement) to <strong class="text-slate-800">5</strong> (excellent). Add a short explanation for each area.
                    </p>
                </div>
                <?php if ($isPerformanceSupervisor): ?>
                <a href="<?php echo htmlspecialchars(hr_performance_employee_url('performance-review-submissions.php', 'performance/submissions'), ENT_QUOTES, 'UTF-8'); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-amber-200/90 bg-gradient-to-br from-amber-50 to-white px-4 py-3 text-sm font-semibold text-amber-900 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Reviews submitted
                </a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($formSuccess): ?>
            <div class="flex gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-4 text-sm text-emerald-900 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-200/60 text-emerald-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div class="min-w-0 pt-0.5 font-medium"><?php echo htmlspecialchars($formSuccess); ?></div>
            </div>
        <?php endif; ?>
        <?php if ($formError !== ''): ?>
            <div class="flex gap-3 rounded-2xl border border-rose-200/80 bg-rose-50/90 px-4 py-4 text-sm text-rose-900 shadow-sm">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-200/60 text-rose-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                </span>
                <div class="min-w-0 pt-0.5 font-medium"><?php echo htmlspecialchars($formError); ?></div>
            </div>
        <?php endif; ?>

        <?php
        $formAction = hr_performance_employee_url('performance.php', 'performance.php');
        $defaultStaffName = $employeeName;
        $defaultStaffEmployeeId = $employeeDbId;
        $defaultSupervisorName = $employeeName;
        $selfReviewMode = true;
        include __DIR__ . '/include/performance-review-form.inc.php';
        ?>
        </div>
    </main>
    <script src="/assets/js/sidebar-dropdown.js"></script>
</body>
</html>
