<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

$_SESSION['admin_module'] = 'workforce';
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';
include __DIR__ . '/../database/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - Workforce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-workforce.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Payslip</h1>
            <p class="text-sm text-slate-500 mt-1">Workforce Management System – payslips and payroll</p>
        </div>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold uppercase tracking-wide mb-4">
                Coming Soon
            </div>
            <p class="text-sm text-slate-600">
                Payslip management and payroll reports will be available here.
            </p>
        </section>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
