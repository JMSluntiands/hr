<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$logs = [];
if ($conn) {
    // Check if activity_logs table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'activity_logs'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $sql = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 500";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $logs[] = $row;
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
    <title>Activity Log - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Activity Log</h1>
                <p class="text-sm text-slate-500 mt-1">View all system activities and actions</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-6">
                <table id="activityTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Date & Time</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Action</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Entity Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Description</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-600"><?php echo !empty($log['created_at']) ? date('M d, Y H:i:s', strtotime($log['created_at'])) : '—'; ?></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($log['user_name'] ?? 'Unknown'); ?></div>
                                <div class="text-xs text-slate-500">ID: <?php echo (int)$log['user_id']; ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    $action = $log['action'] ?? '';
                                    echo strpos($action, 'Approve') !== false ? 'bg-emerald-100 text-emerald-700' : 
                                         (strpos($action, 'Decline') !== false || strpos($action, 'Reject') !== false ? 'bg-red-100 text-red-700' : 
                                         (strpos($action, 'Add') !== false || strpos($action, 'Create') !== false ? 'bg-blue-100 text-blue-700' : 
                                         (strpos($action, 'Update') !== false ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'))); 
                                    ?>">
                                    <?php echo htmlspecialchars($action); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($log['entity_type'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($log['description'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-500 text-xs"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        $(function() {
            $('#activityTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                language: { search: '', searchPlaceholder: 'Search activities...', emptyTable: 'No activity logs found.' }
            });
        });

        // Sidebar dropdown functionality is handled by include/sidebar-dropdown.js
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
