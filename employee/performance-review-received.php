<?php
session_start();

require_once __DIR__ . '/include/require_performance_review_access.php';

$reviewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nameKey = hr_performance_review_normalize_name((string)$employeeName);

$review = null;
if ($conn && $reviewId > 0 && $nameKey !== '') {
    $stmt = $conn->prepare(
        'SELECT id, review_date, staff_name, supervisor_name,
                accuracy_rating, accuracy_explanation, cross_ref_rating, cross_ref_explanation,
                comprehension_rating, comprehension_explanation,
                teamwork_support_rating, teamwork_support_explanation,
                initiative_learning_rating, initiative_learning_explanation,
                daily_output_rating, daily_output_explanation,
                task_management_rating, task_management_explanation,
                communication_delays_rating, communication_delays_explanation,
                created_at
         FROM staff_performance_reviews WHERE id = ? LIMIT 1'
    );
    if ($stmt) {
        $stmt->bind_param('i', $reviewId);
        $stmt->execute();
        $res = $stmt->get_result();
        $review = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
}

if (!$review || hr_performance_review_normalize_name((string)($review['staff_name'] ?? '')) !== $nameKey) {
    header('Location: performance-my-reviews.php');
    exit;
}

$competencyBlocks = [
    ['Accuracy in task execution', 'accuracy_rating', 'accuracy_explanation', 'Accuracy'],
    ['Cross-referencing resources', 'cross_ref_rating', 'cross_ref_explanation', 'Cross-ref'],
    ['Comprehension of instructions', 'comprehension_rating', 'comprehension_explanation', 'Comprehension'],
    ['Teamwork and support', 'teamwork_support_rating', 'teamwork_support_explanation', 'Teamwork'],
    ['Initiative to learn and ask meaningful questions', 'initiative_learning_rating', 'initiative_learning_explanation', 'Learning'],
    ['Meeting daily output expectations', 'daily_output_rating', 'daily_output_explanation', 'Daily output'],
    ['Task management and allocation', 'task_management_rating', 'task_management_explanation', 'Task mgmt'],
    ['Communication of delays or challenges', 'communication_delays_rating', 'communication_delays_explanation', 'Communication'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review detail — Performance</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] }, colors: { luntianBlue: '#FA9800' } } } };
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
<?php include __DIR__ . '/include/performance-pages-body-shell.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:ml-64 md:p-8 md:pt-8">
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <a href="performance-my-reviews.php" class="inline-flex items-center gap-2 text-sm font-semibold text-amber-800 hover:text-amber-950 hover:underline">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to My Performance Review
                </a>
            </div>

            <header class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-md">
                <p class="text-xs font-bold uppercase tracking-widest text-amber-700/90">Review about you</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">Performance review</h1>
                <p class="mt-2 text-sm text-slate-600">
                    <span class="font-medium text-slate-800">Supervisor:</span> <?php echo htmlspecialchars($review['supervisor_name'] ?? ''); ?>
                    <span class="mx-2 text-slate-300">·</span>
                    <span class="font-medium text-slate-800">Review date:</span> <?php echo htmlspecialchars($review['review_date'] ?? ''); ?>
                </p>
                <p class="mt-1 text-xs text-slate-500">Recorded <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string)($review['created_at'] ?? 'now')))); ?></p>
            </header>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-md space-y-6">
                <div class="flex flex-wrap gap-2 text-sm">
                    <?php foreach ($competencyBlocks as $b):
                        $rk = hr_performance_review_rating_or_null($review[$b[1]] ?? null);
                        ?>
                        <?php if ($rk !== null): ?>
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-900 ring-1 ring-amber-200/80"><?php echo htmlspecialchars($b[3]); ?>: <?php echo $rk; ?>/5</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-500 ring-1 ring-slate-200/80" title="Not recorded on this review"><?php echo htmlspecialchars($b[3]); ?>: —</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="space-y-5 text-sm text-slate-700">
                    <?php foreach ($competencyBlocks as $b):
                        $rk = hr_performance_review_rating_or_null($review[$b[1]] ?? null);
                        $ex = trim((string)($review[$b[2]] ?? ''));
                        ?>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500"><?php echo htmlspecialchars($b[0]); ?><?php echo $rk !== null ? ' — ' . $rk . '/5' : ''; ?></p>
                            <?php if ($ex !== ''): ?>
                                <p class="mt-2 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($ex); ?></p>
                            <?php elseif ($rk !== null): ?>
                                <p class="mt-2 text-slate-400 italic">No explanation text stored.</p>
                            <?php else: ?>
                                <p class="mt-2 text-slate-400 italic">This area was not part of the review form when this record was submitted.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="include/sidebar-employee.js"></script>
</body>
</html>
