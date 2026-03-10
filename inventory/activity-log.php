<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

include __DIR__ . '/../database/db.php';

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

$logs = [];
$tableMissing = false;

if ($conn) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'inventory_activity_logs'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $sql = "
            SELECT *
            FROM inventory_activity_logs
            ORDER BY created_at DESC
            LIMIT 500
        ";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $logs[] = $row;
            }
        }
    } else {
        $tableMissing = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Activity Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-inventory.php'; ?>

    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Inventory Activity Log</h1>
            <p class="text-sm text-slate-500 mt-1">Track actions related to inventory module.</p>
        </div>

        <?php if ($tableMissing): ?>
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
                Activity log table is not available yet.
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="overflow-x-auto">
                <table id="inventoryActivityTable" class="display stripe hover w-full text-sm">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Updated By</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Change Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo !empty($log['created_at']) ? htmlspecialchars(date('M d, Y h:i:s A', strtotime($log['created_at']))) : '—'; ?></td>
                                <td>
                                    <?php echo htmlspecialchars((string)($log['user_name'] ?? '—')); ?>
                                    <?php if (!empty($log['user_id'])): ?>
                                        <div class="text-xs text-slate-500">ID: <?php echo (int)$log['user_id']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)($log['action'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['entity_type'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['item_code'] ?? '—')); ?></td>
                                <td><?php echo htmlspecialchars((string)($log['description'] ?? '')); ?></td>
                                <td class="whitespace-pre-wrap text-slate-600"><?php echo htmlspecialchars((string)($log['change_details'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        $(function () {
            $('#inventoryActivityTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    emptyTable: 'No inventory activity logs found.',
                    search: '',
                    searchPlaceholder: 'Search logs...'
                }
            });
        });
    </script>
</body>
</html>
