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
require_once __DIR__ . '/database/setup_inventory_items_table.php';
require_once __DIR__ . '/database/setup_inventory_item_allocations_table.php';
require_once __DIR__ . '/include/inventory-activity-logger.php';

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

ensureInventoryItemsTable($conn);
ensureInventoryItemAllocationsTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'mark_read') {
        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        if ($allocationId > 0) {
            $itemCode = inventoryGetItemCodeByAllocationId($conn, $allocationId);
            $stmt = $conn->prepare("
                UPDATE inventory_item_allocations
                SET admin_viewed_at = NOW()
                WHERE id = ? AND employee_appeal IS NOT NULL AND TRIM(employee_appeal) <> ''
            ");
            if ($stmt) {
                $stmt->bind_param('i', $allocationId);
                $stmt->execute();
                $stmt->close();
                $desc = 'Admin marked inventory appeal message as read (allocation #' . $allocationId . ').';
                inventoryLogActivity($conn, inventoryActionWithItemCode('Mark Message Read', $itemCode), 'Message', $allocationId, $desc, null, $itemCode);
            }
        }
        header('Location: messages.php?status=updated');
        exit;
    }

    if ($action === 'mark_all_read') {
        $conn->query("
            UPDATE inventory_item_allocations
            SET admin_viewed_at = NOW()
            WHERE employee_appeal IS NOT NULL
              AND TRIM(employee_appeal) <> ''
              AND admin_viewed_at IS NULL
        ");
        inventoryLogActivity($conn, inventoryActionWithItemCode('Mark All Messages Read', 'MULTI'), 'Message', null, 'Admin marked all unread inventory appeal messages as read.', null, 'MULTI');
        header('Location: messages.php?status=updated');
        exit;
    }
}

$status = (string)($_GET['status'] ?? '');
$messages = [];
$unreadCount = 0;

$unreadResult = $conn->query("
    SELECT COUNT(*) AS total_unread
    FROM inventory_item_allocations
    WHERE employee_appeal IS NOT NULL
      AND TRIM(employee_appeal) <> ''
      AND admin_viewed_at IS NULL
");
if ($unreadResult && $unreadRow = $unreadResult->fetch_assoc()) {
    $unreadCount = (int)($unreadRow['total_unread'] ?? 0);
}

$result = $conn->query("
    SELECT
        ia.id,
        ia.employee_appeal,
        ia.employee_appeal_remarks,
        ia.employee_appeal_at,
        ia.admin_viewed_at,
        ii.item_id,
        ii.item_name,
        e.full_name,
        e.employee_id AS employee_code
    FROM inventory_item_allocations ia
    JOIN inventory_items ii ON ii.id = ia.inventory_item_id
    JOIN employees e ON e.id = ia.employee_id
    WHERE ia.employee_appeal IS NOT NULL
      AND TRIM(ia.employee_appeal) <> ''
    ORDER BY
      CASE WHEN ia.admin_viewed_at IS NULL THEN 0 ELSE 1 END ASC,
      ia.employee_appeal_at DESC,
      ia.id DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Messages</title>
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
                <h1 class="text-2xl font-semibold text-slate-800">Messages</h1>
                <p class="text-sm text-slate-500">Employee appeals for wrong inventory allocation.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1">
                    Unread: <?php echo (int)$unreadCount; ?>
                </span>
                <form method="POST">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] text-white hover:opacity-90">
                        Mark All as Read
                    </button>
                </form>
            </div>
        </div>

        <?php if ($status === 'updated'): ?>
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                Message status updated.
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
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Appeal</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Remarks</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Sent</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                    No employee appeals yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $row): ?>
                                <?php
                                $isUnread = empty($row['admin_viewed_at']);
                                $employeeLabel = (string)$row['full_name'] . ' (' . (string)$row['employee_code'] . ')';
                                $itemLabel = (string)$row['item_id'] . ' - ' . (string)$row['item_name'];
                                $sentAt = (string)($row['employee_appeal_at'] ?? '');
                                ?>
                                <tr class="<?php echo $isUnread ? 'bg-amber-50/40' : ''; ?>">
                                    <td class="px-4 py-3">
                                        <?php if ($isUnread): ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">New</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($employeeLabel); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($itemLabel); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo nl2br(htmlspecialchars((string)$row['employee_appeal'])); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars(trim((string)($row['employee_appeal_remarks'] ?? '')) !== '' ? (string)$row['employee_appeal_remarks'] : '—'); ?></td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <?php echo $sentAt !== '' ? htmlspecialchars(date('M d, Y h:i A', strtotime($sentAt))) : '—'; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($isUnread): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="allocation_id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-[#FA9800] text-white hover:opacity-90">
                                                    Mark as Read
                                                </button>
                                            </form>
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
