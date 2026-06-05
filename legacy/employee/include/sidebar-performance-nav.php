<?php
if (! isset($conn) || ! $conn) {
    require_once __DIR__ . '/../../database/db.php';
}
if (! isset($employeeDbId)) {
    include __DIR__ . '/employee_data.php';
}
if (! isset($department)) {
    $department = '';
}

require_once __DIR__ . '/performance_review_helpers.php';

$performanceReviewEligible = hr_department_performance_review_enabled($conn, (string) $department);
if (! $performanceReviewEligible) {
    return;
}

$performanceReviewSupervisorNav = hr_employee_is_performance_review_supervisor($conn, (int) ($employeeDbId ?? 0));

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$currentPerfNav = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '', '.php');
if ($currentPerfNav === '' || $currentPerfNav === 'index') {
    if (str_contains($requestPath, 'performance/form-review') || str_contains($requestPath, 'performance-form-review')) {
        $currentPerfNav = 'performance-form-review';
    } elseif (str_contains($requestPath, 'performance/submissions') || str_contains($requestPath, 'performance-review-submissions')) {
        $currentPerfNav = 'performance-review-submissions';
    } elseif (str_contains($requestPath, 'performance/my-reviews') || str_contains($requestPath, 'performance-my-reviews') || str_contains($requestPath, 'performance-review-received')) {
        $currentPerfNav = 'performance-my-reviews';
    } elseif (str_contains($requestPath, '/employee/performance') && ! str_contains($requestPath, 'form-review') && ! str_contains($requestPath, 'submissions') && ! str_contains($requestPath, 'my-reviews')) {
        $currentPerfNav = 'performance';
    }
}

$perfPages = ['performance', 'performance-my-reviews', 'performance-review-received'];
if ($performanceReviewSupervisorNav) {
    $perfPages[] = 'performance-form-review';
    $perfPages[] = 'performance-review-submissions';
}
$isPerfSection = in_array($currentPerfNav, $perfPages, true);
$perfNavOpen = $isPerfSection ? '' : ' hidden';
$perfNavArrow = $isPerfSection ? ' rotate-180' : '';
$perfNavBtnActive = $isPerfSection ? ' bg-white/20' : '';
$isPerfFormReview = ($currentPerfNav === 'performance-form-review');
$isPerfMyReviews = ($currentPerfNav === 'performance-my-reviews' || $currentPerfNav === 'performance-review-received');
$isPerfSubmissions = ($currentPerfNav === 'performance-review-submissions');
$isPerfSelf = ($currentPerfNav === 'performance');

$irEmbedded = defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED;
$irAppBase = defined('HR_APP_URL') ? rtrim(HR_APP_URL, '/') : '';
$perfHref = static function (string $legacyFile, string $laravelPath) use ($irEmbedded, $irAppBase): string {
    if ($irEmbedded && $irAppBase !== '') {
        return $irAppBase.'/employee/'.$laravelPath;
    }

    return $legacyFile;
};
?>
<div class="dropdown-container relative z-10">
    <button type="button" id="employee-perf-dropdown-btn" class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-white transition-colors hover:bg-white/10<?php echo $perfNavBtnActive; ?>" aria-expanded="<?php echo $perfNavOpen === '' ? 'true' : 'false'; ?>" aria-controls="employee-perf-dropdown">
        <svg class="h-5 w-5 shrink-0 text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <span class="min-w-0 flex-1 pointer-events-none">Performance</span>
        <svg id="employee-perf-arrow" class="h-4 w-4 shrink-0 text-white transition-transform pointer-events-none<?php echo $perfNavArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div id="employee-perf-dropdown" class="mb-2 ml-10 space-y-1<?php echo $perfNavOpen; ?>" role="region" aria-label="Performance submenu">
        <?php if ($performanceReviewSupervisorNav): ?>
        <a href="<?php echo htmlspecialchars($perfHref('performance-form-review.php', 'performance/form-review'), ENT_QUOTES, 'UTF-8'); ?>" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isPerfFormReview ? ' bg-white/20' : ''; ?>">Form Review</a>
        <a href="<?php echo htmlspecialchars($perfHref('performance-review-submissions.php', 'performance/submissions'), ENT_QUOTES, 'UTF-8'); ?>" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isPerfSubmissions ? ' bg-white/20' : ''; ?>">Reviews Submitted</a>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($perfHref('performance.php', 'performance'), ENT_QUOTES, 'UTF-8'); ?>" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isPerfSelf ? ' bg-white/20' : ''; ?>">Self Performance Review</a>
        <a href="<?php echo htmlspecialchars($perfHref('performance-my-reviews.php', 'performance/my-reviews'), ENT_QUOTES, 'UTF-8'); ?>" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isPerfMyReviews ? ' bg-white/20' : ''; ?>">My Performance Review</a>
    </div>
</div>
