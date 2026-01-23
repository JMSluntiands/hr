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

// Fetch employees from database
$employees = [];
$departments = [];
if ($conn) {
    // Fetch all employees
    $query = "SELECT id, employee_id, full_name, email, phone, position, department, date_hired, status, profile_picture 
              FROM employees 
              ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    
    // Fetch unique departments for filter dropdown
    $deptQuery = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department";
    $deptResult = $conn->query($deptQuery);
    if ($deptResult && $deptResult->num_rows > 0) {
        while ($deptRow = $deptResult->fetch_assoc()) {
            $departments[] = $deptRow['department'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Employee - Admin</title>
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
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="leaves-dropdown" class="hidden space-y-1 mt-1">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white">
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
            <a href="compensation" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Compensation</span>
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
                <h1 class="text-2xl font-semibold text-slate-800">List of Employee</h1>
                <p class="text-sm text-slate-500 mt-1">Manage and view all employees in the system</p>
            </div>
            <a href="staff-add" class="inline-flex items-center gap-2 px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Employee
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Search</label>
                    <input type="text" id="searchInput" placeholder="Search by name, email..." 
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Department</label>
                    <select id="departmentFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Status</label>
                    <select id="statusFilter" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6">
                <table id="employeeTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Employee ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Name</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Email</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Position</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Department</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Date Hired</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): 
                            $photo = !empty($emp['profile_picture']) && file_exists(__DIR__ . '/../uploads/' . $emp['profile_picture']) ? $emp['profile_picture'] : null;
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($emp['employee_id'] ?? ''); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full overflow-hidden bg-slate-200 flex items-center justify-center flex-shrink-0">
                                        <?php if ($photo): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($photo); ?>" alt="" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="text-sm font-semibold text-slate-500"><?php echo strtoupper(substr($emp['full_name'] ?? '?', 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-medium text-slate-700"><?php echo htmlspecialchars($emp['full_name'] ?? ''); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($emp['email'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($emp['position'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($emp['department'] ?? ''); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($emp['status'] ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo htmlspecialchars($emp['status'] ?? 'Active'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($emp['date_hired']) ? date('M d, Y', strtotime($emp['date_hired'])) : 'N/A'; ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="staff-view.php?id=<?php echo $emp['id']; ?>" class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <a href="staff-edit.php?id=<?php echo $emp['id']; ?>" class="p-2 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <button type="button" class="view-documents-btn p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="View Documents" data-id="<?php echo $emp['id']; ?>" data-name="<?php echo htmlspecialchars($emp['full_name'] ?? ''); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Documents Modal -->
    <div id="documentsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Employee Documents</h3>
                    <p class="text-sm text-slate-500" id="documentsEmployeeName"></p>
                </div>
                <button type="button" id="closeDocumentsModal" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div id="documentsContent" class="space-y-4">
                    <div class="text-center text-slate-500 py-8">Loading documents...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var table = $('#employeeTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[6, 'desc']], // Sort by Date Hired (column 6) descending
                language: {
                    search: "",
                    searchPlaceholder: "Search employees...",
                    emptyTable: "No employees found. <a href='staff-add' class='text-[#d97706] hover:underline'>Add your first employee</a>"
                },
                dom: '<"flex justify-between items-center mb-4"<"flex gap-2"l><"flex gap-2"f>>rt<"flex justify-between items-center mt-4"<"text-sm text-slate-600"i><"flex gap-2"p>>',
            });

            // Custom search
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Department filter
            $('#departmentFilter').on('change', function() {
                table.column(4).search(this.value).draw();
            });

            // Status filter
            $('#statusFilter').on('change', function() {
                table.column(5).search(this.value).draw();
            });
        });

        
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

            function toggleEmployeesDropdown() {
                if (!employeesDropdown) return;
                closeOthers('employees');
                const isHidden = employeesDropdown.classList.contains('hidden');
                employeesDropdown.classList.toggle('hidden');
                if (employeesArrow) employeesArrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
            function toggleLeavesDropdown() {
                if (!leavesDropdown) return;
                closeOthers('leaves');
                const isHidden = leavesDropdown.classList.contains('hidden');
                leavesDropdown.classList.toggle('hidden');
                if (leavesArrow) leavesArrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
            function toggleRequestDropdown() {
                if (!requestDropdown) return;
                closeOthers('request');
                const isHidden = requestDropdown.classList.contains('hidden');
                requestDropdown.classList.toggle('hidden');
                if (requestArrow) requestArrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }

            if (employeesBtn) employeesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleEmployeesDropdown(); });
            if (leavesBtn) leavesBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleLeavesDropdown(); });
            if (requestBtn) requestBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); toggleRequestDropdown(); });

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

        // Documents Modal
        const documentsModal = document.getElementById('documentsModal');
        const documentsContent = document.getElementById('documentsContent');
        const documentsEmployeeName = document.getElementById('documentsEmployeeName');
        const closeDocumentsModal = document.getElementById('closeDocumentsModal');

        document.querySelectorAll('.view-documents-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const employeeId = this.dataset.id;
                const employeeName = this.dataset.name;
                documentsEmployeeName.textContent = employeeName;
                documentsContent.innerHTML = '<div class="text-center text-slate-500 py-8">Loading documents...</div>';
                documentsModal.classList.remove('hidden');
                documentsModal.classList.add('flex');

                // Fetch documents
                fetch('staff-documents.php?id=' + employeeId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.documents && data.documents.length > 0) {
                            let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                            data.documents.forEach(function(doc) {
                                const fileUrl = doc.file_path ? '../uploads/' + doc.file_path : '';
                                const isImage = fileUrl && /\.(jpg|jpeg|png|gif|webp)$/i.test(fileUrl);
                                const isPdf = fileUrl && /\.pdf$/i.test(fileUrl);
                                
                                html += '<div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50">';
                                html += '<div class="flex items-start justify-between mb-2">';
                                html += '<h4 class="font-medium text-slate-800">' + (doc.document_type || 'Document') + '</h4>';
                                if (doc.status) {
                                    const statusClass = doc.status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : 
                                                      doc.status === 'Pending' ? 'bg-amber-100 text-amber-700' : 
                                                      'bg-red-100 text-red-700';
                                    html += '<span class="px-2 py-0.5 rounded-full text-xs font-medium ' + statusClass + '">' + doc.status + '</span>';
                                }
                                html += '</div>';
                                
                                if (fileUrl) {
                                    if (isImage) {
                                        html += '<div class="mb-2"><img src="' + fileUrl + '" alt="' + (doc.document_type || '') + '" class="w-full h-32 object-cover rounded border border-slate-200"></div>';
                                    } else if (isPdf) {
                                        html += '<div class="mb-2 p-4 bg-red-50 rounded border border-red-200 text-center"><svg class="w-12 h-12 mx-auto text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg><span class="text-xs text-red-700">PDF Document</span></div>';
                                    } else {
                                        html += '<div class="mb-2 p-4 bg-slate-50 rounded border border-slate-200 text-center"><svg class="w-12 h-12 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg><span class="text-xs text-slate-700">Document File</span></div>';
                                    }
                                    html += '<a href="' + fileUrl + '" target="_blank" class="inline-flex items-center gap-1 text-sm text-[#d97706] hover:text-[#b45309] font-medium">View/Download <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg></a>';
                                } else {
                                    html += '<p class="text-sm text-slate-500">No file uploaded</p>';
                                }
                                
                                if (doc.created_at) {
                                    html += '<p class="text-xs text-slate-400 mt-2">Uploaded: ' + new Date(doc.created_at).toLocaleDateString() + '</p>';
                                }
                                html += '</div>';
                            });
                            html += '</div>';
                            documentsContent.innerHTML = html;
                        } else {
                            documentsContent.innerHTML = '<div class="text-center text-slate-500 py-8"><p>No documents found for this employee.</p></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        documentsContent.innerHTML = '<div class="text-center text-red-500 py-8"><p>Error loading documents. Please try again.</p></div>';
                    });
            });
        });

        closeDocumentsModal.addEventListener('click', function() {
            documentsModal.classList.add('hidden');
            documentsModal.classList.remove('flex');
        });

        documentsModal.addEventListener('click', function(e) {
            if (e.target === documentsModal) {
                documentsModal.classList.add('hidden');
                documentsModal.classList.remove('flex');
            }
        });
    </script>
</body>
</html>
