<?php
session_start();

$diagMode = isset($_REQUEST['diag']) && (string)$_REQUEST['diag'] === '1';
if ($diagMode) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    register_shutdown_function(function (): void {
        $lastError = error_get_last();
        if (!$lastError) {
            return;
        }
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int)$lastError['type'], $fatalTypes, true)) {
            return;
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Item Diagnostics</title></head><body style="font-family:sans-serif;padding:2rem;">'
            . '<h1>Fatal error detected</h1>'
            . '<p><strong>Message:</strong> ' . htmlspecialchars((string)($lastError['message'] ?? '')) . '</p>'
            . '<p><strong>File:</strong> ' . htmlspecialchars((string)($lastError['file'] ?? '')) . '</p>'
            . '<p><strong>Line:</strong> ' . htmlspecialchars((string)($lastError['line'] ?? '')) . '</p>'
            . '</body></html>';
    });
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

require_once __DIR__ . '/../database/bootstrap.php';
$dbBootstrap = __DIR__ . '/../database/db.php';
$dbState = hr_load_database_connection($dbBootstrap);
if (!$dbState['ok']) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Service unavailable</title></head><body style="font-family:sans-serif;padding:2rem;">'
        . '<h1>Database unavailable</h1>'
        . '<p>' . htmlspecialchars($dbState['message']) . '</p>'
        . '<p style="color:#64748b;font-size:13px;">Code: ' . htmlspecialchars($dbState['code']) . '</p>'
        . '</body></html>';
    exit;
}
$conn = $dbState['conn'];
require_once __DIR__ . '/database/item-repository.php';
require_once __DIR__ . '/database/setup_inventory_item_allocations_table.php';
require_once __DIR__ . '/include/inventory-activity-logger.php';

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

$itemOptions = getInventoryItemOptions();
$itemPrefixes = getInventoryItemPrefixes();
$itemConditions = ['Working-Active', 'Need Repair', 'Decom - Stock', 'Stock'];
ensureInventoryItemsTable($conn);
ensureInventoryItemAllocationsTable($conn);
$activeTab = strtolower((string)($_GET['tab'] ?? 'add'));
if (!in_array($activeTab, ['add', 'list', 'history'], true)) {
    $activeTab = 'add';
}

function getInventoryItemHistoryMap(mysqli $conn): array
{
    $map = [];
    $result = $conn->query("
        SELECT
            ia.inventory_item_id,
            ia.date_received,
            ia.date_return,
            ia.return_remarks,
            e.full_name,
            e.employee_id AS employee_code
        FROM inventory_item_allocations ia
        LEFT JOIN employees e ON e.id = ia.employee_id
        ORDER BY ia.inventory_item_id ASC, ia.date_received DESC, ia.id DESC
    ");

    if (!$result) {
        return $map;
    }

    while ($row = $result->fetch_assoc()) {
        $itemId = (int)($row['inventory_item_id'] ?? 0);
        if (!isset($map[$itemId])) {
            $map[$itemId] = [];
        }
        $map[$itemId][] = [
            'employee_name' => (string)($row['full_name'] ?? ''),
            'employee_code' => (string)($row['employee_code'] ?? ''),
            'date_received' => (string)($row['date_received'] ?? ''),
            'date_return' => (string)($row['date_return'] ?? ''),
            'return_remarks' => (string)($row['return_remarks'] ?? ''),
        ];
    }

    return $map;
}

function getInventoryHistoryRows(mysqli $conn): array
{
    $rows = [];
    $result = $conn->query("
        SELECT
            ia.id,
            ii.item_id,
            ii.item_name,
            e.full_name,
            e.employee_id AS employee_code,
            ia.date_received,
            ia.date_return,
            ia.return_remarks
        FROM inventory_item_allocations ia
        JOIN inventory_items ii ON ii.id = ia.inventory_item_id
        LEFT JOIN employees e ON e.id = ia.employee_id
        ORDER BY ia.id DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function ensureInventoryItemUploadDirectory(): string
{
    $uploadDir = __DIR__ . '/uploads/items';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            error_log('ensureInventoryItemUploadDirectory: cannot create ' . $uploadDir);
        }
    }
    return $uploadDir;
}

function uploadInventoryItemImage(?array $file, string &$errorMessage)
{
    if (!$file || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Image upload failed.';
        return false;
    }

    if ((int)$file['size'] > 5 * 1024 * 1024) {
        $errorMessage = 'Image must be 5MB or below.';
        return false;
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        $errorMessage = 'Invalid uploaded file.';
        return false;
    }

    $mimeType = '';
    if (class_exists('finfo')) {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = (string)$finfo->file($tmpPath);
        } catch (Throwable $e) {
            error_log('uploadInventoryItemImage finfo: ' . $e->getMessage());
            $mimeType = '';
        }
    } else {
        $imgInfo = @getimagesize($tmpPath);
        if (is_array($imgInfo) && !empty($imgInfo['mime'])) {
            $mimeType = (string)$imgInfo['mime'];
        }
    }
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimeTypes[$mimeType])) {
        $errorMessage = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
        return false;
    }

    $uploadDir = ensureInventoryItemUploadDirectory();
    $extension = $allowedMimeTypes[$mimeType];
    try {
        $randomSuffix = bin2hex(random_bytes(5));
    } catch (Exception $e) {
        $randomSuffix = uniqid('', true);
    }
    $newFileName = 'item_' . date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9]/', '', (string)$randomSuffix) . '.' . $extension;
    $destination = $uploadDir . '/' . $newFileName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        $errorMessage = 'Failed to save uploaded image.';
        return false;
    }

    return 'uploads/items/' . $newFileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    $action = $_POST['action'] ?? '';
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $type = trim((string)($_POST['type'] ?? ''));
    $itemCondition = trim((string)($_POST['item_condition'] ?? ''));
    $remarks = trim((string)($_POST['remarks'] ?? ''));
    $dateArrived = trim((string)($_POST['date_arrived'] ?? ''));
    $currentImagePath = trim((string)($_POST['current_image_path'] ?? ''));
    $rowId = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $rowId > 0) {
        $itemCode = '';
        $itemBeforeDelete = getInventoryItemById($conn, $rowId);
        if ($itemBeforeDelete) {
            $itemCode = (string)($itemBeforeDelete['item_id'] ?? '');
        }
        $ok = deleteInventoryItem($conn, $rowId);
        if ($ok) {
            $deleteDetails = '';
            if ($itemBeforeDelete) {
                $deleteDetails = "Item ID: " . ($itemBeforeDelete['item_id'] ?? '') . "\n"
                    . "Item name: " . ($itemBeforeDelete['item_name'] ?? '') . "\n"
                    . "Description: " . (trim((string)($itemBeforeDelete['description'] ?? '')) ?: '(empty)') . "\n"
                    . "Type: " . (trim((string)($itemBeforeDelete['type'] ?? '')) ?: '(empty)') . "\n"
                    . "Condition: " . (trim((string)($itemBeforeDelete['item_condition'] ?? '')) ?: '(empty)');
            }
            inventoryLogActivity($conn, inventoryActionWithItemCode('Delete Item', $itemCode), 'Item', $rowId, 'Deleted inventory item record #' . $rowId . '.', $deleteDetails ?: null, $itemCode);
            header('Location: item.php?status=deleted');
        } else {
            inventoryLogActivity($conn, inventoryActionWithItemCode('Delete Item Failed', $itemCode), 'Item', $rowId, 'Failed to delete inventory item record #' . $rowId . '.', null, $itemCode);
            header('Location: item.php?status=error&message=Unable+to+delete+item');
        }
        exit;
    }

    if (!isset($itemPrefixes[$itemName])) {
        header('Location: item.php?status=error&message=Invalid+item+name');
        exit;
    }
    if (!in_array($itemCondition, $itemConditions, true)) {
        header('Location: item.php?status=error&message=Invalid+item+condition');
        exit;
    }

    $uploadError = '';
    $uploadedImagePath = uploadInventoryItemImage($_FILES['item_image'] ?? null, $uploadError);
    if ($uploadedImagePath === false) {
        header('Location: item.php?status=error&message=' . urlencode($uploadError));
        exit;
    }
    $itemImagePath = $uploadedImagePath !== null ? $uploadedImagePath : $currentImagePath;

    if ($action === 'create') {
        $ok = createInventoryItem($conn, [
            'item_name' => $itemName,
            'description' => $description,
            'type' => $type,
            'item_condition' => $itemCondition,
            'remarks' => $remarks,
            'item_image_path' => $itemImagePath,
            'date_arrived' => $dateArrived,
        ]);

        if ($ok) {
            $newItemDbId = (int)$conn->insert_id;
            $newItemCode = inventoryGetItemCodeByItemDbId($conn, $newItemDbId);
            $createDetails = "Item name: " . $itemName . "\n"
                . "Description: " . ($description ?: '(empty)') . "\n"
                . "Type: " . ($type ?: '(empty)') . "\n"
                . "Item condition: " . $itemCondition . "\n"
                . "Remarks: " . ($remarks ?: '(empty)') . "\n"
                . "Date arrived: " . ($dateArrived ?: '(empty)') . "\n"
                . "Item picture: " . ($itemImagePath ? 'yes' : 'no');
            inventoryLogActivity($conn, inventoryActionWithItemCode('Create Item', $newItemCode), 'Item', $newItemDbId > 0 ? $newItemDbId : null, 'Created new inventory item: ' . $itemName . '.', $createDetails, $newItemCode);
            header('Location: item.php?status=created');
        } else {
            inventoryLogActivity($conn, inventoryActionWithItemCode('Create Item Failed', 'NO-ID'), 'Item', null, 'Failed to create inventory item: ' . $itemName . '.', null, null);
            header('Location: item.php?status=error&message=Unable+to+create+item');
        }
        exit;
    }

    if ($action === 'update' && $rowId > 0) {
        $itemCode = inventoryGetItemCodeByItemDbId($conn, $rowId);
        $itemBefore = getInventoryItemById($conn, $rowId);

        $ok = updateInventoryItem($conn, $rowId, [
            'item_name' => $itemName,
            'description' => $description,
            'type' => $type,
            'item_condition' => $itemCondition,
            'remarks' => $remarks,
            'item_image_path' => $itemImagePath,
            'date_arrived' => $dateArrived,
        ]);

        if ($ok) {
            $updatedItemCode = inventoryGetItemCodeByItemDbId($conn, $rowId);
            $logItemCode = $updatedItemCode !== '' ? $updatedItemCode : $itemCode;

            $changeDetails = [];
            if ($itemBefore) {
                $oldName = trim((string)($itemBefore['item_name'] ?? ''));
                if ($oldName !== $itemName) {
                    $changeDetails[] = 'Item name: ' . ($oldName ?: '(empty)') . ' → ' . $itemName;
                }
                $oldDesc = trim((string)($itemBefore['description'] ?? ''));
                if ($oldDesc !== $description) {
                    $changeDetails[] = 'Description: ' . ($oldDesc ?: '(empty)') . ' → ' . ($description ?: '(empty)');
                }
                $oldType = trim((string)($itemBefore['type'] ?? ''));
                if ($oldType !== $type) {
                    $changeDetails[] = 'Type: ' . ($oldType ?: '(empty)') . ' → ' . ($type ?: '(empty)');
                }
                $oldCond = trim((string)($itemBefore['item_condition'] ?? ''));
                if ($oldCond !== $itemCondition) {
                    $changeDetails[] = 'Item condition: ' . ($oldCond ?: '(empty)') . ' → ' . $itemCondition;
                }
                $oldRemarks = trim((string)($itemBefore['remarks'] ?? ''));
                if ($oldRemarks !== $remarks) {
                    $changeDetails[] = 'Remarks: ' . ($oldRemarks ?: '(empty)') . ' → ' . ($remarks ?: '(empty)');
                }
                $oldDate = trim((string)($itemBefore['date_arrived'] ?? ''));
                if ($oldDate !== $dateArrived) {
                    $changeDetails[] = 'Date arrived: ' . ($oldDate ?: '(empty)') . ' → ' . ($dateArrived ?: '(empty)');
                }
                $oldImg = trim((string)($itemBefore['item_image_path'] ?? ''));
                if ($oldImg !== $itemImagePath) {
                    $changeDetails[] = 'Item picture: ' . ($oldImg ? 'changed' : '(none)') . ' → ' . ($itemImagePath ? 'updated' : '(removed)');
                }
            }
            $changeDetailsStr = implode("\n", $changeDetails);
            $desc = !empty($changeDetails) ? 'Updated inventory item.' : 'Updated inventory item (no field changes).';

            inventoryLogActivity($conn, inventoryActionWithItemCode('Update Item', $logItemCode), 'Item', $rowId, $desc, $changeDetailsStr ?: null, $logItemCode);
            $returnTab = $_POST['return_tab'] ?? '';
            if ($returnTab === 'list') {
                header('Location: item.php?tab=list&status=updated');
            } else {
                header('Location: item.php?status=updated');
            }
        } else {
            inventoryLogActivity($conn, inventoryActionWithItemCode('Update Item Failed', $itemCode), 'Item', $rowId, 'Failed to update inventory item record #' . $rowId . '.', null, $itemCode);
            header('Location: item.php?status=error&message=Unable+to+update+item');
        }
        exit;
    }
    } catch (Throwable $e) {
        error_log('inventory/item.php POST fatal: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        $_SESSION['inventory_item_post_error'] = $e->getMessage() . ' — ' . basename($e->getFile()) . ':' . $e->getLine();
        if ($diagMode) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Item Diagnostics</title></head><body style="font-family:sans-serif;padding:2rem;">'
                . '<h1>POST exception detected</h1>'
                . '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
                . '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>'
                . '<p><strong>Line:</strong> ' . htmlspecialchars((string)$e->getLine()) . '</p>'
                . '</body></html>';
            exit;
        }
        header('Location: item.php?tab=add&status=error&message=Server+error+while+saving+item');
        exit;
    }
}

$items = listInventoryItems($conn);
$itemHistoryMap = getInventoryItemHistoryMap($conn);
$itemHistoryRows = getInventoryHistoryRows($conn);

$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
$editItemId = (int)($_GET['edit_id'] ?? 0);
$editItem = null;

if ($editItemId > 0) {
    $editItem = getInventoryItemById($conn, $editItemId);
    if ($editItem) {
        $activeTab = 'add';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <style>
        .action-cell {
            min-width: 250px;
        }
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .action-btn {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            text-decoration: none;
            cursor: pointer;
            transition: opacity .2s ease, transform .2s ease;
        }
        .action-btn:hover {
            opacity: .92;
            transform: translateY(-1px);
        }
        .action-btn-blue { background: #2563eb; }
        .action-btn-orange { background: #d97706; }
        .action-btn-red { background: #dc2626; }
        .action-btn-emerald { background: #059669; }
        .action-btn-violet { background: #7c3aed; }
        .print-label-cell {
            min-width: 160px;
        }
        .print-label-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .print-label-link {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            line-height: 1.2;
        }
        .print-label-qr {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .print-label-barcode {
            background: #dcfce7;
            color: #15803d;
        }
        .picture-link {
            background: #ede9fe;
            color: #6d28d9;
        }
        .hidden {
            display: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            color: #334155 !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            margin-left: 6px !important;
            font-size: 13px !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            border-color: #94a3b8 !important;
            background: #f8fafc !important;
            color: #0f172a !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            border-color: #FA9800 !important;
            background: #FA9800 !important;
            color: #ffffff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            opacity: 0.55;
            cursor: not-allowed !important;
            border-color: #cbd5e1 !important;
            background: #f1f5f9 !important;
            color: #64748b !important;
        }
    </style>
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
        <h1 class="text-2xl font-semibold text-slate-800 mb-6">Item Management</h1>
        <section class="mb-6">
            <div class="inline-flex rounded-lg bg-white border border-slate-200 p-1 shadow-sm">
                <a href="item.php?tab=add" class="px-4 py-2 text-sm rounded-md font-medium transition <?php echo $activeTab === 'add' ? 'bg-[#FA9800] text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Add Item</a>
                <a href="item.php?tab=list" class="px-4 py-2 text-sm rounded-md font-medium transition <?php echo $activeTab === 'list' ? 'bg-[#FA9800] text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">List Item</a>
                <a href="item.php?tab=history" class="px-4 py-2 text-sm rounded-md font-medium transition <?php echo $activeTab === 'history' ? 'bg-[#FA9800] text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Item History</a>
            </div>
        </section>

        <?php if ($status === 'created'): ?>
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">Item created successfully.</div>
        <?php elseif ($status === 'updated'): ?>
            <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">The item has been updated successfully.</div>
        <?php elseif ($status === 'deleted'): ?>
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">Item deleted successfully.</div>
        <?php elseif ($status === 'error'): ?>
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <?php echo htmlspecialchars($message !== '' ? $message : 'Something went wrong.'); ?>
                <?php
                if (!empty($_SESSION['inventory_item_post_error'])) {
                    echo '<div class="mt-3 text-xs text-red-900 font-mono whitespace-pre-wrap border-t border-red-200 pt-2">';
                    echo htmlspecialchars((string)$_SESSION['inventory_item_post_error']);
                    echo '</div>';
                    unset($_SESSION['inventory_item_post_error']);
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($activeTab === 'add'): ?>
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Add / Edit Item</h2>
            <form id="itemForm" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" id="formAction" value="<?php echo $editItem ? 'update' : 'create'; ?>">
                <input type="hidden" name="id" id="rowId" value="<?php echo $editItem ? (int)$editItem['id'] : ''; ?>">
                <input type="hidden" name="current_image_path" id="currentImagePath" value="<?php echo htmlspecialchars((string)($editItem['item_image_path'] ?? '')); ?>">
                <?php if ($diagMode): ?><input type="hidden" name="diag" value="1"><?php endif; ?>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item Name</label>
                    <select name="item_name" id="itemName" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select item</option>
                        <?php foreach ($itemOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (($editItem['item_name'] ?? '') === $option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item ID (Auto)</label>
                    <input type="text" id="itemIdPreview" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50" readonly placeholder="Auto-generated after save" value="<?php echo htmlspecialchars((string)($editItem['item_id'] ?? '')); ?>">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Description</label>
                    <input type="text" name="description" id="description" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="<?php echo htmlspecialchars((string)($editItem['description'] ?? '')); ?>">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Type</label>
                    <input type="text" name="type" id="type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="<?php echo htmlspecialchars((string)($editItem['type'] ?? '')); ?>">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item Condition</label>
                    <select name="item_condition" id="itemCondition" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select condition</option>
                        <?php foreach ($itemConditions as $condition): ?>
                            <option value="<?php echo htmlspecialchars($condition); ?>" <?php echo (((string)($editItem['item_condition'] ?? '')) === $condition) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($condition); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Remarks</label>
                    <input type="text" name="remarks" id="remarks" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="<?php echo htmlspecialchars((string)($editItem['remarks'] ?? '')); ?>">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Date Arrived / Purchased</label>
                    <input type="date" name="date_arrived" id="dateArrived" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="<?php echo htmlspecialchars((string)($editItem['date_arrived'] ?? '')); ?>">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item Picture</label>
                    <input type="file" name="item_image" id="itemImage" accept=".jpg,.jpeg,.png,.gif,.webp" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <p id="currentImageNote" class="mt-1 text-xs text-slate-500">
                        <?php if ((string)($editItem['item_image_path'] ?? '') !== ''): ?>
                            Current image:
                            <a class="text-blue-600 hover:underline" target="_blank" href="<?php echo htmlspecialchars((string)$editItem['item_image_path']); ?>">View</a>
                        <?php else: ?>
                            No current image.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
                        <?php echo $editItem ? 'Update Item' : 'Save Item'; ?>
                    </button>
                    <button type="button" id="resetBtn" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-300 transition">Reset</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($activeTab === 'list'): ?>
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-slate-800">Items List</h2>
                <div class="w-full md:w-72">
                    <label for="itemNameFilter" class="block text-sm text-slate-600 mb-1">Filter by Item Name</label>
                    <select id="itemNameFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All items</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table id="itemsTable" class="display stripe hover w-full text-sm">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Item Condition</th>
                            <th>Remarks</th>
                            <th>Date Arrived</th>
                            <th>Picture</th>
                            <th>Print Label</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars((string)$item['description']); ?></td>
                                <td><?php echo htmlspecialchars((string)$item['type']); ?></td>
                                <td><?php echo htmlspecialchars((string)($item['item_condition'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)$item['remarks']); ?></td>
                                <td><?php echo htmlspecialchars((string)$item['date_arrived']); ?></td>
                                <td>
                                    <?php if ((string)($item['item_image_path'] ?? '') !== ''): ?>
                                        <a class="print-label-link picture-link" target="_blank" href="<?php echo htmlspecialchars((string)$item['item_image_path']); ?>">View</a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td class="print-label-cell">
                                    <div class="print-label-buttons">
                                        <a class="print-label-link print-label-qr" target="_blank" href="print-sticker.php?id=<?php echo (int)$item['id']; ?>&mode=qr">QR</a>
                                        <a class="print-label-link print-label-barcode" target="_blank" href="print-sticker.php?id=<?php echo (int)$item['id']; ?>&mode=barcode">BARCODE</a>
                                    </div>
                                </td>
                                <td class="action-cell">
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="viewBtn action-btn action-btn-emerald"
                                            data-id="<?php echo (int)$item['id']; ?>"
                                            data-item_id="<?php echo htmlspecialchars($item['item_id']); ?>"
                                            data-item_name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                            data-description="<?php echo htmlspecialchars((string)$item['description']); ?>"
                                            data-type="<?php echo htmlspecialchars((string)$item['type']); ?>"
                                            data-item_condition="<?php echo htmlspecialchars((string)($item['item_condition'] ?? '')); ?>"
                                            data-remarks="<?php echo htmlspecialchars((string)$item['remarks']); ?>"
                                            data-date_arrived="<?php echo htmlspecialchars((string)$item['date_arrived']); ?>"
                                            data-item_image_path="<?php echo htmlspecialchars((string)($item['item_image_path'] ?? '')); ?>"
                                            title="View"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            class="historyBtn action-btn action-btn-violet"
                                            data-id="<?php echo (int)$item['id']; ?>"
                                            data-item_id="<?php echo htmlspecialchars($item['item_id']); ?>"
                                            data-item_name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                            title="History"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M3 12a9 9 0 109-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M3 3v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            class="editBtn action-btn action-btn-blue"
                                            data-id="<?php echo (int)$item['id']; ?>"
                                            data-item_id="<?php echo htmlspecialchars($item['item_id']); ?>"
                                            data-item_name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                            data-description="<?php echo htmlspecialchars((string)$item['description']); ?>"
                                            data-type="<?php echo htmlspecialchars((string)$item['type']); ?>"
                                            data-item_condition="<?php echo htmlspecialchars((string)($item['item_condition'] ?? '')); ?>"
                                            data-remarks="<?php echo htmlspecialchars((string)$item['remarks']); ?>"
                                            data-date_arrived="<?php echo htmlspecialchars((string)$item['date_arrived']); ?>"
                                            data-item_image_path="<?php echo htmlspecialchars((string)($item['item_image_path'] ?? '')); ?>"
                                            title="Edit"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4 12.5-12.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>

                                        <form method="POST" onsubmit="return confirm('Delete this item?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                            <?php if ($diagMode): ?><input type="hidden" name="diag" value="1"><?php endif; ?>
                                            <button type="submit" class="action-btn action-btn-red" title="Delete">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($activeTab === 'history'): ?>
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-4">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Item History Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label for="historyItemNameFilter" class="block text-sm text-slate-600 mb-1">Select Item Name</label>
                    <select id="historyItemNameFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Choose item name</option>
                    </select>
                </div>
                <div>
                    <label for="historyItemIdFilter" class="block text-sm text-slate-600 mb-1">Select Item ID</label>
                    <select id="historyItemIdFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" disabled>
                        <option value="">Choose item ID</option>
                    </select>
                </div>
            </div>
            <p class="text-sm text-slate-500 mb-4">Pili muna ng Item Name at Item ID bago lumabas ang history.</p>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Item History</h2>
            <div class="overflow-x-auto">
                <table id="itemHistoryTable" class="display stripe hover w-full text-sm">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Employee</th>
                            <th>Date Received</th>
                            <th>Date Return</th>
                            <th>Return Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemHistoryRows as $history): ?>
                            <?php
                            $employeeLabel = trim((string)($history['full_name'] ?? '')) !== ''
                                ? (string)$history['full_name'] . ' (' . (string)($history['employee_code'] ?? '') . ')'
                                : 'Unknown Employee';
                            $dateReturn = (string)($history['date_return'] ?? '');
                            $isReturned = $dateReturn !== '';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)($history['item_id'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($history['item_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($employeeLabel); ?></td>
                                <td><?php echo htmlspecialchars((string)($history['date_received'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($dateReturn !== '' ? $dateReturn : '-'); ?></td>
                                <td><?php echo htmlspecialchars((string)($history['return_remarks'] ?? '') !== '' ? (string)$history['return_remarks'] : '-'); ?></td>
                                <td>
                                    <?php if ($isReturned): ?>
                                        <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Returned</span>
                                    <?php else: ?>
                                        <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Currently Allocated</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <div id="itemViewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Item Details</h3>
                <button type="button" class="closeModalBtn text-slate-500 hover:text-slate-700" data-target="itemViewModal">X</button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><span class="text-slate-500">Item ID:</span> <span id="viewItemId" class="text-slate-800 font-medium"></span></div>
                <div><span class="text-slate-500">Item Name:</span> <span id="viewItemName" class="text-slate-800 font-medium"></span></div>
                <div><span class="text-slate-500">Description:</span> <span id="viewDescription" class="text-slate-800"></span></div>
                <div><span class="text-slate-500">Type:</span> <span id="viewType" class="text-slate-800"></span></div>
                <div><span class="text-slate-500">Condition:</span> <span id="viewCondition" class="text-slate-800"></span></div>
                <div><span class="text-slate-500">Remarks:</span> <span id="viewRemarks" class="text-slate-800"></span></div>
                <div><span class="text-slate-500">Date Arrived:</span> <span id="viewDateArrived" class="text-slate-800"></span></div>
                <div><span class="text-slate-500">Picture:</span> <span id="viewPictureContainer" class="text-slate-800"></span></div>
            </div>
        </div>
    </div>

    <div id="itemHistoryModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 id="historyModalTitle" class="text-lg font-semibold text-slate-800">Item History</h3>
                <button type="button" class="closeModalBtn text-slate-500 hover:text-slate-700" data-target="itemHistoryModal">X</button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-3 py-2 border-b border-slate-200">Employee</th>
                                <th class="text-left px-3 py-2 border-b border-slate-200">Date Received</th>
                                <th class="text-left px-3 py-2 border-b border-slate-200">Date Return</th>
                                <th class="text-left px-3 py-2 border-b border-slate-200">Return Remarks</th>
                                <th class="text-left px-3 py-2 border-b border-slate-200">Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
                <p id="historyEmptyState" class="hidden mt-3 text-sm text-slate-500">No allocation history yet for this item.</p>
            </div>
        </div>
    </div>

    <div id="itemEditModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl my-8">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Edit Item</h3>
                <button type="button" class="closeModalBtn text-slate-500 hover:text-slate-700 text-xl leading-none" data-target="itemEditModal">×</button>
            </div>
            <form id="editItemForm" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="return_tab" value="list">
                <input type="hidden" name="id" id="editRowId" value="">
                <input type="hidden" name="current_image_path" id="editCurrentImagePath" value="">
                <?php if ($diagMode): ?><input type="hidden" name="diag" value="1"><?php endif; ?>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item Name</label>
                    <select name="item_name" id="editItemName" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select item</option>
                        <?php foreach ($itemOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item ID (Auto)</label>
                    <input type="text" id="editItemIdPreview" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50" readonly value="">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Description</label>
                    <input type="text" name="description" id="editDescription" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Type</label>
                    <input type="text" name="type" id="editType" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item Condition</label>
                    <select name="item_condition" id="editItemCondition" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select condition</option>
                        <?php foreach ($itemConditions as $condition): ?>
                            <option value="<?php echo htmlspecialchars($condition); ?>"><?php echo htmlspecialchars($condition); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Remarks</label>
                    <input type="text" name="remarks" id="editRemarks" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Date Arrived / Purchased</label>
                    <input type="date" name="date_arrived" id="editDateArrived" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" value="">
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item Picture</label>
                    <input type="file" name="item_image" id="editItemImage" accept=".jpg,.jpeg,.png,.gif,.webp" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <p id="editCurrentImageNote" class="mt-1 text-xs text-slate-500">No current image.</p>
                </div>

                <div class="md:col-span-2 flex gap-2 pt-2">
                    <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:opacity-90 transition">Update Item</button>
                    <button type="button" class="editModalCloseBtn px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-300 transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        const prefixes = <?php echo json_encode($itemPrefixes); ?>;
        const itemHistoryMap = <?php echo json_encode($itemHistoryMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        function setItemIdPreview() {
            const itemName = document.getElementById('itemName').value;
            const preview = document.getElementById('itemIdPreview');
            if (!itemName || !prefixes[itemName]) {
                preview.value = '';
                return;
            }
            preview.value = prefixes[itemName] + 'AUTO';
        }

        function resetForm() {
            document.getElementById('itemForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('rowId').value = '';
            document.getElementById('itemIdPreview').value = '';
            document.getElementById('currentImagePath').value = '';
            document.getElementById('currentImageNote').textContent = 'No current image.';
        }

        const itemNameEl = document.getElementById('itemName');
        const resetBtnEl = document.getElementById('resetBtn');
        if (itemNameEl) {
            itemNameEl.addEventListener('change', setItemIdPreview);
        }
        if (resetBtnEl) {
            resetBtnEl.addEventListener('click', resetForm);
        }

        $(function () {
            if ($('#itemsTable').length) {
                const table = $('#itemsTable').DataTable({
                    pageLength: 10
                });

                const itemNameColumnIndex = 1;
                const itemNameFilter = document.getElementById('itemNameFilter');
                const uniqueItemNames = [];

                table.column(itemNameColumnIndex).data().each(function (value) {
                    const itemName = String(value).trim();
                    if (itemName !== '' && !uniqueItemNames.includes(itemName)) {
                        uniqueItemNames.push(itemName);
                    }
                });

                uniqueItemNames.sort(function (a, b) {
                    return a.localeCompare(b);
                });

                uniqueItemNames.forEach(function (itemName) {
                    const option = document.createElement('option');
                    option.value = itemName;
                    option.textContent = itemName;
                    itemNameFilter.appendChild(option);
                });

                itemNameFilter.addEventListener('change', function () {
                    const selected = this.value;
                    if (!selected) {
                        table.column(itemNameColumnIndex).search('').draw();
                        return;
                    }
                    table.column(itemNameColumnIndex).search('^' + $.fn.dataTable.util.escapeRegex(selected) + '$', true, false).draw();
                });

                $('.editBtn').on('click', function () {
                    const btn = this;
                    $('#editRowId').val(btn.dataset.id || '');
                    $('#editCurrentImagePath').val(btn.dataset.item_image_path || '');
                    $('#editItemName').val(btn.dataset.item_name || '');
                    $('#editItemIdPreview').val(btn.dataset.item_id || '');
                    $('#editDescription').val(btn.dataset.description || '');
                    $('#editType').val(btn.dataset.type || '');
                    $('#editItemCondition').val(btn.dataset.item_condition || '');
                    $('#editRemarks').val(btn.dataset.remarks || '');
                    $('#editDateArrived').val(btn.dataset.date_arrived || '');
                    $('#editItemImage').val('');
                    const imgPath = (btn.dataset.item_image_path || '').trim();
                    const noteEl = $('#editCurrentImageNote');
                    noteEl.empty();
                    if (imgPath) {
                        noteEl.append(document.createTextNode('Current image: '));
                        noteEl.append($('<a>', { class: 'text-blue-600 hover:underline', target: '_blank', href: imgPath, text: 'View' }));
                    } else {
                        noteEl.text('No current image.');
                    }
                    $('#itemEditModal').removeClass('hidden');
                });

                $('.viewBtn').on('click', function () {
                    const btn = this;
                    $('#viewItemId').text(btn.dataset.item_id || '');
                    $('#viewItemName').text(btn.dataset.item_name || '');
                    $('#viewDescription').text(btn.dataset.description || '');
                    $('#viewType').text(btn.dataset.type || '');
                    $('#viewCondition').text(btn.dataset.item_condition || '');
                    $('#viewRemarks').text(btn.dataset.remarks || '');
                    $('#viewDateArrived').text(btn.dataset.date_arrived || '');

                    const pictureContainer = $('#viewPictureContainer');
                    pictureContainer.empty();
                    if (btn.dataset.item_image_path) {
                        const link = $('<a>', {
                            class: 'text-blue-600 hover:underline',
                            target: '_blank',
                            href: btn.dataset.item_image_path,
                            text: 'View Image'
                        });
                        pictureContainer.append(link);
                    } else {
                        pictureContainer.text('No image');
                    }

                    $('#itemViewModal').removeClass('hidden');
                });

                $('.historyBtn').on('click', function () {
                    const btn = this;
                    const itemDbId = String(btn.dataset.id || '');
                    const itemLabel = (btn.dataset.item_name || '') + ' (' + (btn.dataset.item_id || '') + ')';
                    $('#historyModalTitle').text('Item History - ' + itemLabel);

                    const historyRows = itemHistoryMap[itemDbId] || [];
                    const tbody = $('#historyTableBody');
                    tbody.empty();

                    if (!historyRows.length) {
                        $('#historyEmptyState').removeClass('hidden');
                    } else {
                        $('#historyEmptyState').addClass('hidden');
                        historyRows.forEach(function (entry) {
                            const employeeLabel = entry.employee_name
                                ? (entry.employee_name + (entry.employee_code ? ' (' + entry.employee_code + ')' : ''))
                                : 'Unknown Employee';
                            const dateReturn = entry.date_return || '';
                            const remarks = entry.return_remarks || '';
                            const isReturned = Boolean(dateReturn);
                            const statusBadge = isReturned
                                ? '<span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Returned</span>'
                                : '<span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Currently Allocated</span>';

                            tbody.append(
                                '<tr class="border-b border-slate-100">' +
                                    '<td class="px-3 py-2">' + $('<div>').text(employeeLabel).html() + '</td>' +
                                    '<td class="px-3 py-2">' + $('<div>').text(entry.date_received || '').html() + '</td>' +
                                    '<td class="px-3 py-2">' + $('<div>').text(dateReturn || '-').html() + '</td>' +
                                    '<td class="px-3 py-2">' + $('<div>').text(remarks || '-').html() + '</td>' +
                                    '<td class="px-3 py-2">' + statusBadge + '</td>' +
                                '</tr>'
                            );
                        });
                    }

                    $('#itemHistoryModal').removeClass('hidden');
                });
            }

            if ($('#itemHistoryTable').length) {
                const historyTable = $('#itemHistoryTable').DataTable({
                    pageLength: 10,
                    order: [[3, 'desc']],
                    language: {
                        emptyTable: 'Select Item Name and Item ID to view history.'
                    }
                });

                const historyItemIdColumnIndex = 0;
                const historyItemNameColumnIndex = 1;
                const historyItemNameFilter = document.getElementById('historyItemNameFilter');
                const historyItemIdFilter = document.getElementById('historyItemIdFilter');
                const uniqueHistoryItemNames = [];
                const itemNameToItemIds = {};

                historyTable.rows().data().each(function (row) {
                    const itemId = String(row[historyItemIdColumnIndex] || '').trim();
                    const itemName = String(row[historyItemNameColumnIndex] || '').trim();
                    if (itemName !== '' && !uniqueHistoryItemNames.includes(itemName)) {
                        uniqueHistoryItemNames.push(itemName);
                    }
                    if (itemName !== '' && itemId !== '') {
                        if (!itemNameToItemIds[itemName]) {
                            itemNameToItemIds[itemName] = [];
                        }
                        if (!itemNameToItemIds[itemName].includes(itemId)) {
                            itemNameToItemIds[itemName].push(itemId);
                        }
                    }
                });

                uniqueHistoryItemNames.sort(function (a, b) {
                    return a.localeCompare(b);
                });

                uniqueHistoryItemNames.forEach(function (itemName) {
                    const option = document.createElement('option');
                    option.value = itemName;
                    option.textContent = itemName;
                    historyItemNameFilter.appendChild(option);
                });

                function applyHistoryFilters() {
                    const selectedName = historyItemNameFilter.value;
                    const selectedId = historyItemIdFilter.value;

                    if (!selectedName || !selectedId) {
                        historyTable.column(historyItemNameColumnIndex).search('a^', true, false);
                        historyTable.column(historyItemIdColumnIndex).search('a^', true, false);
                        historyTable.draw();
                        return;
                    }

                    historyTable.column(historyItemNameColumnIndex).search('^' + $.fn.dataTable.util.escapeRegex(selectedName) + '$', true, false);
                    historyTable.column(historyItemIdColumnIndex).search('^' + $.fn.dataTable.util.escapeRegex(selectedId) + '$', true, false);
                    historyTable.draw();
                }

                historyItemNameFilter.addEventListener('change', function () {
                    const selectedName = this.value;
                    historyItemIdFilter.innerHTML = '<option value="">Choose item ID</option>';
                    historyItemIdFilter.value = '';

                    if (!selectedName || !itemNameToItemIds[selectedName]) {
                        historyItemIdFilter.disabled = true;
                        applyHistoryFilters();
                        return;
                    }

                    const ids = itemNameToItemIds[selectedName].slice().sort(function (a, b) {
                        return a.localeCompare(b);
                    });
                    ids.forEach(function (itemId) {
                        const option = document.createElement('option');
                        option.value = itemId;
                        option.textContent = itemId;
                        historyItemIdFilter.appendChild(option);
                    });
                    historyItemIdFilter.disabled = false;
                    applyHistoryFilters();
                });

                historyItemIdFilter.addEventListener('change', function () {
                    applyHistoryFilters();
                });

                applyHistoryFilters();
            }

            $('.closeModalBtn').on('click', function () {
                const target = this.dataset.target;
                if (target) {
                    $('#' + target).addClass('hidden');
                }
            });

            $('.editModalCloseBtn').on('click', function () {
                $('#itemEditModal').addClass('hidden');
            });

            $('#itemViewModal, #itemHistoryModal, #itemEditModal').on('click', function (event) {
                if (event.target === this) {
                    $(this).addClass('hidden');
                }
            });

        });
    </script>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
