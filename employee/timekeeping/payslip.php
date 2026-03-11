<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

include '../../database/db.php';
include '../include/employee_data.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
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
    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../../uploads/' . $employeePhoto)): ?>
                    <img src="../../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold">
                        <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate">
                    <?php echo htmlspecialchars($employeeName); ?>
                </span>
                <span class="text-[11px] text-white/80">
                    Employee
                </span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <!-- Sidebar (Timesheet / Payslip only) -->
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#FA9800] text-white flex flex-col transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../../uploads/' . $employeePhoto)): ?>
                    <img src="../../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="index.php"
               data-url="index.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm3-8h6" />
                </svg>
                <span>Timesheet</span>
            </a>
            <a href="payslip.php"
               data-url="payslip.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
                <span>Payslip</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="../module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">
                Back to Main Menu
            </a>
        </div>
    </aside>

    <!-- Mobile sidebar backdrop -->
    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">Payslip</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        This area is reserved for your payslips. Content will be added here.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../include/sidebar-employee.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          if (url === 'index.php' || url === 'payslip.php') {
            window.location.href = url;
            return;
          }
        });
      });
    </script>
</body>
</html>

