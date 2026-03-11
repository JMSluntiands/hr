<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

$inventoryUrl = '../inventory/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Module</title>
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
<body class="font-inter min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800] p-4">
    <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Welcome, Admin</h1>
        <p class="text-slate-600 mb-6">Choose which system you want to open.</p>

        <div class="space-y-3">
            <a
                href="index.php"
                class="block w-full text-left p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition"
            >
                <span class="block text-base font-semibold text-slate-800">HR Management</span>
                <span class="block text-sm text-slate-500">Employee records, leave requests, documents, and HR dashboard.</span>
            </a>

            <a
                href="../workforce/index.php"
                class="block w-full text-left p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition"
            >
                <span class="block text-base font-semibold text-slate-800">Workforce Management System</span>
                <span class="block text-sm text-slate-500">Staff, leaves, requests, compensation, and workforce dashboard.</span>
            </a>

            <a
                href="<?php echo htmlspecialchars($inventoryUrl, ENT_QUOTES, 'UTF-8'); ?>"
                class="block w-full text-left p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition"
            >
                <span class="block text-base font-semibold text-slate-800">Inventory Management</span>
                <span class="block text-sm text-slate-500">Open the inventory system for stocks and item tracking.</span>
            </a>
        </div>
    </div>
</body>
</html>
