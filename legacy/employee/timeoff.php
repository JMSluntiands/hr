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
    <script src="../assets/js/manila-time.js"></script>
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
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



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
                        <label for="end_date" class="block text-sm font-medium text-slate-700 mb-2">Return Date <span class="text-red-500">*</span></label>
                        <input type="date" id="end_date" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent">
                    </div>
                    <div>
                        <label for="reason" class="block text-sm font-medium text-slate-700 mb-2">Remarks <span class="text-red-500">*</span></label>
                        <textarea id="reason" name="reason" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="Please provide remarks for your leave request"></textarea>
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
    <script src="/assets/js/sidebar-dropdown.js"></script>
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
            var today = (window.HrManilaTime && HrManilaTime.getTodayYmd) ? HrManilaTime.getTodayYmd() : new Date().toISOString().split('T')[0];
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
