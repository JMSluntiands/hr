<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include __DIR__ . '/../database/db.php';
include __DIR__ . '/include/employee_data.php';
include __DIR__ . '/include/employee_leave_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leave Credits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: { luntianBlue: '#FA9800', luntianLight: '#f3f4ff' }
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
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate"><?php echo htmlspecialchars($employeeName); ?></span>
                <span class="text-[11px] text-white/80">Employee</span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </header>

    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../include/sidebar-scrollbar-once.php'; ?>
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
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
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-2">
            <a href="index.php" data-url="index.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" data-url="profile.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>My Profile</span>
            </a>
            <a href="timeoff.php" data-url="timeoff.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg bg-white/20 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span>My Leave Credits</span>
            </a>
            <a href="request.php" data-url="request.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <span>My Request</span>
            </a>
            <a href="compensation.php" data-url="compensation.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>My Compensation</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-my-inventory-nav.php'; ?>
            <a href="progressive-discipline.php" data-url="progressive-discipline.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
                <span>Progressive Discipline</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-incident-nav.php'; ?>
            <a href="settings.php" data-url="settings.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="shrink-0 border-t border-white/20 p-4">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
    </aside>

    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 overflow-y-auto">
        <div id="main-inner">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">My Leave Credits</h1>
                    <p class="text-sm text-slate-500 mt-1">View your leave credits and file leave requests.</p>
                </div>
                <button id="newLeaveRequestBtn" type="button" class="px-4 py-2.5 rounded-lg bg-[#FA9800] text-white text-sm font-medium hover:bg-[#d18a15] transition-colors">
                    New Leave Request
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Remaining Leave</h2>
                    <p class="text-2xl font-bold text-emerald-600"><?php echo (int)$remainingLeave; ?> <span class="text-base font-normal text-slate-600">days</span></p>
                </section>
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Used Leave</h2>
                    <p class="text-2xl font-bold text-sky-600"><?php echo (int)$usedLeave; ?> <span class="text-base font-normal text-slate-600">days</span></p>
                </section>
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4">Pending Requests</h2>
                    <p class="text-2xl font-bold text-amber-500"><?php echo (int)$pendingCount; ?></p>
                </section>
            </div>

            <!-- Leave Credits by Type (Bar Chart) - one row, separate cards -->
            <?php
            $slUsedPct = $slTotal > 0 ? round(($slUsed / $slTotal) * 100) : 0;
            $slRemPct  = $slTotal > 0 ? round(($slRemaining / $slTotal) * 100) : 0;
            $vlUsedPct = $vlTotal > 0 ? round(($vlUsed / $vlTotal) * 100) : 0;
            $vlRemPct  = $vlTotal > 0 ? round(($vlRemaining / $vlTotal) * 100) : 0;
            ?>
            <div class="mb-8">
                <h2 class="text-sm font-semibold text-slate-700 mb-4">Leave Credits by Type (<?php echo (int)$currentYear; ?>)</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Card: Sick Leave (SL) -->
                    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700">Sick Leave (SL)</span>
                            <span class="text-xs text-slate-500"><?php echo (int)$slRemaining; ?> / <?php echo (int)$slTotal; ?> days</span>
                        </div>
                        <div class="h-8 bg-slate-100 rounded-lg overflow-hidden flex" title="Used: <?php echo (int)$slUsed; ?> days, Remaining: <?php echo (int)$slRemaining; ?> days">
                            <div class="bg-sky-500 h-full transition-all" style="width: <?php echo $slUsedPct; ?>%;" title="Used"></div>
                            <div class="bg-emerald-500 h-full transition-all" style="width: <?php echo $slRemPct; ?>%;" title="Remaining"></div>
                        </div>
                        <div class="flex gap-4 mt-2 text-xs text-slate-500">
                            <span><span class="inline-block w-2.5 h-2.5 rounded bg-sky-500 align-middle mr-1"></span> Used <?php echo (int)$slUsed; ?></span>
                            <span><span class="inline-block w-2.5 h-2.5 rounded bg-emerald-500 align-middle mr-1"></span> Remaining <?php echo (int)$slRemaining; ?></span>
                        </div>
                    </section>
                    <!-- Card: Vacation Leave (VL) -->
                    <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700">Vacation Leave (VL)</span>
                            <span class="text-xs text-slate-500"><?php echo (int)$vlRemaining; ?> / <?php echo (int)$vlTotal; ?> days</span>
                        </div>
                        <div class="h-8 bg-slate-100 rounded-lg overflow-hidden flex" title="Used: <?php echo (int)$vlUsed; ?> days, Remaining: <?php echo (int)$vlRemaining; ?> days">
                            <div class="bg-sky-500 h-full transition-all" style="width: <?php echo $vlUsedPct; ?>%;" title="Used"></div>
                            <div class="bg-emerald-500 h-full transition-all" style="width: <?php echo $vlRemPct; ?>%;" title="Remaining"></div>
                        </div>
                        <div class="flex gap-4 mt-2 text-xs text-slate-500">
                            <span><span class="inline-block w-2.5 h-2.5 rounded bg-sky-500 align-middle mr-1"></span> Used <?php echo (int)$vlUsed; ?></span>
                            <span><span class="inline-block w-2.5 h-2.5 rounded bg-emerald-500 align-middle mr-1"></span> Remaining <?php echo (int)$vlRemaining; ?></span>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Leave Allocation History + Recent Leave Requests: one row, separate cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Card: Leave Allocation History -->
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">Leave Allocation History</h2>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date Given</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Leave Type</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Year</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Days</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($allocationHistory)): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No leave allocation history found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allocationHistory as $alloc): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($alloc['given_at']); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($alloc['leave_type']); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($alloc['year']); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo (int)$alloc['total_days']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Card: Recent Leave Requests -->
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Recent Leave Requests</h2>
                        <a href="request.php" class="text-sm text-[#FA9800] hover:underline">View all requests</a>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Date</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Leave Type</th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($recentLeaveRequests)): ?>
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">No leave requests yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentLeaveRequests as $req): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($req['date']); ?></td>
                                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($req['type']); ?></td>
                                            <td class="px-4 py-3">
                                                <?php
                                                $status = $req['status'];
                                                $badgeClasses = ['Approved' => 'bg-emerald-100 text-emerald-700', 'Rejected' => 'bg-red-100 text-red-700', 'Declined' => 'bg-red-100 text-red-700', 'Pending' => 'bg-amber-100 text-amber-700', 'Cancelled' => 'bg-slate-100 text-slate-700'];
                                                $class = $badgeClasses[$status] ?? 'bg-slate-100 text-slate-700';
                                                ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- New Leave Request Modal -->
    <div id="newLeaveRequestModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">New Leave Request</h3>
                <button id="closeLeaveModal" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            <form id="leaveRequestForm" class="p-6">
                <div id="unpaidLeaveNote" class="hidden mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
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
                    <button type="submit" id="submitLeaveBtn" class="flex-1 px-4 py-2 bg-[#FA9800] text-white font-medium rounded-lg hover:bg-[#d18a15] transition-colors">Submit Request</button>
                    <button type="button" id="cancelLeaveBtn" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-lg hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
                <div id="leaveRequestMessage" class="mt-4 hidden"></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="include/sidebar-employee.js"></script>
    <script>
    $(function () {
        var leaveTypeTotals = {
            'Sick Leave': <?php echo (int)($slTotal ?? 0); ?>,
            'Vacation Leave': <?php echo (int)($vlTotal ?? 0); ?>,
            'Bereavement Leave': <?php echo (int)($vlTotal ?? 0); ?>,
            'Emergency Leave': <?php echo (int)($vlTotal ?? 0); ?>
        };
        $('#newLeaveRequestBtn').on('click', function () {
            $('#newLeaveRequestModal').removeClass('hidden').addClass('flex');
            var today = new Date().toISOString().split('T')[0];
            $('#start_date, #end_date').attr('min', today);
            $('#unpaidLeaveNote').addClass('hidden');
        });
        $(document).on('change', '#leave_type', function () {
            var selectedType = $(this).val();
            var applicableTypes = ['Sick Leave', 'Vacation Leave', 'Bereavement Leave', 'Emergency Leave'];
            if (applicableTypes.indexOf(selectedType) !== -1) {
                var total = leaveTypeTotals[selectedType] || 0;
                $('#unpaidLeaveNote').toggle(total === 0);
            } else {
                $('#unpaidLeaveNote').addClass('hidden');
            }
        });
        $(document).on('click', '#closeLeaveModal, #cancelLeaveBtn', function () {
            $('#newLeaveRequestModal').removeClass('flex').addClass('hidden');
            $('#leaveRequestForm')[0].reset();
        });
        $(document).on('click', '#newLeaveRequestModal', function (e) {
            if ($(e.target).attr('id') === 'newLeaveRequestModal') {
                $('#newLeaveRequestModal').removeClass('flex').addClass('hidden');
                $('#leaveRequestForm')[0].reset();
            }
        });
        $(document).on('submit', '#leaveRequestForm', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $('#leaveRequestMessage').addClass('hidden').html('');
            $('#submitLeaveBtn').prop('disabled', true).text('Submitting...');
            $.ajax({
                url: 'submit-leave-request.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (res) {
                    $('#submitLeaveBtn').prop('disabled', false).text('Submit Request');
                    if (res.status === 'success') {
                        $('#leaveRequestMessage').removeClass('hidden').addClass('p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm').html(res.message);
                        $('#leaveRequestForm')[0].reset();
                        setTimeout(function () { $('#newLeaveRequestModal').removeClass('flex').addClass('hidden'); location.reload(); }, 1500);
                    } else {
                        $('#leaveRequestMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(res.message || 'Failed to submit leave request');
                    }
                },
                error: function (xhr, status, error) {
                    $('#submitLeaveBtn').prop('disabled', false).text('Submit Request');
                    var m = 'Failed to submit leave request. Please try again.';
                    try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch (e) {}
                    $('#leaveRequestMessage').removeClass('hidden').addClass('p-3 bg-red-50 text-red-700 rounded-lg text-sm').html(m);
                }
            });
        });
    });
    </script>
</body>
</html>
