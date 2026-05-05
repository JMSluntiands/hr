<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/database/setup_inventory_item_requests_table.php';
require_once __DIR__ . '/include/inventory-activity-logger.php';

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

ensureInventoryItemRequestsTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_request_status') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? '');
        $adminRemark = trim((string)($_POST['admin_remark'] ?? ''));

        if ($requestId > 0 && ($newStatus === 'approved' || $newStatus === 'rejected')) {
            $remarkDb = $adminRemark;
            $stmt = $conn->prepare("
                UPDATE inventory_item_requests
                SET status = ?, admin_remark = ?, resolved_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            if ($stmt) {
                $stmt->bind_param('ssi', $newStatus, $remarkDb, $requestId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected > 0) {
                    $label = $newStatus === 'approved' ? 'Approve' : 'Reject';
                    inventoryLogActivity(
                        $conn,
                        'Inventory Item Request ' . $label,
                        'Request',
                        $requestId,
                        'Admin ' . strtolower($label) . 'ed inventory item request #' . $requestId . '.',
                        $adminRemark !== '' ? 'Admin remark: ' . $adminRemark : null,
                        null
                    );
                }
            }
        }
        header('Location: request.php?status=updated');
        exit;
    }
}

$status = (string)($_GET['status'] ?? '');
$requests = [];

$result = $conn->query("
    SELECT
        r.id,
        r.item_name,
        r.details,
        r.status,
        r.admin_remark,
        r.resolved_at,
        r.created_at,
        e.full_name,
        e.employee_id AS employee_code
    FROM inventory_item_requests r
    JOIN employees e ON e.id = r.employee_id
    ORDER BY
        CASE r.status WHEN 'pending' THEN 0 ELSE 1 END ASC,
        r.created_at DESC,
        r.id DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$pendingCount = 0;
$pc = $conn->query("SELECT COUNT(*) AS c FROM inventory_item_requests WHERE status = 'pending'");
if ($pc && $prow = $pc->fetch_assoc()) {
    $pendingCount = (int)($prow['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Item Requests</h1>
                <p class="text-sm text-slate-500">Mga request ng employees para sa bagong inventory items.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 self-start">
                Pending: <?php echo (int)$pendingCount; ?>
            </span>
        </div>

        <?php if ($status === 'updated'): ?>
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                Request status updated.
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Requested</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Admin / Resolved</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                    Walang item requests pa.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $row): ?>
                                <?php
                                $st = (string)$row['status'];
                                $isPending = $st === 'pending';
                                $employeeLabel = (string)$row['full_name'] . ' (' . (string)$row['employee_code'] . ')';
                                $created = (string)($row['created_at'] ?? '');
                                $resolved = (string)($row['resolved_at'] ?? '');
                                ?>
                                <tr class="<?php echo $isPending ? 'bg-amber-50/40' : ''; ?>">
                                    <td class="px-4 py-3">
                                        <?php if ($st === 'pending'): ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                        <?php elseif ($st === 'approved'): ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Approved</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($employeeLabel); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$row['item_name']); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo nl2br(htmlspecialchars(trim((string)($row['details'] ?? '')) !== '' ? (string)$row['details'] : '—')); ?></td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <?php echo $created !== '' ? htmlspecialchars(date('M d, Y h:i A', strtotime($created))) : '—'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 text-xs">
                                        <?php if (trim((string)($row['admin_remark'] ?? '')) !== ''): ?>
                                            <div class="text-slate-700"><?php echo nl2br(htmlspecialchars((string)$row['admin_remark'])); ?></div>
                                        <?php else: ?>
                                            <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                        <?php if ($resolved !== ''): ?>
                                            <div class="mt-1 text-slate-500"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($resolved))); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <?php if ($isPending): ?>
                                            <div class="flex flex-col gap-2 min-w-[200px]">
                                                <form method="POST" class="space-y-1">
                                                    <input type="hidden" name="action" value="update_request_status">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="approved">
                                                    <textarea name="admin_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Optional note sa employee"></textarea>
                                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-600 text-white hover:opacity-90">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST" class="space-y-1">
                                                    <input type="hidden" name="action" value="update_request_status">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="rejected">
                                                    <textarea name="admin_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Reason (optional)"></textarea>
                                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-red-600 text-white hover:opacity-90">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
