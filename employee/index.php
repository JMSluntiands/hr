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

// Get Time Off Summary from database
$remainingLeave = 0;
$usedLeave = 0;
$pendingCount = 0;
$slTotal = 0;
$vlTotal = 0;
$currentYear = (int)date('Y');

if ($employeeDbId && $conn) {
    // Get leave allocations for current year
    $checkAlloc = $conn->query("SHOW TABLES LIKE 'leave_allocations'");
    if ($checkAlloc && $checkAlloc->num_rows > 0) {
        // Try with integer year first
        $allocStmt = $conn->prepare("SELECT COALESCE(SUM(remaining_days), 0) as total_remaining, COALESCE(SUM(used_days), 0) as total_used FROM leave_allocations WHERE employee_id = ? AND year = ?");
        if ($allocStmt) {
            $allocStmt->bind_param('ii', $employeeDbId, $currentYear);
            $allocStmt->execute();
            $allocResult = $allocStmt->get_result();
            if ($allocRow = $allocResult->fetch_assoc()) {
                $remainingLeave = (int)($allocRow['total_remaining'] ?? 0);
                $usedLeave = (int)($allocRow['total_used'] ?? 0);
            }
            $allocStmt->close();
        }
        
        // Get SL and VL totals separately
        $typeAllocStmt = $conn->prepare("SELECT leave_type, total_days FROM leave_allocations WHERE employee_id = ? AND year = ?");
        if ($typeAllocStmt) {
            $typeAllocStmt->bind_param('ii', $employeeDbId, $currentYear);
            $typeAllocStmt->execute();
            $typeAllocResult = $typeAllocStmt->get_result();
            while ($typeRow = $typeAllocResult->fetch_assoc()) {
                if ($typeRow['leave_type'] === 'Sick Leave') {
                    $slTotal = (int)($typeRow['total_days'] ?? 0);
                } elseif ($typeRow['leave_type'] === 'Vacation Leave') {
                    $vlTotal = (int)($typeRow['total_days'] ?? 0);
                } elseif ($typeRow['leave_type'] === 'Bereavement Leave') {
                    $blTotal = (int)($typeRow['total_days'] ?? 0);
                } elseif ($typeRow['leave_type'] === 'Emergency Leave') {
                    $elTotal = (int)($typeRow['total_days'] ?? 0);
                }
            }
            $typeAllocStmt->close();
        }
        
        // If no data found, try with string year format
        if ($remainingLeave == 0 && $usedLeave == 0 && $slTotal == 0 && $vlTotal == 0) {
            $yearStr = (string)$currentYear;
            $allocStmt2 = $conn->prepare("SELECT COALESCE(SUM(remaining_days), 0) as total_remaining, COALESCE(SUM(used_days), 0) as total_used FROM leave_allocations WHERE employee_id = ? AND year = ?");
            if ($allocStmt2) {
                $allocStmt2->bind_param('is', $employeeDbId, $yearStr);
                $allocStmt2->execute();
                $allocResult2 = $allocStmt2->get_result();
                if ($allocRow2 = $allocResult2->fetch_assoc()) {
                    $remainingLeave = (int)($allocRow2['total_remaining'] ?? 0);
                    $usedLeave = (int)($allocRow2['total_used'] ?? 0);
                }
                $allocStmt2->close();
            }
            
            // Get SL and VL totals with string year
            $typeAllocStmt2 = $conn->prepare("SELECT leave_type, total_days FROM leave_allocations WHERE employee_id = ? AND year = ?");
            if ($typeAllocStmt2) {
                $typeAllocStmt2->bind_param('is', $employeeDbId, $yearStr);
                $typeAllocStmt2->execute();
                $typeAllocResult2 = $typeAllocStmt2->get_result();
                while ($typeRow2 = $typeAllocResult2->fetch_assoc()) {
                    if ($typeRow2['leave_type'] === 'Sick Leave') {
                        $slTotal = (int)($typeRow2['total_days'] ?? 0);
                    } elseif ($typeRow2['leave_type'] === 'Vacation Leave') {
                        $vlTotal = (int)($typeRow2['total_days'] ?? 0);
                    } elseif ($typeRow2['leave_type'] === 'Bereavement Leave') {
                        $blTotal = (int)($typeRow2['total_days'] ?? 0);
                    } elseif ($typeRow2['leave_type'] === 'Emergency Leave') {
                        $elTotal = (int)($typeRow2['total_days'] ?? 0);
                    }
                }
                $typeAllocStmt2->close();
            }
        }
    }
    
    // Get pending leave requests count
    $checkLeaveReq = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkLeaveReq && $checkLeaveReq->num_rows > 0) {
        $pendingStmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status = 'Pending'");
        if ($pendingStmt) {
            $pendingStmt->bind_param('i', $employeeDbId);
            $pendingStmt->execute();
            $pendingResult = $pendingStmt->get_result();
            if ($pendingRow = $pendingResult->fetch_assoc()) {
                $pendingCount = (int)($pendingRow['count'] ?? 0);
            }
            $pendingStmt->close();
        }
    }
    
    // Get pending document requests count
    $checkDocReq = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($checkDocReq && $checkDocReq->num_rows > 0) {
        $docPendingStmt = $conn->prepare("SELECT COUNT(*) as count FROM document_requests WHERE employee_id = ? AND status = 'Pending'");
        if ($docPendingStmt) {
            $docPendingStmt->bind_param('i', $employeeDbId);
            $docPendingStmt->execute();
            $docPendingResult = $docPendingStmt->get_result();
            if ($docPendingRow = $docPendingResult->fetch_assoc()) {
                $pendingCount += (int)($docPendingRow['count'] ?? 0);
            }
            $docPendingStmt->close();
        }
    }
}

// Get Recent Requests from database (combine leave_requests and document_requests)
$recentRequests = [];

if ($employeeDbId && $conn) {
    $allRequests = [];
    
    // Get recent leave requests
    $checkLeaveReq = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkLeaveReq && $checkLeaveReq->num_rows > 0) {
        $leaveReqStmt = $conn->prepare("SELECT id, leave_type as type, status, created_at, 'Leave Request' as request_type FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
        if ($leaveReqStmt) {
            $leaveReqStmt->bind_param('i', $employeeDbId);
            $leaveReqStmt->execute();
            $leaveReqResult = $leaveReqStmt->get_result();
            while ($row = $leaveReqResult->fetch_assoc()) {
                $allRequests[] = [
                    'id' => $row['id'],
                    'date' => date('M d, Y', strtotime($row['created_at'])),
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'request_type' => $row['request_type'],
                    'created_at' => $row['created_at']
                ];
            }
            $leaveReqStmt->close();
        }
    }
    
    // Get recent document requests
    $checkDocReq = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($checkDocReq && $checkDocReq->num_rows > 0) {
        $docReqStmt = $conn->prepare("SELECT id, document_type as type, status, created_at, 'Document Request' as request_type FROM document_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
        if ($docReqStmt) {
            $docReqStmt->bind_param('i', $employeeDbId);
            $docReqStmt->execute();
            $docReqResult = $docReqStmt->get_result();
            while ($row = $docReqResult->fetch_assoc()) {
                $allRequests[] = [
                    'id' => $row['id'],
                    'date' => date('M d, Y', strtotime($row['created_at'])),
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'request_type' => $row['request_type'],
                    'created_at' => $row['created_at']
                ];
            }
            $docReqStmt->close();
        }
    }
    
    // Sort all requests by created_at DESC and take top 5
    usort($allRequests, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $recentRequests = array_slice($allRequests, 0, 5);
}
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
                    <button id="newLeaveRequestBtn" class="w-full py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#d18a15] transition-colors">
                        New Leave Request
                    </button>
                    <button id="viewMyRequestsBtn" class="w-full py-2.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition-colors">
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
                        <?php if (empty($recentRequests)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-slate-500 text-sm">
                                    No recent requests found.
                                </td>
                            </tr>
                        <?php else: ?>
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
                                            'Rejected' => 'bg-red-100 text-red-700',
                                            'Declined' => 'bg-red-100 text-red-700',
                                            'Pending'  => 'bg-amber-100 text-amber-700',
                                            'Cancelled' => 'bg-slate-100 text-slate-700'
                                        ];
                                        $class = $badgeClasses[$status] ?? 'bg-slate-100 text-slate-700';
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          // My Profile, Compensation, Time Off, and Dashboard: full page load so content and modals always work correctly
          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'index.php') {
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
            // Re-initialize date pickers if dashboard is loaded
            if (url === 'index.php') {
              const today = new Date().toISOString().split('T')[0];
              $('#start_date, #end_date').attr('min', today);
            }
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

        // Dismiss default password notice (use event delegation)
        $(document).on('click', '#dismissNotice', function() {
          $('#defaultPasswordNotice').fadeOut(300);
        });

        // Quick Actions - View My Requests (use event delegation)
        $(document).on('click', '#viewMyRequestsBtn', function() {
          window.location.href = 'request.php';
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

