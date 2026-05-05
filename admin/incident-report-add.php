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

$irRecord = [
    'report_date' => date('Y-m-d'),
    'report_time' => date('H:i'),
];
$irFormAction = 'incident-report-action.php';
$irMode = 'create';
$irSubmitLabel = 'Save report';
$irCancelHref = 'incident-report-list';
$irExtraHiddenHtml = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Incident Report - Admin</title>
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
                <h1 class="text-2xl font-semibold text-slate-800">Add incident report</h1>
                <p class="text-sm text-slate-500 mt-1">Create a new report on behalf of the organization.</p>
            </div>
            <a href="incident-report-list" class="text-sm font-medium text-[#FA9800] hover:text-amber-700">List of incident →</a>
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
            <section class="w-full min-w-0">
                <?php require __DIR__ . '/../include/incident-report-form.inc.php'; ?>
            </section>
        <?php endif; ?>
    </main>

    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
