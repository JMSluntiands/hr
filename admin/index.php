<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

// Dummy values – palitan mo na lang pag may real data
$totalEmployees   = 24;
$openRequests     = 5;
$pendingApprovals = 3;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#2563eb',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-[#1d4ed8] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-blue-500/40">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/10 flex items-center justify-center">
                <span class="text-2xl font-semibold">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-semibold text-sm"><?php echo htmlspecialchars($adminName); ?></div>
                <div class="text-xs text-blue-100">Administrator</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2 text-sm">
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white/10 font-medium">
                <span>Dashboard</span>
            </a>
            <a href="staff" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>Employees</span>
            </a>
            <a href="job" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>Job Requests</span>
            </a>
            <a href="announcement" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10">
                <span>Announcements</span>
            </a>
        </nav>
        <div class="p-4 border-t border-blue-500/40">
            <div class="flex items-center justify-between text-xs text-blue-100 mb-2">
                <span>Role</span>
                <span class="px-2 py-0.5 rounded-full bg-white/10">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
            <a href="../logout.php" class="block text-xs text-blue-100 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <!-- Top Bar -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">
                Welcome back, <?php echo htmlspecialchars(explode(' ', $adminName)[0]); ?>!
            </h1>
            <div class="flex items-center gap-3">
                <button class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-slate-500">
                    &#128276;
                </button>
                <button class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-slate-500">
                    &#9881;
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Total Employees -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Total Employees</h2>
                <p class="text-3xl font-semibold text-slate-900 mb-1">
                    <?php echo (int)$totalEmployees; ?>
                </p>
                <p class="text-xs text-slate-500">Active employees in the system</p>
            </section>

            <!-- Open Requests -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Open Requests</h2>
                <p class="text-3xl font-semibold text-amber-500 mb-1">
                    <?php echo (int)$openRequests; ?>
                </p>
                <p class="text-xs text-slate-500">Leave and job requests awaiting review</p>
            </section>

            <!-- Pending Approvals -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Pending Approvals</h2>
                <p class="text-3xl font-semibold text-emerald-600 mb-1">
                    <?php echo (int)$pendingApprovals; ?>
                </p>
                <p class="text-xs text-slate-500">Items that need your decision</p>
            </section>
        </div>

        <!-- Recent Activity -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Recent Activity</h2>
            </div>
            <div class="p-6 text-sm text-slate-600 space-y-3">
                <div class="flex items-center justify-between">
                    <span>New leave request submitted</span>
                    <span class="text-xs text-slate-400">2 mins ago</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>3 employees added to IT Department</span>
                    <span class="text-xs text-slate-400">1 hour ago</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Announcement "Holiday Schedule" published</span>
                    <span class="text-xs text-slate-400">Yesterday</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>

