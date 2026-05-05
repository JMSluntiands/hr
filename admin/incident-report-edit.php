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

function ir_time_input(?string $t): string
{
    if ($t === null || $t === '') {
        return '';
    }
    $t = trim($t);
    if (preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) {
        return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
    }
    return substr($t, 0, 5);
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editRow = null;
if ($tableReady && $editId > 0) {
    $st = $conn->prepare('SELECT * FROM incident_reports WHERE id = ? LIMIT 1');
    if ($st) {
        $st->bind_param('i', $editId);
        $st->execute();
        $rs = $st->get_result();
        $editRow = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
}

if (!$editRow) {
    $_SESSION['incident_report_flash'] = 'Report not found.';
    header('Location: incident-report-list');
    exit;
}

$editRow['incident_time'] = ir_time_input($editRow['incident_time'] ?? '');
$editRow['report_time'] = ir_time_input($editRow['report_time'] ?? '');
$irRecord = $editRow;
$irMode = 'update';
$irSubmitLabel = 'Update report';
$irCancelHref = 'incident-report-list';
$irFormAction = 'incident-report-action.php';
$irExtraHiddenHtml = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Incident Report #<?php echo (int)$editId; ?> - Admin</title>
    <link rel="icon" type="image/png" href="../assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Edit incident report #<?php echo (int)$editId; ?></h1>
            <p class="text-sm text-slate-500 mt-1"><a href="incident-report-list" class="text-amber-700 hover:underline">← Back to list</a></p>
        </div>

        <?php if ($flash !== ''): ?>
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <section class="w-full min-w-0">
            <?php require __DIR__ . '/../include/incident-report-form.inc.php'; ?>
        </section>
    </main>

    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
