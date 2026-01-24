<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/employee_data.php';

// Initialize leave data
$slTotal = 0;
$slUsed = 0;
$slRemaining = 0;
$vlTotal = 0;
$vlUsed = 0;
$vlRemaining = 0;
$blTotal = 0; // Bereavement Leave
$elTotal = 0; // Emergency Leave
$leaveUsageHistory = [];
$leaveRequests = [];
$availableYears = [];

if ($conn && $employeeDbId) {
    $currentYear = date('Y');
    
    // Get leave allocations for current year
    $allocQuery = "SELECT leave_type, total_days 
                   FROM leave_allocations 
                   WHERE employee_id = ? AND year = ?";
    $allocStmt = $conn->prepare($allocQuery);
    if ($allocStmt) {
        $allocStmt->bind_param('ii', $employeeDbId, $currentYear);
        $allocStmt->execute();
        $allocResult = $allocStmt->get_result();
        while ($row = $allocResult->fetch_assoc()) {
            if ($row['leave_type'] === 'Sick Leave') {
                $slTotal = (int)$row['total_days'];
            } elseif ($row['leave_type'] === 'Vacation Leave') {
                $vlTotal = (int)$row['total_days'];
            } elseif ($row['leave_type'] === 'Bereavement Leave') {
                $blTotal = (int)$row['total_days'];
            } elseif ($row['leave_type'] === 'Emergency Leave') {
                $elTotal = (int)$row['total_days'];
            }
        }
        $allocStmt->close();
    }
    
    // Check if leave_requests table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkTable && $checkTable->num_rows > 0) {
        // Get used days from approved leave requests for current year
        $usedQuery = "SELECT leave_type, 
                      SUM(CASE 
                          WHEN start_date = end_date THEN 1
                          ELSE COALESCE(days, DATEDIFF(end_date, start_date) + 1)
                      END) as total_used
                      FROM leave_requests 
                      WHERE employee_id = ? 
                      AND status = 'Approved'
                      AND YEAR(start_date) = ?
                      GROUP BY leave_type";
        $usedStmt = $conn->prepare($usedQuery);
        if ($usedStmt) {
            $usedStmt->bind_param('ii', $employeeDbId, $currentYear);
            $usedStmt->execute();
            $usedResult = $usedStmt->get_result();
            while ($row = $usedResult->fetch_assoc()) {
                if ($row['leave_type'] === 'Sick Leave') {
                    $slUsed = (int)$row['total_used'];
                } elseif ($row['leave_type'] === 'Vacation Leave') {
                    $vlUsed = (int)$row['total_used'];
                }
            }
            $usedStmt->close();
        }
        
        // Calculate remaining days
        $slRemaining = max(0, $slTotal - $slUsed);
        $vlRemaining = max(0, $vlTotal - $vlUsed);
        
        // Get leave usage history (approved requests only)
        $historyQuery = "SELECT id, start_date, end_date, leave_type, 
                        CASE 
                            WHEN start_date = end_date THEN 1
                            ELSE COALESCE(days, DATEDIFF(end_date, start_date) + 1)
                        END as calculated_days,
                        reason, YEAR(start_date) as year
                        FROM leave_requests 
                        WHERE employee_id = ? 
                        AND status = 'Approved'
                        ORDER BY start_date DESC";
        $historyStmt = $conn->prepare($historyQuery);
        if ($historyStmt) {
            $historyStmt->bind_param('i', $employeeDbId);
            $historyStmt->execute();
            $historyResult = $historyStmt->get_result();
            while ($row = $historyResult->fetch_assoc()) {
                $leaveUsageHistory[] = $row;
                // Collect unique years
                $year = (int)$row['year'];
                if (!in_array($year, $availableYears)) {
                    $availableYears[] = $year;
                }
            }
            $historyStmt->close();
        }
        
        // Get all leave requests
        $requestsQuery = "SELECT id, created_at, start_date, end_date, leave_type, 
                         CASE 
                             WHEN start_date = end_date THEN 1
                             ELSE COALESCE(days, DATEDIFF(end_date, start_date) + 1)
                         END as calculated_days,
                         status
                         FROM leave_requests 
                         WHERE employee_id = ? 
                         ORDER BY created_at DESC";
        $requestsStmt = $conn->prepare($requestsQuery);
        if ($requestsStmt) {
            $requestsStmt->bind_param('i', $employeeDbId);
            $requestsStmt->execute();
            $requestsResult = $requestsStmt->get_result();
            while ($row = $requestsResult->fetch_assoc()) {
                $leaveRequests[] = $row;
            }
            $requestsStmt->close();
        }
    }
    
    // Sort years descending
    rsort($availableYears);
}
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
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#FA9800] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
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
            <!-- My Compensation -->
            <a href="compensation.php"
               data-url="compensation.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>My Compensation</span>
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
            <button id="newLeaveRequestBtn" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#FA9800] text-white text-xs font-medium shadow-sm hover:bg-[#d18a15]">
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
                        <p class="text-xl font-semibold text-slate-900"><?php echo $slTotal; ?></p>
                        <p class="text-xs text-slate-400">days / year</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Used</p>
                        <p class="text-xl font-semibold text-amber-600"><?php echo $slUsed; ?></p>
                        <p class="text-xs text-slate-400">approved</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Remaining</p>
                        <p class="text-xl font-semibold text-emerald-600"><?php echo $slRemaining; ?></p>
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
                        <p class="text-xl font-semibold text-slate-900"><?php echo $vlTotal; ?></p>
                        <p class="text-xs text-slate-400">days / year</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Used</p>
                        <p class="text-xl font-semibold text-amber-600"><?php echo $vlUsed; ?></p>
                        <p class="text-xs text-slate-400">approved</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Remaining</p>
                        <p class="text-xl font-semibold text-emerald-600"><?php echo $vlRemaining; ?></p>
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
                        <?php foreach ($availableYears as $year): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                        <?php endforeach; ?>
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
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($leaveUsageHistory)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No leave usage history found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($leaveUsageHistory as $history): 
                            $typeCode = $history['leave_type'] === 'Sick Leave' ? 'SL' : 'VL';
                            $year = (int)$history['year'];
                            $dateStr = date('M d, Y', strtotime($history['start_date']));
                            $startDate = $history['start_date'];
                            $isFutureDate = strtotime($startDate) > strtotime('today');
                            $isVL = $history['leave_type'] === 'Vacation Leave';
                            $canCancel = $isVL && $isFutureDate;
                        ?>
                        <tr data-type="<?php echo $typeCode; ?>" data-year="<?php echo $year; ?>" data-id="<?php echo (int)$history['id']; ?>">
                            <td class="px-4 py-2 text-slate-700"><?php echo $dateStr; ?></td>
                            <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars($history['leave_type']); ?></td>
                            <td class="px-4 py-2 text-slate-700"><?php echo (int)$history['calculated_days']; ?></td>
                            <td class="px-4 py-2 text-slate-500 text-xs"><?php echo htmlspecialchars($history['reason'] ?? '—'); ?></td>
                            <td class="px-4 py-2">
                                <?php if ($canCancel): ?>
                                <button type="button" 
                                        class="cancel-leave-btn px-3 py-1.5 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 font-medium rounded-lg transition-colors cursor-pointer border border-red-200 hover:border-red-300" 
                                        data-id="<?php echo (int)$history['id']; ?>" 
                                        data-days="<?php echo (int)$history['calculated_days']; ?>" 
                                        data-type="<?php echo htmlspecialchars($history['leave_type']); ?>">
                                    Cancel
                                </button>
                                <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
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
                        <?php if (empty($leaveRequests)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No leave requests found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($leaveRequests as $request): 
                            $typeCode = $request['leave_type'] === 'Sick Leave' ? 'SL' : 'VL';
                            $status = $request['status'];
                            $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : 
                                          ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 
                                          ($status === 'Cancelled' ? 'bg-slate-100 text-slate-700' : 
                                          'bg-amber-100 text-amber-700'));
                            $filedDate = date('M d, Y', strtotime($request['created_at']));
                            $startDate = date('M d, Y', strtotime($request['start_date']));
                            $endDate = date('M d, Y', strtotime($request['end_date']));
                            $dateRange = $request['start_date'] === $request['end_date'] ? $startDate : $startDate . '–' . $endDate;
                        ?>
                        <tr data-type="<?php echo $typeCode; ?>" data-status="<?php echo $status; ?>">
                            <td class="px-4 py-2 text-slate-700"><?php echo $filedDate; ?></td>
                            <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars($request['leave_type']); ?></td>
                            <td class="px-4 py-2 text-slate-700"><?php echo $dateRange; ?></td>
                            <td class="px-4 py-2 text-slate-700"><?php echo (int)$request['calculated_days']; ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>

    <!-- New Leave Request Modal -->
    <div id="newLeaveRequestModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">New Leave Request</h3>
                <button id="closeLeaveModal" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="leaveRequestForm" class="p-6">
                <div id="unpaidLeaveNote" class="hidden mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Unpaid Leave Notice</p>
                            <p class="text-xs text-amber-700 mt-1">You have no remaining leave credits. The upcoming leave filing will be considered as <strong>UNPAID LEAVE</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="leave_type" class="block text-sm font-medium text-slate-700 mb-2">Leave Type <span class="text-red-500">*</span></label>
                        <select id="leave_type" name="leave_type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent">
                            <option value="">Select Leave Type</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Maternity Leave">Maternity Leave</option>
                            <option value="Paternity Leave">Paternity Leave</option>
                            <option value="Bereavement Leave">Bereavement Leave</option>
                        </select>
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-slate-700 mb-2">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" id="start_date" name="start_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-slate-700 mb-2">End Date <span class="text-red-500">*</span></label>
                        <input type="date" id="end_date" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent">
                    </div>
                    <div>
                        <label for="reason" class="block text-sm font-medium text-slate-700 mb-2">Reason <span class="text-red-500">*</span></label>
                        <textarea id="reason" name="reason" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="Please provide a reason for your leave request"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" id="submitLeaveBtn" class="flex-1 px-4 py-2 bg-[#FA9800] text-white font-medium rounded-lg hover:bg-[#d18a15] transition-colors">
                        Submit Request
                    </button>
                    <button type="button" id="cancelLeaveBtn" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
                <div id="leaveRequestMessage" class="mt-4 hidden"></div>
            </form>
        </div>
    </div>

    <!-- Cancel Leave Request Modal -->
    <div id="cancelLeaveModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Cancel Leave Request</h3>
                <button type="button" id="closeCancelModal" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="cancelLeaveForm" class="p-6">
                <input type="hidden" id="cancelLeaveId" name="leave_id" value="">
                <input type="hidden" id="cancelLeaveDays" name="days" value="">
                <input type="hidden" id="cancelLeaveType" name="leave_type" value="">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-slate-600 mb-4">Are you sure you want to cancel this leave request? The leave credits will be restored to your account.</p>
                    </div>
                    <div>
                        <label for="cancelReason" class="block text-sm font-medium text-slate-700 mb-2">Reason for Cancellation <span class="text-red-500">*</span></label>
                        <textarea id="cancelReason" name="cancel_reason" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="Please provide a reason for cancelling this leave request"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="submit" id="submitCancelBtn" class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        Confirm Cancel
                    </button>
                    <button type="button" id="cancelCancelBtn" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200 transition-colors">
                        Back
                    </button>
                </div>
                <div id="cancelLeaveMessage" class="mt-4 hidden"></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php') {
            window.location.href = url;
            return;
          }

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Delegated filters for Time Off page (works with AJAX loading)
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

        // Cancel leave request modal handlers
        $(document).on('click', '.cancel-leave-btn', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          const leaveId = $(this).data('id');
          const days = $(this).data('days');
          const leaveType = $(this).data('type');
          
          if (!leaveId || !days || !leaveType) {
            alert('Error: Missing data. Please refresh the page and try again.');
            return;
          }
          
          $('#cancelLeaveId').val(leaveId);
          $('#cancelLeaveDays').val(days);
          $('#cancelLeaveType').val(leaveType);
          $('#cancelReason').val('');
          $('#cancelLeaveMessage').addClass('hidden').html('');
          
          $('#cancelLeaveModal').removeClass('hidden').addClass('flex');
        });

        // Close cancel modal
        $(document).on('click', '#closeCancelModal, #cancelCancelBtn', function() {
          $('#cancelLeaveModal').removeClass('flex').addClass('hidden');
          $('#cancelLeaveForm')[0].reset();
        });

        // Close modal when clicking outside
        $(document).on('click', '#cancelLeaveModal', function(e) {
          if ($(e.target).attr('id') === 'cancelLeaveModal') {
            $('#cancelLeaveModal').removeClass('flex').addClass('hidden');
            $('#cancelLeaveForm')[0].reset();
          }
        });

        // Handle cancel leave form submission
        $(document).on('submit', '#cancelLeaveForm', function(e) {
          e.preventDefault();
          
          const formData = $(this).serialize();
          
          $('#cancelLeaveMessage').addClass('hidden').html('');
          $('#submitCancelBtn').prop('disabled', true).text('Cancelling...');
          
          $.ajax({
            url: 'cancel-leave-request.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
              $('#submitCancelBtn').prop('disabled', false).text('Confirm Cancel');
              if (res.status === 'success') {
                $('#cancelLeaveMessage').removeClass('hidden').addClass('p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm').html(res.message);
                setTimeout(function() {
                  $('#cancelLeaveModal').removeClass('flex').addClass('hidden');
                  location.reload();
                }, 1500);
              } else {
                $('#cancelLeaveMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(res.message || 'Failed to cancel leave request');
              }
            },
            error: function(xhr, status, error) {
              $('#submitCancelBtn').prop('disabled', false).text('Confirm Cancel');
              var m = 'Failed to cancel leave request. Please try again.';
              try {
                var r = JSON.parse(xhr.responseText);
                if (r.message) m = r.message;
              } catch(e) {
                if (xhr.responseText) {
                  m = 'Error: ' + xhr.responseText.substring(0, 200);
                } else {
                  m = 'Error: ' + error + ' (Status: ' + status + ')';
                }
              }
              $('#cancelLeaveMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(m);
            }
          });
        });

        // Quick Actions - New Leave Request (use event delegation)
        $(document).on('click', '#newLeaveRequestBtn', function() {
          $('#newLeaveRequestModal').removeClass('hidden').addClass('flex');
          // Set minimum date to today
          const today = new Date().toISOString().split('T')[0];
          $('#start_date, #end_date').attr('min', today);
          
          // Hide notice initially
          $('#unpaidLeaveNote').addClass('hidden');
        });
        
        // Leave type totals for validation
        // BL and EL use VL credits
        const leaveTypeTotals = {
          'Sick Leave': <?php echo $slTotal ?? 0; ?>,
          'Vacation Leave': <?php echo $vlTotal ?? 0; ?>,
          'Bereavement Leave': <?php echo $vlTotal ?? 0; ?>, // Uses VL credits
          'Emergency Leave': <?php echo $vlTotal ?? 0; ?> // Uses VL credits
        };
        
        // Show/hide unpaid leave notice when leave type is selected
        $(document).on('change', '#leave_type', function() {
          const selectedType = $(this).val();
          const applicableTypes = ['Sick Leave', 'Vacation Leave', 'Bereavement Leave', 'Emergency Leave'];
          
          if (applicableTypes.includes(selectedType)) {
            const total = leaveTypeTotals[selectedType] || 0;
            if (total === 0) {
              $('#unpaidLeaveNote').removeClass('hidden');
            } else {
              $('#unpaidLeaveNote').addClass('hidden');
            }
          } else {
            $('#unpaidLeaveNote').addClass('hidden');
          }
        });

        // Close modal (use event delegation)
        $(document).on('click', '#closeLeaveModal, #cancelLeaveBtn', function() {
          $('#newLeaveRequestModal').removeClass('flex').addClass('hidden');
          $('#leaveRequestForm')[0].reset();
        });

        // Close modal when clicking outside (use event delegation)
        $(document).on('click', '#newLeaveRequestModal', function(e) {
          if ($(e.target).attr('id') === 'newLeaveRequestModal') {
            $('#newLeaveRequestModal').removeClass('flex').addClass('hidden');
            $('#leaveRequestForm')[0].reset();
          }
        });

        // Handle leave request form submission (use event delegation)
        $(document).on('submit', '#leaveRequestForm', function(e) {
          e.preventDefault();
          
          const formData = $(this).serialize();
          
          $('#leaveRequestMessage').addClass('hidden').html('');
          $('#submitLeaveBtn').prop('disabled', true).text('Submitting...');
          
          $.ajax({
            url: 'submit-leave-request.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
              $('#submitLeaveBtn').prop('disabled', false).text('Submit Request');
              if (res.status === 'success') {
                $('#leaveRequestMessage').removeClass('hidden').addClass('p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm').html(res.message);
                $('#leaveRequestForm')[0].reset();
                setTimeout(function() {
                  $('#newLeaveRequestModal').removeClass('flex').addClass('hidden');
                  location.reload();
                }, 1500);
              } else {
                $('#leaveRequestMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(res.message || 'Failed to submit leave request');
              }
            },
            error: function(xhr, status, error) {
              $('#submitLeaveBtn').prop('disabled', false).text('Submit Request');
              var m = 'Failed to submit leave request. Please try again.';
              try {
                var r = JSON.parse(xhr.responseText);
                if (r.message) m = r.message;
              } catch(e) {
                // If response is not JSON, show the raw response or error details
                if (xhr.responseText) {
                  m = 'Error: ' + xhr.responseText.substring(0, 200);
                } else {
                  m = 'Error: ' + error + ' (Status: ' + status + ')';
                }
              }
              $('#leaveRequestMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(m);
            }
          });
        });

        $(document).on('click', '#profilePhotoBtn', function(e) { e.preventDefault(); $('#profilePhotoInput').click(); });
        $(document).on('change', '#profilePhotoInput', function() {
          var $input = $(this); var files = $input[0].files;
          if (!files || !files.length) return;
          var fd = new FormData(); fd.append('profile_picture', files[0]);
          $('#profilePhotoMessage').addClass('hidden').html('');
          $('#profilePhotoBtn').prop('disabled', true).text('Uploading...');
          $.ajax({ url: 'profile-picture-upload.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo'); $input.val('');
              if (res.status === 'success') {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-emerald-600').html(res.message);
                if (res.path) { $('#profilePhotoImg').attr('src', '../uploads/' + res.path).removeClass('hidden'); $('#profilePhotoInitial').addClass('hidden'); }
                setTimeout(function() { location.reload(); }, 800);
              } else { $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(res.message || 'Upload failed'); }
            },
            error: function(xhr) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo'); $input.val('');
              var m = 'Upload failed.'; try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(e) {}
              $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(m);
            }
          });
        });
      });
    </script>
</body>
</html>

