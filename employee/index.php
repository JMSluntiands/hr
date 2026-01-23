<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/employee_data.php';

$position   = $position ?: ($_SESSION['position'] ?? '');
$department = $department ?: ($_SESSION['department'] ?? '');
$hireDate   = $dateHired ?: ($_SESSION['hire_date'] ?? '');

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
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#FA9800] text-black flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-[#FA9800]/40">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/10 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-bold text-sm truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs font-bold">Employee</div>
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
            <!-- Settings -->
            <a href="settings.php"
               data-url="settings.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>Settings</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content (scrollable only on the right side) -->
    <main class="ml-64 min-h-screen p-8 overflow-y-auto">
        <div id="main-inner">
        <!-- Default Password Notice -->
        <?php if (isset($_SESSION['is_default_password']) && $_SESSION['is_default_password']): ?>
        <div id="defaultPasswordNotice" class="mb-6 bg-amber-50 border-l-4 border-amber-400 p-4 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-amber-800">
                        <strong>Security Notice:</strong> You are currently using a default password. Please change your password immediately for security purposes.
                    </p>
                    <div class="mt-3">
                        <a href="settings.php" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors">
                            Change Password Now
                        </a>
                        <button type="button" id="dismissNotice" class="ml-3 text-sm text-amber-700 hover:text-amber-900 underline">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
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
                    <button class="w-full py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#d18a15]">
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

          // My Profile: full page load so content and upload modal always work correctly
          if (url === 'profile.php') {
            window.location.href = url;
            return;
          }

          // Remove any active state from all links
          $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#FA9800] font-medium rounded-l-none rounded-r-full');
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

        // Dismiss default password notice
        $('#dismissNotice').on('click', function() {
          $('#defaultPasswordNotice').fadeOut(300);
        });

        // Profile Photo Upload (when profile is loaded via AJAX)
        $(document).on('click', '#profilePhotoBtn', function(e) {
          e.preventDefault();
          $('#profilePhotoInput').click();
        });
        $(document).on('change', '#profilePhotoInput', function() {
          var $input = $(this);
          var files = $input[0].files;
          if (!files || !files.length) return;
          var fd = new FormData();
          fd.append('profile_picture', files[0]);
          $('#profilePhotoMessage').addClass('hidden').html('');
          $('#profilePhotoBtn').prop('disabled', true).text('Uploading...');
          $.ajax({
            url: 'profile-picture-upload.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo');
              $input.val('');
              if (res.status === 'success') {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-emerald-600').html(res.message);
                if (res.path) {
                  $('#profilePhotoImg').attr('src', '../uploads/' + res.path).removeClass('hidden');
                  $('#profilePhotoInitial').addClass('hidden');
                }
                setTimeout(function() { location.reload(); }, 800);
              } else {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(res.message || 'Upload failed');
              }
            },
            error: function(xhr) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo');
              $input.val('');
              var m = 'Upload failed. Please try again.';
              try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(e) {}
              $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(m);
            }
          });
        });
      });
    </script>
</body>
</html>

