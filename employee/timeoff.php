<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$employeeName = $_SESSION['name'] ?? 'Juan Dela Cruz';
$position     = $_SESSION['position'] ?? 'Software Engineer';
$department   = $_SESSION['department'] ?? 'IT Department';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Time Off</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#FA9800',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <!-- Sidebar (fixed) -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#d97706] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white">
                    <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <!-- Dashboard -->
            <a href="index.php"
               data-url="index.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- My Profile -->
            <a href="profile.php"
               data-url="profile.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>My Profile</span>
            </a>
            <!-- My Time Off -->
            <a href="timeoff.php"
               data-url="timeoff.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>My Time Off</span>
            </a>
            <!-- My Request -->
            <a href="request.php"
               data-url="request.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>My Request</span>
            </a>
            <!-- Settings -->
            <a href="settings.php"
               data-url="settings.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Time Off</h1>
                <p class="text-sm text-slate-500 mt-1">
                    View your Sick Leave (SL) and Vacation Leave (VL) balances and history.
                </p>
            </div>
            <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                <span><?php echo htmlspecialchars($department); ?></span>
                <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                <span><?php echo htmlspecialchars($position); ?></span>
            </div>
        </div>

        <!-- Summary Cards (SL / VL) -->
        <div class="flex items-center justify-between mb-3">
            <div></div>
            <button class="inline-flex items-center px-4 py-2 rounded-lg bg-[#FA9800] text-white text-xs font-medium shadow-sm hover:bg-[#d18a15]">
                + Add New Request
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sick Leave -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Sick Leave (SL)</h2>
                <div class="grid grid-cols-3 gap-4 text-sm text-slate-600">
                    <div>
                        <p class="text-slate-500">Total</p>
                        <p class="text-xl font-semibold text-slate-900">10</p>
                        <p class="text-xs text-slate-400">days / year</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Used</p>
                        <p class="text-xl font-semibold text-amber-600">3</p>
                        <p class="text-xs text-slate-400">approved</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Remaining</p>
                        <p class="text-xl font-semibold text-emerald-600">7</p>
                        <p class="text-xs text-slate-400">available</p>
                    </div>
                </div>
            </section>

            <!-- Vacation Leave -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Vacation Leave (VL)</h2>
                <div class="grid grid-cols-3 gap-4 text-sm text-slate-600">
                    <div>
                        <p class="text-slate-500">Total</p>
                        <p class="text-xl font-semibold text-slate-900">15</p>
                        <p class="text-xs text-slate-400">days / year</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Used</p>
                        <p class="text-xl font-semibold text-amber-600">5</p>
                        <p class="text-xs text-slate-400">approved</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Remaining</p>
                        <p class="text-xl font-semibold text-emerald-600">10</p>
                        <p class="text-xs text-slate-400">available</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Usage History -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 mt-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Leave Usage History</h2>
                <div class="flex flex-wrap gap-3 text-xs">
                    <select id="usageTypeFilter" class="border border-slate-200 rounded-lg px-2 py-1 text-slate-600">
                        <option value="all">All Types</option>
                        <option value="VL">Vacation Leave</option>
                        <option value="SL">Sick Leave</option>
                    </select>
                    <select id="usageYearFilter" class="border border-slate-200 rounded-lg px-2 py-1 text-slate-600">
                        <option value="all">All Years</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                    </select>
                </div>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="usageTable" class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Days</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr data-type="VL" data-year="2024">
                            <td class="px-4 py-2 text-slate-700">Jan 10, 2024</td>
                            <td class="px-4 py-2 text-slate-700">Vacation Leave</td>
                            <td class="px-4 py-2 text-slate-700">2</td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Family trip</td>
                        </tr>
                        <tr data-type="SL" data-year="2024">
                            <td class="px-4 py-2 text-slate-700">Feb 02, 2024</td>
                            <td class="px-4 py-2 text-slate-700">Sick Leave</td>
                            <td class="px-4 py-2 text-slate-700">1</td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Flu</td>
                        </tr>
                        <tr data-type="VL" data-year="2023">
                            <td class="px-4 py-2 text-slate-700">Dec 20, 2023</td>
                            <td class="px-4 py-2 text-slate-700">Vacation Leave</td>
                            <td class="px-4 py-2 text-slate-700">3</td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Holiday break</td>
                        </tr>
                        <tr data-type="SL" data-year="2023">
                            <td class="px-4 py-2 text-slate-700">Aug 15, 2023</td>
                            <td class="px-4 py-2 text-slate-700">Sick Leave</td>
                            <td class="px-4 py-2 text-slate-700">1</td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Check-up</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Requests Table -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 mt-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Leave Requests</h2>
                <div class="flex flex-wrap gap-3 text-xs">
                    <select id="requestStatusFilter" class="border border-slate-200 rounded-lg px-2 py-1 text-slate-600">
                        <option value="all">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <select id="requestTypeFilter" class="border border-slate-200 rounded-lg px-2 py-1 text-slate-600">
                        <option value="all">All Types</option>
                        <option value="VL">Vacation Leave</option>
                        <option value="SL">Sick Leave</option>
                    </select>
                </div>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="requestTable" class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date Filed</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Dates</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Days</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr data-type="VL" data-status="Pending">
                            <td class="px-4 py-2 text-slate-700">Mar 20, 2024</td>
                            <td class="px-4 py-2 text-slate-700">Vacation Leave</td>
                            <td class="px-4 py-2 text-slate-700">Apr 01–03, 2024</td>
                            <td class="px-4 py-2 text-slate-700">3</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                    Pending
                                </span>
                            </td>
                        </tr>
                        <tr data-type="SL" data-status="Approved">
                            <td class="px-4 py-2 text-slate-700">Feb 01, 2024</td>
                            <td class="px-4 py-2 text-slate-700">Sick Leave</td>
                            <td class="px-4 py-2 text-slate-700">Feb 02, 2024</td>
                            <td class="px-4 py-2 text-slate-700">1</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    Approved
                                </span>
                            </td>
                        </tr>
                        <tr data-type="VL" data-status="Approved">
                            <td class="px-4 py-2 text-slate-700">Jan 05, 2024</td>
                            <td class="px-4 py-2 text-slate-700">Vacation Leave</td>
                            <td class="px-4 py-2 text-slate-700">Jan 10–11, 2024</td>
                            <td class="px-4 py-2 text-slate-700">2</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    Approved
                                </span>
                            </td>
                        </tr>
                        <tr data-type="SL" data-status="Cancelled">
                            <td class="px-4 py-2 text-slate-700">Nov 10, 2023</td>
                            <td class="px-4 py-2 text-slate-700">Sick Leave</td>
                            <td class="px-4 py-2 text-slate-700">Nov 12, 2023</td>
                            <td class="px-4 py-2 text-slate-700">1</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    Cancelled
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          // Load only the right content (same behavior as dashboard)
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Local filter behavior when page is loaded directly (no AJAX shell)
        $('#usageTypeFilter, #usageYearFilter').on('change', function () {
          const type = $('#usageTypeFilter').val();
          const year = $('#usageYearFilter').val();

          $('#usageTable tbody tr').each(function () {
            const rowType = $(this).data('type');
            const rowYear = String($(this).data('year'));
            const typeOk = type === 'all' || type === rowType;
            const yearOk = year === 'all' || year === rowYear;
            $(this).toggle(typeOk && yearOk);
          });
        });

        $('#requestStatusFilter, #requestTypeFilter').on('change', function () {
          const status = $('#requestStatusFilter').val();
          const type = $('#requestTypeFilter').val();

          $('#requestTable tbody tr').each(function () {
            const rowStatus = $(this).data('status');
            const rowType = $(this).data('type');
            const statusOk = status === 'all' || status === rowStatus;
            const typeOk = type === 'all' || type === rowType;
            $(this).toggle(statusOk && typeOk);
          });
        });
      });
    </script>
</body>
</html>

