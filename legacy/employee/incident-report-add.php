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
require_once __DIR__ . '/../include/datetime_helpers.php';

$tableReady = $conn && ensureIncidentReportsTable($conn);

$flash = $_SESSION['incident_report_flash'] ?? '';
unset($_SESSION['incident_report_flash']);

$irRecord = [
    'company_name' => 'Luntian',
    'employee_name' => $employeeName,
    'report_date' => hr_today_ymd(),
    'report_time' => date('H:i'),
];
$irEmbedded = defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED;
$irAppBase = defined('HR_APP_URL') ? rtrim(HR_APP_URL, '/') : '';
$irFormAction = $irEmbedded && $irAppBase !== ''
    ? $irAppBase.'/employee/incident-report-action.php'
    : 'incident-report-action.php';
$irMode = 'create';
$irSubmitLabel = 'Save report';
$irCancelHref = $irEmbedded && $irAppBase !== ''
    ? $irAppBase.'/employee/incident-reports'
    : 'incident-report-list.php';
$irListHref = $irCancelHref;
$irExtraHiddenHtml = '';
if ($irEmbedded && function_exists('csrf_token')) {
    $irExtraHiddenHtml = '<input type="hidden" name="_token" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'">';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Incident Report</title>
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
                    <h1 class="text-2xl font-semibold text-slate-800">Add incident report</h1>
                    <p class="text-sm text-slate-500 mt-1">Fill out the form below. You can review submitted reports under List.</p>
                </div>
                <a href="<?php echo htmlspecialchars($irListHref, ENT_QUOTES, 'UTF-8'); ?>" class="text-sm font-medium text-[#FA9800] hover:text-amber-700">List of incident →</a>
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
                <section class="w-full min-w-0">
                    <?php require __DIR__ . '/../include/incident-report-form.inc.php'; ?>
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
