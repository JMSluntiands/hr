<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workforce Dashboard</title>
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
            <h1 class="text-2xl font-semibold text-slate-800">Workforce Dashboard</h1>
            <p class="text-sm text-slate-500 mt-1">Use the sidebar to open Time Keeping, Payslip, or Report.</p>
        </div>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
