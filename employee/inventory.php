<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include '../database/db.php';
include 'include/employee_data.php';
require_once __DIR__ . '/../inventory/database/setup_inventory_items_table.php';
require_once __DIR__ . '/../inventory/database/setup_inventory_item_allocations_table.php';
require_once __DIR__ . '/../inventory/database/setup_inventory_item_requests_table.php';
require_once __DIR__ . '/../inventory/database/mysqli-stmt-fetch.php';
require_once __DIR__ . '/../inventory/include/inventory-activity-logger.php';

$allocatedItems = [];
$myItemRequests = [];
$tableMissing = false;
$status = (string)($_GET['status'] ?? '');
$message = (string)($_GET['message'] ?? '');
$rawInvView = (string)($_GET['view'] ?? 'list');
$inventoryView = in_array($rawInvView, ['list', 'request'], true) ? $rawInvView : 'list';

if ($conn && $employeeDbId) {
    ensureInventoryItemsTable($conn);
    ensureInventoryItemAllocationsTable($conn);
    ensureInventoryItemRequestsTable($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'submit_item_request') {
        $reqItemName = trim((string)($_POST['requested_item_name'] ?? ''));
        $reqDetails = trim((string)($_POST['requested_item_details'] ?? ''));

        if ($reqItemName === '') {
            header('Location: inventory.php?view=request&status=error&message=' . rawurlencode('Please enter the item you are requesting.'));
            exit;
        }

        $reqDetailsDb = $reqDetails === '' ? '' : $reqDetails;
        $ins = $conn->prepare("
            INSERT INTO inventory_item_requests (employee_id, item_name, details, status)
            VALUES (?, ?, ?, 'pending')
        ");
        if ($ins) {
            $ins->bind_param('iss', $employeeDbId, $reqItemName, $reqDetailsDb);
            $ok = $ins->execute();
            $newId = (int)$ins->insert_id;
            $ins->close();

            if ($ok && $newId > 0) {
                inventoryLogActivity(
                    $conn,
                    'Submit Inventory Item Request',
                    'Request',
                    $newId,
                    'Employee requested inventory item: ' . $reqItemName . '.',
                    $reqDetailsDb !== '' ? 'Details: ' . $reqDetailsDb : null,
                    null
                );
                header('Location: inventory.php?view=request&status=request_sent');
                exit;
            }
        }

        header('Location: inventory.php?view=request&status=error&message=' . rawurlencode('Could not submit your request. Please try again.'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'submit_appeal') {
        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        $appeal = trim((string)($_POST['employee_appeal'] ?? ''));
        $remarks = trim((string)($_POST['employee_appeal_remarks'] ?? ''));

        if ($allocationId <= 0 || $appeal === '') {
            header('Location: inventory.php?view=list&status=error&message=Please+provide+your+appeal+details.');
            exit;
        }

        $remarksDb = $remarks === '' ? null : $remarks;

        $updateStmt = $conn->prepare("
            UPDATE inventory_item_allocations
            SET
                employee_appeal = ?,
                employee_appeal_remarks = ?,
                employee_appeal_at = NOW(),
                admin_viewed_at = NULL
            WHERE id = ? AND employee_id = ? AND date_return IS NULL
        ");
        if ($updateStmt) {
            $updateStmt->bind_param('ssii', $appeal, $remarksDb, $allocationId, $employeeDbId);
            $ok = $updateStmt->execute();
            $affected = $updateStmt->affected_rows;
            $updateStmt->close();

            if ($ok && $affected > 0) {
                $itemCode = inventoryGetItemCodeByAllocationId($conn, $allocationId);
                $desc = 'Employee submitted inventory appeal for allocation #' . $allocationId . '.';
                inventoryLogActivity($conn, inventoryActionWithItemCode('Submit Appeal', $itemCode), 'Appeal', $allocationId, $desc, null, $itemCode);
                header('Location: inventory.php?view=list&status=appeal_sent');
                exit;
            }
        }

        header('Location: inventory.php?view=list&status=error&message=Unable+to+submit+appeal.+Please+try+again.');
        exit;
    }

    $checkAllocTable = $conn->query("SHOW TABLES LIKE 'inventory_item_allocations'");
    $checkItemsTable = $conn->query("SHOW TABLES LIKE 'inventory_items'");

    if (($checkAllocTable && $checkAllocTable->num_rows > 0) && ($checkItemsTable && $checkItemsTable->num_rows > 0)) {
        $stmt = $conn->prepare("
            SELECT
                ia.id,
                ii.item_id,
                ii.item_name,
                ii.description,
                ii.`type` AS type,
                ii.item_condition,
                ia.date_received,
                ia.employee_appeal,
                ia.employee_appeal_remarks,
                ia.employee_appeal_at
            FROM inventory_item_allocations ia
            JOIN inventory_items ii ON ii.id = ia.inventory_item_id
            WHERE ia.employee_id = ? AND ia.date_return IS NULL
            ORDER BY ia.date_received DESC, ia.id DESC
        ");

        if ($stmt) {
            $stmt->bind_param('i', $employeeDbId);
            $stmt->execute();
            $allocatedItems = inventory_stmt_fetch_all_assoc($stmt);
            $stmt->close();
        }

        $reqCheck = $conn->query("SHOW TABLES LIKE 'inventory_item_requests'");
        if ($reqCheck && $reqCheck->num_rows > 0) {
            $reqStmt = $conn->prepare("
                SELECT id, item_name, details, status, admin_remark, created_at, resolved_at
                FROM inventory_item_requests
                WHERE employee_id = ?
                ORDER BY created_at DESC, id DESC
            ");
            if ($reqStmt) {
                $reqStmt->bind_param('i', $employeeDbId);
                $reqStmt->execute();
                $myItemRequests = inventory_stmt_fetch_all_assoc($reqStmt);
                $reqStmt->close();
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
    <title>My Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
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
    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold">
                        <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate">
                    <?php echo htmlspecialchars($employeeName); ?>
                </span>
                <span class="text-[11px] text-white/80">
                    Employee
                </span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <?php require_once __DIR__ . '/../include/sidebar-scrollbar-once.php'; ?>
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-2">
            <a href="index.php" data-url="index.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" data-url="profile.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>My Profile</span>
            </a>
            <a href="timeoff.php" data-url="timeoff.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>My Leave Credits</span>
            </a>
            <a href="request.php" data-url="request.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>My Request</span>
            </a>
            <a href="compensation.php" data-url="compensation.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>My Compensation</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-my-inventory-nav.php'; ?>
            <a href="progressive-discipline.php" data-url="progressive-discipline.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <span>Progressive Discipline</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-incident-nav.php'; ?>
            <a href="settings.php" data-url="settings.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="shrink-0 border-t border-white/20 p-4">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Mobile sidebar backdrop -->
    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <main class="min-h-screen p-8 space-y-6 overflow-y-auto md:ml-64 md:pt-8 pt-16">
        <div id="main-inner">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">My Inventory</h1>
                    <p class="text-sm text-slate-500 mt-1"><?php echo $inventoryView === 'request' ? 'Request an item or track your submitted requests.' : 'Items currently allocated to you.'; ?></p>
                </div>
                <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                    <span><?php echo htmlspecialchars($department); ?></span>
                    <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                    <span><?php echo htmlspecialchars($position); ?></span>
                </div>
            </div>

            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <?php if ($status === 'appeal_sent'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                            Appeal sent successfully. Makikita ito ni admin sa inventory messages.
                        </div>
                    </div>
                <?php elseif ($status === 'request_sent'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                            Your item request was sent. An admin will review it under Inventory → Request.
                        </div>
                    </div>
                <?php elseif ($status === 'error'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                            <?php echo htmlspecialchars($message !== '' ? $message : 'Something went wrong.'); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($tableMissing): ?>
                    <div class="p-6">
                        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
                            Inventory tables are not available yet.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-4 md:p-6 space-y-4">
                        <details class="inventory-collapsible group rounded-xl border border-slate-200 bg-white shadow-sm open:shadow"<?php echo $inventoryView === 'list' ? ' open' : ''; ?>>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 font-semibold text-slate-800 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                                <span class="flex items-center gap-2">
                                    <svg class="h-5 w-5 text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2h-3V3H9v2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4m4 0v2m8-2v2" />
                                    </svg>
                                    My Items
                                </span>
                                <svg class="h-5 w-5 text-slate-400 transition-transform group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="border-t border-slate-100 px-4 pb-4 pt-2 overflow-x-auto">
                                <table id="inventoryTable" class="min-w-full text-sm display w-full">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item ID</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Item Name</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Condition</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date Received</th>
                                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide no-sort">Appeal / Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if (empty($allocatedItems)): ?>
                                            <tr>
                                                <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                                                    Wala pang allocated items sa account mo.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($allocatedItems as $item): ?>
                                                <tr class="hover:bg-slate-50/80">
                                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['item_id']); ?></td>
                                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['item_name']); ?></td>
                                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['description']); ?></td>
                                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['type']); ?></td>
                                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars((string)$item['item_condition']); ?></td>
                                                    <td class="px-4 py-3 text-slate-700">
                                                        <?php
                                                        $receivedDate = (string)($item['date_received'] ?? '');
                                                        echo $receivedDate !== '' ? htmlspecialchars(date('M d, Y', strtotime($receivedDate))) : '—';
                                                        ?>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700 min-w-[280px]">
                                                        <?php
                                                        $hasAppeal = trim((string)($item['employee_appeal'] ?? '')) !== '';
                                                        $appealBtn = $hasAppeal ? 'Update Appeal' : 'Submit Appeal';
                                                        ?>
                                                        <?php if ($hasAppeal): ?>
                                                            <div class="mb-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                                                                <div class="font-semibold">Sent to Admin</div>
                                                                <div class="mt-1"><?php echo htmlspecialchars((string)$item['employee_appeal']); ?></div>
                                                                <?php if (trim((string)($item['employee_appeal_remarks'] ?? '')) !== ''): ?>
                                                                    <div class="mt-1 text-amber-700">Remarks: <?php echo htmlspecialchars((string)$item['employee_appeal_remarks']); ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <button
                                                            type="button"
                                                            class="openAppealModal px-3 py-1.5 rounded-lg text-xs font-medium bg-[#FA9800] text-white hover:opacity-90"
                                                            data-allocation-id="<?php echo (int)$item['id']; ?>"
                                                            data-item-label="<?php echo htmlspecialchars((string)$item['item_id'] . ' - ' . (string)$item['item_name']); ?>"
                                                            data-existing-appeal="<?php echo htmlspecialchars((string)($item['employee_appeal'] ?? '')); ?>"
                                                            data-existing-remarks="<?php echo htmlspecialchars((string)($item['employee_appeal_remarks'] ?? '')); ?>"
                                                        >
                                                            <?php echo $appealBtn; ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>

                        <details class="inventory-collapsible group rounded-xl border border-slate-200 bg-white shadow-sm open:shadow"<?php echo $inventoryView === 'request' ? ' open' : ''; ?>>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 font-semibold text-slate-800 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                                <span class="flex items-center gap-2">
                                    <svg class="h-5 w-5 text-[#FA9800] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Request Item
                                </span>
                                <svg class="h-5 w-5 text-slate-400 transition-transform group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="border-t border-slate-100 px-4 pb-4 pt-4 space-y-6">
                                <form method="POST" class="rounded-lg border border-slate-100 bg-slate-50/80 p-4 space-y-3">
                                    <input type="hidden" name="action" value="submit_item_request">
                                    <div>
                                        <label for="requested_item_name" class="block text-sm font-medium text-slate-700 mb-1">Item name <span class="text-red-500">*</span></label>
                                        <input type="text" name="requested_item_name" id="requested_item_name" required maxlength="255" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Laptop, Mouse, Monitor">
                                    </div>
                                    <div>
                                        <label for="requested_item_details" class="block text-sm font-medium text-slate-700 mb-1">Details <span class="text-slate-400 font-normal">(optional)</span></label>
                                        <textarea name="requested_item_details" id="requested_item_details" rows="3" maxlength="2000" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Specifications, quantity, or reason for the request"></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#FA9800] text-white hover:opacity-90">
                                            Submit request
                                        </button>
                                    </div>
                                </form>

                                <div>
                                    <h3 class="text-sm font-semibold text-slate-700 mb-2">Your submitted requests</h3>
                                    <div class="overflow-x-auto rounded-lg border border-slate-100">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Item</th>
                                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Details</th>
                                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Date</th>
                                                    <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Admin response</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                <?php if (empty($myItemRequests)): ?>
                                                    <tr>
                                                        <td colspan="5" class="px-3 py-6 text-center text-slate-500 text-sm">
                                                            You have not submitted any requests yet.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($myItemRequests as $req): ?>
                                                        <?php
                                                        $rs = (string)($req['status'] ?? '');
                                                        $reqCreated = (string)($req['created_at'] ?? '');
                                                        ?>
                                                        <tr class="hover:bg-slate-50/80">
                                                            <td class="px-3 py-2 text-slate-700"><?php echo htmlspecialchars((string)$req['item_name']); ?></td>
                                                            <td class="px-3 py-2 text-slate-600"><?php echo nl2br(htmlspecialchars(trim((string)($req['details'] ?? '')) !== '' ? (string)$req['details'] : '—')); ?></td>
                                                            <td class="px-3 py-2">
                                                                <?php if ($rs === 'pending'): ?>
                                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                                                                <?php elseif ($rs === 'approved'): ?>
                                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Approved</span>
                                                                <?php else: ?>
                                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                                <?php echo $reqCreated !== '' ? htmlspecialchars(date('M d, Y', strtotime($reqCreated))) : '—'; ?>
                                                            </td>
                                                            <td class="px-3 py-2 text-slate-600 text-xs">
                                                                <?php echo trim((string)($req['admin_remark'] ?? '')) !== '' ? nl2br(htmlspecialchars((string)$req['admin_remark'])) : '—'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <div id="appealModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
            <div class="p-5 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800">Appeal Wrong Allocation</h3>
                <p id="appealItemLabel" class="text-sm text-slate-500 mt-1"></p>
            </div>
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="action" value="submit_appeal">
                <input type="hidden" name="allocation_id" id="appealAllocationId">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Appeal</label>
                    <textarea name="employee_appeal" id="appealText" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Hal. Hindi ito ang assigned item ko." required></textarea>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Remarks (Optional)</label>
                    <textarea name="employee_appeal_remarks" id="appealRemarks" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Additional details"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelAppealBtn" class="px-4 py-2 rounded-lg text-sm bg-slate-200 text-slate-700 hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-[#FA9800] text-white hover:opacity-90">Send to Admin</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="include/sidebar-employee.js"></script>
    <style>
      .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.25rem 0.5rem; margin-left: 0.5rem; }
      .dataTables_wrapper .dataTables_length select { border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.25rem 0.5rem; }
      .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { padding-top: 0.75rem; font-size: 0.875rem; color: #64748b; }
      .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0.25rem 0.75rem; margin: 0 1px; border-radius: 0.375rem; }
      .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #FA9800; color: #fff !important; border-color: #FA9800; }
    </style>
    <script>
      $(function () {
        if ($('#inventoryTable').length && $('#inventoryTable tbody tr').length > 0 && $('#inventoryTable tbody tr').first().find('td').length > 1) {
          $('#inventoryTable').DataTable({
            order: [[5, 'desc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            columnDefs: [{ orderable: false, targets: 6 }],
            language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries', info: 'Showing _START_ to _END_ of _TOTAL_ items', infoEmpty: 'No items', infoFiltered: '(filtered from _MAX_)', paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' } }
          });
        }
        const appealModal = document.getElementById('appealModal');
        const appealAllocationId = document.getElementById('appealAllocationId');
        const appealItemLabel = document.getElementById('appealItemLabel');
        const appealText = document.getElementById('appealText');
        const appealRemarks = document.getElementById('appealRemarks');
        const cancelAppealBtn = document.getElementById('cancelAppealBtn');

        function closeAppealModal() {
          appealModal.classList.add('hidden');
          appealAllocationId.value = '';
          appealItemLabel.textContent = '';
          appealText.value = '';
          appealRemarks.value = '';
        }

        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          const pathOnly = (url || '').split('#')[0].split('?')[0];
          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'index.php' || url === 'progressive-discipline.php' || pathOnly === 'inventory.php' || ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'].indexOf(pathOnly) !== -1) {
            window.location.href = url;
            return;
          }

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        $('.openAppealModal').on('click', function () {
          const allocationId = this.dataset.allocationId || '';
          const itemLabel = this.dataset.itemLabel || '';
          const existingAppeal = this.dataset.existingAppeal || '';
          const existingRemarks = this.dataset.existingRemarks || '';

          appealAllocationId.value = allocationId;
          appealItemLabel.textContent = itemLabel;
          appealText.value = existingAppeal;
          appealRemarks.value = existingRemarks;
          appealModal.classList.remove('hidden');
        });

        cancelAppealBtn.addEventListener('click', closeAppealModal);
        appealModal.addEventListener('click', function (event) {
          if (event.target === appealModal) {
            closeAppealModal();
          }
        });
      });
    </script>
</body>
</html>
