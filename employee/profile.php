<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$employeeName = $_SESSION['name'] ?? 'Juan Dela Cruz';
$position     = $_SESSION['position'] ?? 'Software Engineer';
$department   = $_SESSION['department'] ?? 'IT Department';
$employeeId   = $_SESSION['employee_id'] ?? 'EMP-0001';
$dateHired    = $_SESSION['hire_date'] ?? 'Jan 15, 2020';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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

    <!-- Main Content (only this area scrolls) -->
    <main class="ml-64 min-h-screen p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Profile</h1>
                <p class="text-sm text-slate-500 mt-1">
                    View and verify your HRIS information. Philippine format and IDs applied.
                </p>
            </div>
            <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                <span><?php echo htmlspecialchars($department); ?></span>
                <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                <span><?php echo htmlspecialchars($position); ?></span>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Personal Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Full Name</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($employeeName); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Date of Birth</p>
                        <p class="font-medium text-slate-900">January 10, 1995</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Sex</p>
                        <p class="font-medium text-slate-900">Male</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Civil Status</p>
                        <p class="font-medium text-slate-900">Single</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Citizenship</p>
                        <p class="font-medium text-slate-900">Filipino</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Place of Birth</p>
                        <p class="font-medium text-slate-900">Quezon City, Philippines</p>
                    </div>
                </div>
            </section>

            <!-- Government Information -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Government Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">TIN</p>
                        <p class="font-medium text-slate-900">123-456-789-000</p>
                    </div>
                    <div>
                        <p class="text-slate-500">SSS Number</p>
                        <p class="font-medium text-slate-900">12-3456789-0</p>
                    </div>
                    <div>
                        <p class="text-slate-500">PhilHealth Number</p>
                        <p class="font-medium text-slate-900">12-345678901-2</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Pag-IBIG (HDMF) Number</p>
                        <p class="font-medium text-slate-900">1234-5678-9012</p>
                    </div>
                    <div>
                        <p class="text-slate-500">PhilSys ID</p>
                        <p class="font-medium text-slate-900">0000-1111-2222-3333</p>
                    </div>
                    <div>
                        <p class="text-slate-500">BIR Tax Status</p>
                        <p class="font-medium text-slate-900">Single / ME</p>
                    </div>
                </div>
            </section>

            <!-- Employee Information -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Employee Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Employee ID</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($employeeId); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Position</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($position); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Department</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($department); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Employment Status</p>
                        <p class="font-medium text-slate-900">Regular</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Date Hired</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($dateHired); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Work Schedule</p>
                        <p class="font-medium text-slate-900">Mon–Fri, 9:00 AM – 6:00 PM</p>
                    </div>
                </div>
            </section>

            <!-- Contact Details -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Contact Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Mobile Number</p>
                        <p class="font-medium text-slate-900">+63 917 123 4567</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Email Address</p>
                        <p class="font-medium text-slate-900">juan.delacruz@example.com</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-slate-500">Present Address</p>
                        <p class="font-medium text-slate-900">
                            Unit 5B, Sample Condominium, EDSA, Mandaluyong City, Philippines
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-slate-500">Permanent Address</p>
                        <p class="font-medium text-slate-900">
                            Brgy. San Isidro, Quezon City, Philippines
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Emergency Contact Person</p>
                        <p class="font-medium text-slate-900">Maria Dela Cruz (Mother)</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Emergency Contact Number</p>
                        <p class="font-medium text-slate-900">+63 918 765 4321</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Documents -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 mt-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Documents</h2>
                <span class="text-xs text-slate-400">Uploaded documents for HRIS 201 file</span>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Document Type</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-4 py-2 text-slate-700">Birth Certificate (PSA)</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    Verified
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Jan 05, 2024</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-slate-700">Government IDs (Valid ID Set)</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    Complete
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Jan 05, 2024</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-slate-700">Employment Contract</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                    Signed
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Jan 15, 2024</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-slate-700">Company ID Form</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                    For Release
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs">Feb 01, 2024</td>
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

