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
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- Employees Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="employees-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Employees</span>
                    <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="employees-dropdown" class="hidden space-y-1 mt-1">
                    <a href="staff-add" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
                        Add New Employee
                    </a>
                    <a href="staff" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
                        List of Employee
                    </a>
                </div>
            </div>
            <!-- Leaves Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 font-medium text-white">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="leaves-dropdown" class="hidden space-y-1 mt-1">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white bg-white/10">
                        Allocation of Leave
                    </a>
                    <a href="leaves-summary" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
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
                    <a href="request-leaves" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">Request Leaves</a>
                    <a href="request-document" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors">Request Document</a>
                </div>
            </div>
            <a href="compensation" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
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

        // Dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const employeesBtn = document.getElementById('employees-dropdown-btn');
            const employeesDropdown = document.getElementById('employees-dropdown');
            const employeesArrow = document.getElementById('employees-arrow');
            const leavesBtn = document.getElementById('leaves-dropdown-btn');
            const leavesDropdown = document.getElementById('leaves-dropdown');
            const leavesArrow = document.getElementById('leaves-arrow');
            const requestBtn = document.getElementById('request-dropdown-btn');
            const requestDropdown = document.getElementById('request-dropdown');
            const requestArrow = document.getElementById('request-arrow');

            function closeOthers(exclude) {
                if (exclude !== 'employees' && employeesDropdown) { employeesDropdown.classList.add('hidden'); if (employeesArrow) employeesArrow.style.transform = 'rotate(0deg)'; }
                if (exclude !== 'leaves' && leavesDropdown) { leavesDropdown.classList.add('hidden'); if (leavesArrow) leavesArrow.style.transform = 'rotate(0deg)'; }
                if (exclude !== 'request' && requestDropdown) { requestDropdown.classList.add('hidden'); if (requestArrow) requestArrow.style.transform = 'rotate(0deg)'; }
            }
            function toggleDropdown(dropdown, arrow) {
                if (!dropdown) return;
                const isHidden = dropdown.classList.contains('hidden');
                dropdown.classList.toggle('hidden');
                if (arrow) arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }

            if (employeesBtn) {
                employeesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); closeOthers('employees'); toggleDropdown(employeesDropdown, employeesArrow); });
            }
            if (leavesBtn) {
                leavesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); closeOthers('leaves'); toggleDropdown(leavesDropdown, leavesArrow); });
            }
            if (requestBtn) {
                requestBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); closeOthers('request'); toggleDropdown(requestDropdown, requestArrow); });
            }

            document.addEventListener('click', function(e) {
                if (employeesBtn && employeesDropdown && !employeesBtn.contains(e.target) && !employeesDropdown.contains(e.target)) {
                    employeesDropdown.classList.add('hidden');
                    if (employeesArrow) employeesArrow.style.transform = 'rotate(0deg)';
                }
                if (leavesBtn && leavesDropdown && !leavesBtn.contains(e.target) && !leavesDropdown.contains(e.target)) {
                    leavesDropdown.classList.add('hidden');
                    if (leavesArrow) leavesArrow.style.transform = 'rotate(0deg)';
                }
                if (requestBtn && requestDropdown && !requestBtn.contains(e.target) && !requestDropdown.contains(e.target)) {
                    requestDropdown.classList.add('hidden');
                    if (requestArrow) requestArrow.style.transform = 'rotate(0deg)';
                }
            });
        });
    </script>
</body>
</html>
