<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

$_role = $_SESSION['role'] ?? '';
$sessionRole = is_string($_role) ? strtolower($_role) : '';
if ($sessionRole !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e === null) {
        return;
    }
    $t = (int)($e['type'] ?? 0);
    if (in_array($t, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[inventory/decommission-request.php fatal] ' . ($e['message'] ?? '') . ' @ ' . ($e['file'] ?? '') . ':' . ($e['line'] ?? ''));
    }
});

include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../include/inventory_decommission_helpers.php';
require_once __DIR__ . '/database/setup_inventory_decommission_requests_table.php';
require_once __DIR__ . '/database/setup_inventory_items_table.php';
require_once __DIR__ . '/include/inventory-activity-logger.php';
require_once __DIR__ . '/../admin/include/activity-logger.php';

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

ensureInventoryDecommissionRequestsTable($conn);
ensureInventoryItemsTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_decommission_status') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? '');
        $remark = trim((string)($_POST['resolution_remark'] ?? ''));

        if ($requestId > 0 && ($newStatus === 'approved' || $newStatus === 'declined')) {
            $reviewerId = (int)$_SESSION['user_id'];
            $reviewerName = (string)($_SESSION['name'] ?? 'Admin');
            $stmt = $conn->prepare("
                UPDATE inventory_decommission_requests
                SET status = ?,
                    resolution_remark = ?,
                    reviewed_by_user_id = ?,
                    reviewed_by_name = ?,
                    resolved_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            if ($stmt) {
                $conn->begin_transaction();
                try {
                    $stmt->bind_param('ssisi', $newStatus, $remark, $reviewerId, $reviewerName, $requestId);
                    $stmt->execute();
                    $affected = $stmt->affected_rows;
                    $stmt->close();

                    if ($affected > 0 && $newStatus === 'approved') {
                        if (!inventory_finalize_decommission_approved_request($conn, $requestId, $remark)) {
                            throw new RuntimeException('Could not finalize decommission for inventory item.');
                        }
                    }

                    if ($affected > 0) {
                        $label = $newStatus === 'approved' ? 'Approve' : 'Decline';
                        $itemCode = '';
                        $q = $conn->prepare('SELECT item_code FROM inventory_decommission_requests WHERE id = ? LIMIT 1');
                        if ($q) {
                            $q->bind_param('i', $requestId);
                            $q->execute();
                            $rw = inventory_stmt_fetch_one_assoc($q);
                            $q->close();
                            $itemCode = is_array($rw) ? trim((string)($rw['item_code'] ?? '')) : '';
                        }
                        $desc = "{$label}d decommission request #{$requestId}" . ($itemCode !== '' ? " (Item ID: {$itemCode})" : '') . ' by ' . $reviewerName . '.';
                        logActivity($conn, $label . ' Decommission Request', 'decommission_request', $requestId, $desc);
                        inventoryLogActivity(
                            $conn,
                            inventoryActionWithItemCode($label . ' Decommission Request', $itemCode !== '' ? $itemCode : 'REQ-' . $requestId),
                            'DecommissionRequest',
                            $requestId,
                            $desc,
                            $remark !== '' ? 'Remark: ' . $remark : null,
                            $itemCode !== '' ? $itemCode : null
                        );
                    }

                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    error_log('Decommission approval transaction failed: ' . $e->getMessage());
                }
            }
        }
        header('Location: decommission-request.php?status=updated');
        exit;
    }
}

$status = (string)($_GET['status'] ?? '');
$requests = [];
$approvedDecommissionRows = [];

$decomSelectSql = "
    SELECT
        r.id,
        r.company_name,
        r.request_employee_name,
        r.equipment_name,
        r.item_code,
        r.equipment_type,
        r.serial_number,
        r.equipment_description,
        r.brand_manufacturer,
        r.item_date_received,
        r.date_decommissioning,
        r.reason_decommissioning,
        r.test_1_notes,
        r.test_1_date,
        r.test_2_notes,
        r.test_2_date,
        r.test_3_notes,
        r.test_3_date,
        r.test_1_attachment_paths,
        r.test_2_attachment_paths,
        r.test_3_attachment_paths,
        r.attachment_path,
        r.status,
        r.resolution_remark,
        r.reviewed_by_name,
        r.resolved_at,
        r.created_at,
        e.full_name,
        e.employee_id AS employee_code
    FROM inventory_decommission_requests r
    JOIN employees e ON e.id = r.employee_id
";

$result = $conn->query($decomSelectSql . "
    WHERE r.status IN ('pending', 'declined')
    ORDER BY
        CASE r.status WHEN 'pending' THEN 0 ELSE 1 END ASC,
        r.created_at DESC,
        r.id DESC
");
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    $result->free();
} elseif ($result === false) {
    error_log('decommission-request.php pending/declined query failed: ' . $conn->error);
}

$approvedRes = $conn->query("
    SELECT
        r.id,
        r.company_name,
        r.request_employee_name,
        r.equipment_name,
        r.item_code,
        r.equipment_type,
        r.serial_number,
        r.equipment_description,
        r.brand_manufacturer,
        r.item_date_received,
        r.date_decommissioning,
        r.reason_decommissioning,
        r.test_1_notes,
        r.test_1_date,
        r.test_2_notes,
        r.test_2_date,
        r.test_3_notes,
        r.test_3_date,
        r.test_1_attachment_paths,
        r.test_2_attachment_paths,
        r.test_3_attachment_paths,
        r.attachment_path,
        r.status,
        r.resolution_remark,
        r.reviewed_by_name,
        r.resolved_at,
        r.created_at,
        e.full_name,
        e.employee_id AS employee_code,
        ii.item_name AS live_item_name,
        ii.description AS live_description,
        ii.`type` AS live_type,
        ii.brand_manufacturer AS live_brand,
        ii.item_condition AS live_condition,
        ii.remarks AS live_remarks,
        ii.date_arrived AS live_date_arrived
    FROM inventory_decommission_requests r
    JOIN employees e ON e.id = r.employee_id
    LEFT JOIN inventory_items ii ON ii.item_id = r.item_code
    WHERE r.status = 'approved'
    ORDER BY r.resolved_at DESC, r.id DESC
");
if ($approvedRes instanceof mysqli_result) {
    while ($row = $approvedRes->fetch_assoc()) {
        $approvedDecommissionRows[] = $row;
    }
    $approvedRes->free();
} elseif ($approvedRes === false) {
    error_log('decommission-request.php approved query failed: ' . $conn->error);
}

$pendingCount = 0;
$pc = $conn->query("SELECT COUNT(*) AS c FROM inventory_decommission_requests WHERE status = 'pending'");
if ($pc && $prow = $pc->fetch_assoc()) {
    $pendingCount = (int)($prow['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decommission Requests</title>
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
                <h1 class="text-2xl font-semibold text-slate-800">Equipment Decommissioning Requests</h1>
                <p class="text-sm text-slate-500">Review employee submissions; approve or decline with an optional note. <span class="text-slate-600">Submitted and resolved times are Philippine Standard Time (Asia/Manila).</span></p>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 self-start">
                Pending: <?php echo (int)$pendingCount; ?>
            </span>
        </div>

        <?php if ($status === 'updated'): ?>
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                Request updated.
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Pending and declined requests</h2>
                <p class="text-sm text-slate-500">Approve pending requests to remove the item from active inventory and allocation.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Requester</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Equipment / Item ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Submitted</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reviewer / Resolved</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                    No pending or declined decommission requests.
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
                                    <td class="px-4 py-3 align-top">
                                        <?php if ($st === 'pending'): ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Declined</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 align-top"><?php echo inventory_decommission_html_escape($employeeLabel); ?></td>
                                    <td class="px-4 py-3 text-slate-700 align-top">
                                        <div class="font-medium"><?php echo inventory_decommission_html_escape((string)$row['equipment_name']); ?></div>
                                        <div class="text-xs text-slate-500">ID: <?php echo inventory_decommission_html_escape((string)$row['item_code']); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 align-top whitespace-nowrap">
                                        <?php echo inventory_decommission_html_escape(inventory_decommission_format_datetime_manila($created)); ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 text-xs align-top">
                                        <?php if (trim((string)($row['reviewed_by_name'] ?? '')) !== ''): ?>
                                            <div class="text-slate-700 font-medium"><?php echo inventory_decommission_html_escape((string)$row['reviewed_by_name']); ?></div>
                                        <?php else: ?>
                                            <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                        <?php if ($resolved !== ''): ?>
                                            <div class="mt-1 text-slate-500"><?php echo inventory_decommission_html_escape(inventory_decommission_format_datetime_manila($resolved)); ?></div>
                                        <?php endif; ?>
                                        <?php if (trim((string)($row['resolution_remark'] ?? '')) !== ''): ?>
                                            <div class="mt-1 text-slate-600"><?php echo nl2br(inventory_decommission_html_escape((string)$row['resolution_remark'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 text-xs align-top max-w-xs">
                                        <details class="cursor-pointer">
                                            <summary class="text-[#FA9800] font-medium">View form</summary>
                                            <div class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                                <?php $detailsHrefPrefix = '../'; include __DIR__ . '/include/decommission-request-details.inc.php'; ?>
                                            </div>
                                        </details>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <?php if ($isPending): ?>
                                            <div class="flex flex-col gap-2 min-w-[200px]">
                                                <form method="POST" class="space-y-1">
                                                    <input type="hidden" name="action" value="update_decommission_status">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="approved">
                                                    <textarea name="resolution_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Optional note"></textarea>
                                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-600 text-white hover:opacity-90">Approve</button>
                                                </form>
                                                <form method="POST" class="space-y-1">
                                                    <input type="hidden" name="action" value="update_decommission_status">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)$row['id']; ?>">
                                                    <input type="hidden" name="new_status" value="declined">
                                                    <textarea name="resolution_remark" rows="2" class="w-full border border-slate-300 rounded-lg px-2 py-1 text-xs" placeholder="Optional reason"></textarea>
                                                    <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-red-600 text-white hover:opacity-90">Decline</button>
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

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4 mt-8">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">Approved decommissioning</h2>
                <p class="text-sm text-slate-500">Same fields as List Item. These assets are removed from the main inventory list and cannot be allocated again.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item name</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Brand / Mfr</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Condition</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Remarks</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date arrived</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Requester</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Approved at</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Reviewer</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($approvedDecommissionRows)): ?>
                            <tr>
                                <td colspan="12" class="px-4 py-8 text-center text-slate-500 text-sm">No approved decommissioning yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvedDecommissionRows as $ar): ?>
                                <?php
                                $dispName = trim((string)($ar['live_item_name'] ?? '')) !== '' ? (string)$ar['live_item_name'] : (string)($ar['equipment_name'] ?? '');
                                $dispDesc = trim((string)($ar['live_description'] ?? '')) !== '' ? (string)$ar['live_description'] : (string)($ar['equipment_description'] ?? '');
                                $dispType = trim((string)($ar['live_type'] ?? '')) !== '' ? (string)$ar['live_type'] : (string)($ar['equipment_type'] ?? '');
                                $dispBrand = trim((string)($ar['live_brand'] ?? '')) !== '' ? (string)$ar['live_brand'] : (string)($ar['brand_manufacturer'] ?? '');
                                $dispCond = trim((string)($ar['live_condition'] ?? '')) !== '' ? (string)$ar['live_condition'] : 'Decommissioned';
                                $dispRem = trim((string)($ar['live_remarks'] ?? '')) !== '' ? (string)$ar['live_remarks'] : '—';
                                $dispArrived = (string)($ar['live_date_arrived'] ?? '');
                                $reqLabel = inventory_decommission_html_escape((string)($ar['full_name'] ?? '') . ' (' . (string)($ar['employee_code'] ?? '') . ')');
                                $resolvedAt = (string)($ar['resolved_at'] ?? '');
                                $row = $ar;
                                ?>
                                <tr class="bg-emerald-50/20">
                                    <td class="px-4 py-3 text-slate-800 font-mono text-xs"><?php echo inventory_decommission_html_escape((string)$ar['item_code']); ?></td>
                                    <td class="px-4 py-3 text-slate-800 font-medium"><?php echo inventory_decommission_html_escape($dispName); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs max-w-[200px]"><?php echo nl2br(inventory_decommission_html_escape($dispDesc !== '' ? $dispDesc : '—')); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs"><?php echo inventory_decommission_html_escape($dispType !== '' ? $dispType : '—'); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs"><?php echo inventory_decommission_html_escape($dispBrand !== '' ? $dispBrand : '—'); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs"><?php echo inventory_decommission_html_escape($dispCond); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs max-w-[160px]"><?php echo nl2br(inventory_decommission_html_escape($dispRem)); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap"><?php echo inventory_decommission_html_escape(inventory_decommission_format_date_manila($dispArrived)); ?></td>
                                    <td class="px-4 py-3 text-slate-700 text-xs"><?php echo $reqLabel; ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap"><?php echo inventory_decommission_html_escape(inventory_decommission_format_datetime_manila($resolvedAt)); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs"><?php echo inventory_decommission_html_escape(trim((string)($ar['reviewed_by_name'] ?? '')) !== '' ? (string)$ar['reviewed_by_name'] : '—'); ?></td>
                                    <td class="px-4 py-3 text-slate-600 text-xs align-top max-w-xs">
                                        <details class="cursor-pointer">
                                            <summary class="text-[#FA9800] font-medium">View form</summary>
                                            <div class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                                <?php $detailsHrefPrefix = '../'; include __DIR__ . '/include/decommission-request-details.inc.php'; ?>
                                            </div>
                                        </details>
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
