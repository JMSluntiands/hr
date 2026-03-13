<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
require_once __DIR__ . '/../../controller/session_timeout.php';

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
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">Payslip</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        View your payslip and bank transfer details.
                    </p>
                </div>
                <!-- Search: Year, Month, Cut-off (15 / 30) -->
                <div class="flex flex-wrap items-end gap-2 w-full sm:w-auto">
                    <div>
                        <label for="payslip-year" class="block text-xs font-medium text-slate-500 mb-1">Year</label>
                        <select id="payslip-year" name="year"
                                class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FA9800]/30 focus:border-[#FA9800] min-w-[90px]">
                            <?php
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                echo '<option value="' . $y . '"' . ($y === $currentYear ? ' selected' : '') . '>' . $y . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="payslip-month" class="block text-xs font-medium text-slate-500 mb-1">Month</label>
                        <select id="payslip-month" name="month"
                                class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FA9800]/30 focus:border-[#FA9800] min-w-[110px]">
                            <?php
                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            $currentMonth = (int)date('n');
                            foreach ($months as $i => $name) {
                                $val = $i + 1;
                                echo '<option value="' . $val . '"' . ($val === $currentMonth ? ' selected' : '') . '>' . $name . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="payslip-cutoff" class="block text-xs font-medium text-slate-500 mb-1">Cut-off</label>
                        <select id="payslip-cutoff" name="cutoff"
                                class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#FA9800]/30 focus:border-[#FA9800] min-w-[70px]">
                            <option value="15">15</option>
                            <option value="30">30</option>
                        </select>
                    </div>
                    <button type="button" id="payslip-search-btn"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-[#FA9800] hover:bg-[#e08900] text-white text-sm font-medium whitespace-nowrap h-[38px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Search
                    </button>
                </div>
            </div>

            <!-- Results: Payslip & Bank Transfer (lalabas dito) -->
            <div id="payslip-results-heading" class="hidden mb-2">
                <p class="text-sm text-slate-600">Results for: <span id="payslip-search-term" class="font-medium text-slate-800"></span></p>
            </div>
            <div id="payslip-bank-results" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Card 1: Payslip -->
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                        <div class="w-10 h-10 rounded-lg bg-[#FA9800]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#FA9800]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Payslip</h2>
                            <p class="text-xs text-slate-500">View payslip PDF</p>
                        </div>
                    </div>
                    <!-- Payslip PDF (file lalabas dito) -->
                    <div class="border-t border-slate-100 p-4 bg-slate-50/30">
                        <p class="text-xs font-medium text-slate-600 mb-2">Payslip PDF</p>
                        <div class="min-h-[280px] rounded-lg bg-slate-100/50 flex items-center justify-center overflow-hidden">
                            <div id="payslip-pdf-placeholder" class="text-center py-8 px-4">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs text-slate-500">No payslip PDF for this period.</p>
                            </div>
                            <div id="payslip-pdf-wrap" class="hidden w-full h-full min-h-[280px]">
                                <iframe id="payslip-pdf-viewer" class="w-full min-h-[280px]" style="height: 280px;" title="Payslip PDF"></iframe>
                                <a id="payslip-pdf-download" href="#" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-[#FA9800] hover:underline">Download PDF</a>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Card 2: Bank Transfer -->
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Bank Transfer</h2>
                            <p class="text-xs text-slate-500">View bank transfer PDF</p>
                        </div>
                    </div>
                    <!-- Bank Transfer PDF (file lalabas dito) -->
                    <div class="border-t border-slate-100 p-4 bg-slate-50/30">
                        <p class="text-xs font-medium text-slate-600 mb-2">Bank Transfer PDF</p>
                        <div class="min-h-[280px] rounded-lg bg-slate-100/50 flex items-center justify-center overflow-hidden">
                            <div id="bank-pdf-placeholder" class="text-center py-8 px-4">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs text-slate-500">No bank transfer PDF for this period.</p>
                            </div>
                            <div id="bank-pdf-wrap" class="hidden w-full h-full min-h-[280px]">
                                <iframe id="bank-pdf-viewer" class="w-full min-h-[280px]" style="height: 280px;" title="Bank Transfer PDF"></iframe>
                                <a id="bank-pdf-download" href="#" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:underline">Download PDF</a>
                            </div>
                        </div>
                    </div>
                </section>
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

        // Search: Year, Month, Cut-off (15/30) — payslip & bank transfer PDF lalabas
        var $year = $('#payslip-year');
        var $month = $('#payslip-month');
        var $cutoff = $('#payslip-cutoff');
        var $searchBtn = $('#payslip-search-btn');
        var $resultsHeading = $('#payslip-results-heading');
        var $searchTerm = $('#payslip-search-term');
        var monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        function doPayslipSearch() {
          var y = $year.val();
          var m = $month.val();
          var c = $cutoff.val();
          var label = monthNames[parseInt(m, 10)] + ' ' + y + ' (Cut-off: ' + c + ')';
          $searchTerm.text(label);
          $resultsHeading.removeClass('hidden').show();
          document.getElementById('payslip-bank-results').scrollIntoView({ behavior: 'smooth', block: 'start' });

          // TODO: call API to load payslip PDF & bank transfer PDF for year, month, cutoff
          // Example: GET payslip.pdf?year=2025&month=3&cutoff=15
          // Then: show in #payslip-pdf-viewer, #bank-pdf-viewer and set download links
          var payslipPdfUrl = '';  // set from API
          var bankPdfUrl = '';     // set from API
          if (payslipPdfUrl) {
            $('#payslip-pdf-placeholder').addClass('hidden');
            $('#payslip-pdf-wrap').removeClass('hidden');
            $('#payslip-pdf-viewer').attr('src', payslipPdfUrl);
            $('#payslip-pdf-download').attr('href', payslipPdfUrl).removeClass('hidden');
          } else {
            $('#payslip-pdf-placeholder').removeClass('hidden');
            $('#payslip-pdf-wrap').addClass('hidden').find('iframe').attr('src', '');
          }
          if (bankPdfUrl) {
            $('#bank-pdf-placeholder').addClass('hidden');
            $('#bank-pdf-wrap').removeClass('hidden');
            $('#bank-pdf-viewer').attr('src', bankPdfUrl);
            $('#bank-pdf-download').attr('href', bankPdfUrl).removeClass('hidden');
          } else {
            $('#bank-pdf-placeholder').removeClass('hidden');
            $('#bank-pdf-wrap').addClass('hidden').find('iframe').attr('src', '');
          }
        }

        $searchBtn.on('click', doPayslipSearch);
      });
    </script>
</body>
</html>

