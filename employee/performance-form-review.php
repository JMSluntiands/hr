<?php
session_start();

require_once __DIR__ . '/include/require_performance_review_access.php';

if (!hr_employee_is_performance_review_supervisor($conn, $employeeDbId)) {
    $_SESSION['performance_form_review_denied'] = '1';
    header('Location: performance-my-reviews.php');
    exit;
}

$formError = '';
$formSuccess = isset($_GET['saved']) ? 'Your performance review was submitted.' : '';
$postResult = hr_performance_review_handle_post($conn, $employeeDbId, (string)$department, 'performance-form-review.php?saved=1');
if ($postResult !== null) {
    $formError = $postResult;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Review — Performance</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] }, colors: { luntianBlue: '#FA9800' } } } };
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
<?php include __DIR__ . '/include/performance-pages-body-shell.php'; ?>

    <main class="min-h-screen overflow-y-auto bg-gradient-to-b from-slate-100 via-[#f1f5f9] to-slate-100/90 p-4 pt-16 md:ml-64 md:p-8 md:pt-8">
        <div class="mx-auto max-w-5xl space-y-8">
        <header class="relative overflow-hidden rounded-3xl border border-white/60 bg-white p-6 shadow-xl shadow-slate-200/40 ring-1 ring-slate-200/50 sm:p-8">
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-gradient-to-br from-amber-200/40 to-orange-200/20 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-8 left-1/3 h-32 w-64 rounded-full bg-amber-100/30 blur-2xl"></div>
            <div class="relative">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700/80">Performance</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Form review</h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-[15px]">
                    Complete a structured review below. <strong class="font-semibold text-slate-800">Supervisor</strong> is pre-filled with your account name—adjust if needed.
                </p>
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
        $formAction = 'performance-form-review.php';
        $defaultStaffName = '';
        $defaultSupervisorName = $employeeName;
        include __DIR__ . '/include/performance-review-form.inc.php';
        ?>

        </div>
    </main>
    <script src="include/sidebar-employee.js"></script>
</body>
</html>
