<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$employeeName = $_SESSION['name'] ?? 'Juan Dela Cruz';
$position     = $_SESSION['position'] ?? 'Software Engineer';
$department   = $_SESSION['department'] ?? 'IT Department';
$hireDate     = $_SESSION['hire_date'] ?? 'Jan 15, 2020';

// Dummy values for now – you can replace with DB values
$remainingLeave = 8;
$usedLeave      = 12;
$pendingCount   = 1;

// Recent requests sample data
$recentRequests = [
    ['date' => 'March 10, 2022', 'type' => 'Vacation Leave', 'status' => 'Approved'],
    ['date' => 'April 5, 2022',  'type' => 'Sick Leave',     'status' => 'Declined'],
    ['date' => 'April 18, 2022', 'type' => 'Leave Request',  'status' => 'Pending'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
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
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <!-- Sidebar (fixed) -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#1d4ed8] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-blue-500/40">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/10 flex items-center justify-center">
                <span class="text-2xl font-semibold">
                    <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-semibold text-sm"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-blue-100">Employee</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <!-- Dashboard -->
            <a href="index.php"
               data-url="index.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>Dashboard</span>
            </a>
            <!-- My Profile -->
            <a href="profile.php"
               data-url="profile.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>My Profile</span>
            </a>
            <!-- My Time Off -->
            <a href="timeoff.php"
               data-url="timeoff.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>My Time Off</span>
            </a>
            <!-- My Request -->
            <a href="request.php"
               data-url="request.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>My Request</span>
            </a>
        </nav>
        <div class="p-4 border-t border-blue-500/40">
            <a href="../logout.php" class="block text-xs text-blue-100 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content (scrollable only on the right side) -->
    <main class="ml-64 min-h-screen p-8 overflow-y-auto">
        <div id="main-inner">
        <!-- Top Bar -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">
                Welcome back, <?php echo htmlspecialchars(explode(' ', $employeeName)[0]); ?>!
            </h1>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Profile Overview -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4">Profile Overview</h2>
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Position:</span>
                        <span class="font-medium text-slate-800"><?php echo htmlspecialchars($position); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Department:</span>
                        <span class="font-medium text-slate-800"><?php echo htmlspecialchars($department); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Hire Date:</span>
                        <span class="font-medium text-slate-800"><?php echo htmlspecialchars($hireDate); ?></span>
                    </div>
                </div>
            </section>

            <!-- Time Off Summary -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4">Time Off Summary</h2>
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Remaining Leave:</span>
                        <span class="font-semibold text-emerald-600">
                            <?php echo (int)$remainingLeave; ?> Days
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Used Leave:</span>
                        <span class="font-semibold text-sky-600">
                            <?php echo (int)$usedLeave; ?> Days
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Pending Requests:</span>
                        <span class="font-semibold text-amber-500">
                            <?php echo (int)$pendingCount; ?>
                        </span>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between">
                <h2 class="text-sm font-semibold text-slate-700 mb-4">Quick Actions</h2>
                <div class="space-y-3 text-sm">
                    <button class="w-full py-2.5 rounded-lg bg-[#1d4ed8] text-white text-sm font-medium hover:bg-[#1e40af]">
                        New Leave Request
                    </button>
                    <button class="w-full py-2.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200">
                        View My Requests
                    </button>
                </div>
            </section>
        </div>

        <!-- Recent Requests -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Recent Requests</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Request Type</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($recentRequests as $request): ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-6 py-3 text-slate-700">
                                    <?php echo htmlspecialchars($request['date']); ?>
                                </td>
                                <td class="px-6 py-3 text-slate-700">
                                    <?php echo htmlspecialchars($request['type']); ?>
                                </td>
                                <td class="px-6 py-3">
                                    <?php
                                        $status = $request['status'];
                                        $badgeClasses = [
                                            'Approved' => 'bg-emerald-100 text-emerald-700',
                                            'Declined' => 'bg-red-100 text-red-700',
                                            'Pending'  => 'bg-amber-100 text-amber-700'
                                        ];
                                        $class = $badgeClasses[$status] ?? 'bg-slate-100 text-slate-700';
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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

          // Remove any active state from all links
          $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#1d4ed8] font-medium rounded-l-none rounded-r-full');
          $('.js-side-link').addClass('rounded-lg');

          // Load only the right content
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Delegated filters for Time Off page
        $(document).on('change', '#usageTypeFilter, #usageYearFilter', function () {
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

        $(document).on('change', '#requestStatusFilter, #requestTypeFilter', function () {
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

