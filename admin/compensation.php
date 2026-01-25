<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

// Get all salary adjustments from database
$salaryAdjustments = [];

if ($conn) {
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_salary_adjustments'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $sql = "SELECT esa.*, e.full_name, e.employee_id 
                FROM employee_salary_adjustments esa
                LEFT JOIN employees e ON esa.employee_id = e.id
                ORDER BY esa.date_approved DESC, esa.created_at DESC";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $salaryAdjustments[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensation - Salary Adjustment & History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#E9A319',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: 14px;
            padding-left: 12px;
            line-height: 42px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 10px;
        }
        .select2-dropdown {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
    </style>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#FA9800] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($adminName); ?></div>
                <div class="text-xs text-white/80">Administrator</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- Employees Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="employees-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Employees</span>
                    <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="employees-dropdown" class="hidden space-y-1 mt-1">
                    <a href="staff-add" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Add New Employee
                    </a>
                    <a href="staff" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        List of Employee
                    </a>
                </div>
            </div>
            <!-- Leaves Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="leaves-dropdown" class="hidden space-y-1 mt-1">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Allocation of Leave
                    </a>
                    <a href="leaves-summary" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Leave Summary per Employee
                    </a>
                </div>
            </div>
            <!-- Request Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="request-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Request</span>
                    <svg id="request-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="request-dropdown" class="hidden space-y-1 mt-1">
                    <a href="request-leaves" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Request Leaves
                    </a>
                    <a href="request-document" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">
                        Request Document
                    </a>
                </div>
            </div>
            <a href="compensation" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors bg-white/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Compensation</span>
            </a>
            <a href="activity-log" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Activity Log</span>
            </a>
            <a href="announcement" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>Announcements</span>
            </a>
            <a href="accounts" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>Accounts</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
                <span>Role</span>
                <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen p-8 overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Compensation</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Salary Adjustment & History
                </p>
            </div>
            <button id="addAdjustmentBtn" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d97706] transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Salary Adjustment</span>
            </button>
        </div>

        <!-- Salary Adjustment & History Table -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-800">Salary Adjustment & History</h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="adjustmentsTable" class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Previous Salary</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">New Salary</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reason</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Approved By</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (!empty($salaryAdjustments)): ?>
                            <?php foreach ($salaryAdjustments as $adjustment): ?>
                                <tr>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div>
                                            <div class="font-medium"><?php echo htmlspecialchars($adjustment['full_name'] ?? 'N/A'); ?></div>
                                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($adjustment['employee_id'] ?? ''); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">₱<?php echo number_format($adjustment['previous_salary'], 2); ?></td>
                                    <td class="px-4 py-3 text-slate-700 font-semibold">₱<?php echo number_format($adjustment['new_salary'], 2); ?></td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($adjustment['reason']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($adjustment['approved_by'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo date('M d, Y', strtotime($adjustment['date_approved'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Add Salary Adjustment Modal -->
        <div id="addAdjustmentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-800">Add Salary Adjustment</h3>
                    <button id="closeModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form id="adjustmentForm" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Employee <span class="text-red-500">*</span></label>
                        <select id="employeeSelect" name="employee_id" class="w-full" required>
                            <option value="">Select Employee</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Previous Salary <span class="text-red-500">*</span></label>
                        <input type="number" id="previousSalary" name="previous_salary" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="0.00" required readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">New Salary <span class="text-red-500">*</span></label>
                        <input type="number" id="newSalary" name="new_salary" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Reason <span class="text-red-500">*</span></label>
                        <select id="reasonSelect" name="reason" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" required>
                            <option value="">Select Reason</option>
                            <option value="Promotion">Promotion</option>
                            <option value="Annual Increase">Annual Increase</option>
                            <option value="Adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Date <span class="text-red-500">*</span></label>
                        <input type="date" id="dateApproved" name="date_approved" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#FA9800] focus:border-transparent" required>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" id="cancelBtn" class="px-4 py-2 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg hover:bg-[#d97706] transition-colors">Save Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(function() {
            // Initialize DataTable
            $('#adjustmentsTable').DataTable({
                order: [[5, 'desc']],
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries found",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });

            // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js

            // Load employees for dropdown
            $.ajax({
                url: 'api/get-employees.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const select = $('#employeeSelect');
                        response.data.forEach(function(employee) {
                            select.append(new Option(
                                employee.employee_id + ' - ' + employee.full_name,
                                employee.id,
                                false,
                                false
                            ));
                        });
                    }
                },
                error: function() {
                    alert('Error loading employees. Please refresh the page.');
                }
            });

            // Initialize Select2
            $('#employeeSelect').select2({
                placeholder: 'Search and select employee...',
                allowClear: true,
                width: '100%'
            });

            // Set default date to today
            $('#dateApproved').val(new Date().toISOString().split('T')[0]);

            // Open modal
            $('#addAdjustmentBtn').on('click', function() {
                $('#addAdjustmentModal').removeClass('hidden').addClass('flex');
            });

            // Close modal
            $('#closeModal, #cancelBtn').on('click', function() {
                $('#addAdjustmentModal').removeClass('flex').addClass('hidden');
                $('#adjustmentForm')[0].reset();
                $('#employeeSelect').val(null).trigger('change');
                $('#previousSalary').val('');
            });

            // Get previous salary when employee is selected
            $('#employeeSelect').on('change', function() {
                const employeeId = $(this).val();
                if (employeeId) {
                    $.ajax({
                        url: 'api/get-employee-salary.php',
                        method: 'GET',
                        data: { employee_id: employeeId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success' && response.salary) {
                                $('#previousSalary').val(response.salary);
                            } else {
                                $('#previousSalary').val('0.00');
                            }
                        },
                        error: function() {
                            $('#previousSalary').val('0.00');
                        }
                    });
                } else {
                    $('#previousSalary').val('');
                }
            });

            // Handle form submission
            $('#adjustmentForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    employee_id: $('#employeeSelect').val(),
                    previous_salary: $('#previousSalary').val(),
                    new_salary: $('#newSalary').val(),
                    reason: $('#reasonSelect').val(),
                    date_approved: $('#dateApproved').val(),
                    approved_by: '<?php echo htmlspecialchars($adminName); ?>'
                };

                if (!formData.employee_id || !formData.previous_salary || !formData.new_salary || !formData.reason || !formData.date_approved) {
                    alert('Please fill in all required fields.');
                    return;
                }

                $.ajax({
                    url: 'api/save-salary-adjustment.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert('Salary adjustment saved successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.message || 'Failed to save salary adjustment.'));
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Failed to save salary adjustment.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch(e) {}
                        alert('Error: ' + errorMsg);
                    }
                });
            });
        });
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
