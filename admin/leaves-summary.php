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

// Fetch leave summary per employee
$leaveSummary = [];
if ($conn) {
    // Check if leave_requests table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    $hasLeaveRequests = $checkTable && $checkTable->num_rows > 0;
    
    $currentYear = date('Y');
    
    $query = "SELECT 
                e.id,
                e.employee_id,
                e.full_name,
                e.department,
                COALESCE(SUM(CASE WHEN la.leave_type = 'Sick Leave' THEN la.total_days ELSE 0 END), 0) as sl_total,
                COALESCE((
                    SELECT SUM(CASE 
                        WHEN lr.start_date = lr.end_date THEN 1
                        ELSE COALESCE(lr.days, DATEDIFF(lr.end_date, lr.start_date) + 1)
                    END)
                    FROM leave_requests lr
                    WHERE lr.employee_id = e.id 
                    AND lr.leave_type = 'Sick Leave'
                    AND lr.status = 'Approved'
                    AND YEAR(lr.start_date) = " . (int)$currentYear . "
                ), 0) as sl_used,
                COALESCE(SUM(CASE WHEN la.leave_type = 'Vacation Leave' THEN la.total_days ELSE 0 END), 0) as vl_total,
                COALESCE((
                    SELECT SUM(CASE 
                        WHEN lr.start_date = lr.end_date THEN 1
                        ELSE COALESCE(lr.days, DATEDIFF(lr.end_date, lr.start_date) + 1)
                    END)
                    FROM leave_requests lr
                    WHERE lr.employee_id = e.id 
                    AND lr.leave_type = 'Vacation Leave'
                    AND lr.status = 'Approved'
                    AND YEAR(lr.start_date) = " . (int)$currentYear . "
                ), 0) as vl_used
              FROM employees e
              LEFT JOIN leave_allocations la ON e.id = la.employee_id AND la.year = " . (int)$currentYear . "
              WHERE e.status = 'Active'
              GROUP BY e.id, e.employee_id, e.full_name, e.department
              ORDER BY e.full_name ASC";
    
    // Only execute query if leave_requests table exists
    if ($hasLeaveRequests) {
        $result = $conn->query($query);
    } else {
        $result = false;
    }
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate remaining days
            $row['sl_remaining'] = max(0, $row['sl_total'] - $row['sl_used']);
            $row['vl_remaining'] = max(0, $row['vl_total'] - $row['vl_used']);
            $leaveSummary[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Summary per Employee - Admin</title>
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
    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Leave Summary per Employee</h1>
                <p class="text-sm text-slate-500 mt-1">View leave balances for all employees</p>
            </div>
        </div>

        <!-- DataTable -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6 overflow-x-auto">
                <table id="summaryTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Department</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Sick Leave</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wide">Vacation Leave</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveSummary as $summary): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($summary['full_name']); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo htmlspecialchars($summary['employee_id']); ?></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($summary['department'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-3">
                                <div class="text-sm">
                                    <div>Total: <span class="font-medium"><?php echo (int)$summary['sl_total']; ?></span></div>
                                    <div>Used: <span class="text-amber-600"><?php echo (int)$summary['sl_used']; ?></span></div>
                                    <div>Remaining: <span class="font-medium text-emerald-600"><?php echo (int)$summary['sl_remaining']; ?></span></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">
                                    <div>Total: <span class="font-medium"><?php echo (int)$summary['vl_total']; ?></span></div>
                                    <div>Used: <span class="text-amber-600"><?php echo (int)$summary['vl_used']; ?></span></div>
                                    <div>Remaining: <span class="font-medium text-emerald-600"><?php echo (int)$summary['vl_remaining']; ?></span></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            $('#summaryTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']],
                language: {
                    search: "",
                    searchPlaceholder: "Search employees...",
                    emptyTable: "No leave summary found."
                }
            });
        });

        // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
