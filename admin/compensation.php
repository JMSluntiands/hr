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
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
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
