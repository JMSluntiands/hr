<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

// Include database connection
include '../database/db.php';

$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Fetch employees for allocation
$employees = [];
if ($conn) {
    $query = "SELECT id, employee_id, full_name, email, department 
              FROM employees 
              WHERE status = 'Active'
              ORDER BY full_name ASC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
}

// Fetch leave allocations
$allocations = [];
if ($conn) {
    // Check if leave_allocations table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'leave_allocations'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $allocQuery = "SELECT la.*, e.full_name, e.employee_id 
                       FROM leave_allocations la
                       JOIN employees e ON la.employee_id = e.id
                       WHERE la.year = YEAR(CURDATE())
                       ORDER BY e.full_name ASC, la.leave_type ASC";
        $allocResult = $conn->query($allocQuery);
        
        if ($allocResult && $allocResult->num_rows > 0) {
            while ($row = $allocResult->fetch_assoc()) {
                $allocations[] = $row;
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
    <title>Allocation of Leave - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
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
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Allocation of Leave</h1>
                <p class="text-sm text-slate-500 mt-1">Manage leave allocations for employees</p>
            </div>
            <button onclick="openAllocationModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Allocate Leave
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- DataTable -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6">
                <table id="allocationTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Leave Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Total Days</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Used Days</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Remaining Days</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Year</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allocations as $alloc): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($alloc['full_name']); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo htmlspecialchars($alloc['employee_id']); ?></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($alloc['leave_type']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo (int)$alloc['total_days']; ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo (int)$alloc['used_days']; ?></td>
                            <td class="px-4 py-3">
                                <span class="font-medium <?php echo (int)$alloc['remaining_days'] > 0 ? 'text-emerald-600' : 'text-red-600'; ?>">
                                    <?php echo (int)$alloc['remaining_days']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($alloc['year']); ?></td>
                            <td class="px-4 py-3">
                                <button class="text-[#d97706] hover:text-[#b45309]" title="Edit" onclick="editAllocation(<?php echo $alloc['id']; ?>)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Allocation Modal -->
    <div id="allocationModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-xl font-semibold text-slate-800">Allocate Leave</h2>
            </div>
            <form id="allocationForm" method="POST" action="leaves-allocation-process.php" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Employee <span class="text-red-500">*</span></label>
                    <select name="employee_id" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name'] . ' (' . $emp['employee_id'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Leave Type <span class="text-red-500">*</span></label>
                    <select name="leave_type" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        <option value="">Select Leave Type</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Vacation Leave">Vacation Leave</option>
                        <option value="Emergency Leave">Emergency Leave</option>
                        <option value="Bereavement Leave">Bereavement Leave</option>
                        <option value="Maternity Leave">Maternity Leave</option>
                        <option value="Paternity Leave">Paternity Leave</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Total Days <span class="text-red-500">*</span></label>
                    <input type="number" name="total_days" required min="0" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Year <span class="text-red-500">*</span></label>
                    <input type="number" name="year" required value="<?php echo date('Y'); ?>" min="2020" max="2099" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeAllocationModal()" class="px-6 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium">
                        Allocate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#allocationTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']],
                language: {
                    search: "",
                    searchPlaceholder: "Search allocations...",
                    emptyTable: "No leave allocations found."
                }
            });
        });

        function openAllocationModal() {
            document.getElementById('allocationModal').classList.remove('hidden');
        }

        function closeAllocationModal() {
            document.getElementById('allocationModal').classList.add('hidden');
        }

        function editAllocation(id) {
            // TODO: Implement edit functionality
            alert('Edit functionality coming soon for allocation ID: ' + id);
        }

        // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
