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
require_once __DIR__ . '/../inventory/database/setup_inventory_decommission_requests_table.php';
require_once __DIR__ . '/../inventory/database/mysqli-stmt-fetch.php';
require_once __DIR__ . '/../inventory/include/inventory-activity-logger.php';
require_once __DIR__ . '/../include/inventory_decommission_helpers.php';
require_once __DIR__ . '/../admin/include/activity-logger.php';

$allocatedItems = [];
$myItemRequests = [];
$myDecommissionRequests = [];
$allDecommissionRequests = [];
$canReviewDecommission = false;
$tableMissing = false;
$status = (string)($_GET['status'] ?? '');
$message = (string)($_GET['message'] ?? '');
$rawInvView = (string)($_GET['view'] ?? 'list');
$inventoryView = in_array($rawInvView, ['list', 'request', 'decommission', 'decommission_review'], true) ? $rawInvView : 'list';

if ($conn && $employeeDbId) {
    ensureInventoryItemsTable($conn);
    ensureInventoryItemAllocationsTable($conn);
    ensureInventoryItemRequestsTable($conn);
    ensureInventoryDecommissionRequestsTable($conn);
    $canReviewDecommission = hr_employee_can_review_decommission_requests($conn, $employeeDbId);

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'submit_decommission') {
        $allocId = (int)($_POST['inventory_item_allocation_id'] ?? 0);
        $dateDecommissioning = trim((string)($_POST['date_decommissioning'] ?? ''));
        $reason = trim((string)($_POST['reason_decommissioning'] ?? ''));
        $t1n = trim((string)($_POST['test_1_notes'] ?? ''));
        $t1d = trim((string)($_POST['test_1_date'] ?? ''));
        $t2n = trim((string)($_POST['test_2_notes'] ?? ''));
        $t2d = trim((string)($_POST['test_2_date'] ?? ''));
        $t3n = trim((string)($_POST['test_3_notes'] ?? ''));
        $t3d = trim((string)($_POST['test_3_date'] ?? ''));

        if ($allocId <= 0 || $reason === '') {
            header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode('Select an allocated item and enter the reason for decommissioning.'));
            exit;
        }

        $requestEmployeeName = trim((string)$employeeName);
        if ($requestEmployeeName === '') {
            $requestEmployeeName = 'Employee';
        }

        $allocRow = null;
        $v = $conn->prepare('
            SELECT ia.id, ii.item_id, ii.item_name, ii.description, ii.`type` AS eq_type, ii.brand_manufacturer, ii.remarks AS item_remarks, ia.date_received
            FROM inventory_item_allocations ia
            INNER JOIN inventory_items ii ON ii.id = ia.inventory_item_id
            WHERE ia.id = ? AND ia.employee_id = ? AND ia.date_return IS NULL
            LIMIT 1
        ');
        if ($v) {
            $v->bind_param('ii', $allocId, $employeeDbId);
            $v->execute();
            $allocRow = $v->get_result()->fetch_assoc();
            $v->close();
        }
        if (!$allocRow) {
            header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode('The selected item is invalid or no longer available.'));
            exit;
        }

        $equipmentName = trim((string)($allocRow['item_name'] ?? ''));
        $itemCode = trim((string)($allocRow['item_id'] ?? ''));
        $equipmentType = trim((string)($allocRow['eq_type'] ?? ''));
        $equipmentDescription = trim((string)($allocRow['description'] ?? ''));
        $brandManufacturer = trim((string)($allocRow['brand_manufacturer'] ?? ''));
        $itemRemarksSnap = trim((string)($allocRow['item_remarks'] ?? ''));
        $recvRaw = (string)($allocRow['date_received'] ?? '');
        $itemDateReceived = $recvRaw !== '' ? date('Y-m-d', strtotime($recvRaw)) : '';

        if ($equipmentName === '' || $itemCode === '') {
            header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode('Could not load item details from the allocation.'));
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $testUploads = [
            1 => inventory_decommission_save_multi_uploads($userId, 'test_1_attachments'),
            2 => inventory_decommission_save_multi_uploads($userId, 'test_2_attachments'),
            3 => inventory_decommission_save_multi_uploads($userId, 'test_3_attachments'),
        ];
        $uploadErrMsg = static function (array $r): string {
            if ($r['ok'] ?? false) {
                return '';
            }
            $e = (string)($r['error'] ?? '');
            switch ($e) {
                case 'too_many':
                    return 'Too many images in one test (maximum 20 per test).';
                case 'upload':
                    return 'Image upload failed. Please try again.';
                case 'size':
                    return 'Each image must be 8MB or smaller.';
                case 'type':
                    return 'Test attachments must be images only (JPG, PNG, GIF, or WebP).';
                case 'save':
                    return 'Could not save uploaded images.';
                default:
                    return 'Attachment upload failed.';
            }
        };
        foreach ($testUploads as $tn => $r) {
            if (!($r['ok'] ?? false)) {
                header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode($uploadErrMsg($r)));
                exit;
            }
        }
        foreach ([1, 2, 3] as $tn) {
            if (count($testUploads[$tn]['paths'] ?? []) < 1) {
                header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode("Test {$tn}: upload at least one image (you can select multiple files at once)."));
                exit;
            }
        }

        $t1PathsJson = json_encode($testUploads[1]['paths'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $t2PathsJson = json_encode($testUploads[2]['paths'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $t3PathsJson = json_encode($testUploads[3]['paths'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $normDate = static function (string $d): ?string {
            $d = trim($d);
            if ($d === '') {
                return null;
            }
            $ts = strtotime($d);

            return $ts ? date('Y-m-d', $ts) : null;
        };

        $companyDb = null;
        $typeDb = $equipmentType === '' ? null : $equipmentType;
        $serialDb = $itemRemarksSnap === '' ? null : $itemRemarksSnap;
        $descDb = $equipmentDescription === '' ? null : $equipmentDescription;
        $brandDb = $brandManufacturer === '' ? null : $brandManufacturer;
        $recvDb = $normDate($itemDateReceived);
        $decomDb = $normDate($dateDecommissioning);
        $t1nDb = $t1n === '' ? null : $t1n;
        $t1dDb = $normDate($t1d);
        $t2nDb = $t2n === '' ? null : $t2n;
        $t2dDb = $normDate($t2d);
        $t3nDb = $t3n === '' ? null : $t3n;
        $t3dDb = $normDate($t3d);
        $allocDb = $allocId > 0 ? (string)$allocId : null;

        $ins = $conn->prepare('
            INSERT INTO inventory_decommission_requests (
                employee_id, inventory_item_allocation_id, company_name, request_employee_name,
                equipment_name, item_code, equipment_type, serial_number, equipment_description,
                brand_manufacturer, item_date_received, date_decommissioning, reason_decommissioning,
                test_1_notes, test_1_date, test_2_notes, test_2_date, test_3_notes, test_3_date,
                test_1_attachment_paths, test_2_attachment_paths, test_3_attachment_paths,
                attachment_path, status
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'pending\')
        ');
        if (!$ins) {
            header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode('Database error.'));
            exit;
        }

        $eidStr = (string)(int)$employeeDbId;
        $attachDb = null;
        $ins->bind_param(
            'sssssssssssssssssssssss',
            $eidStr,
            $allocDb,
            $companyDb,
            $requestEmployeeName,
            $equipmentName,
            $itemCode,
            $typeDb,
            $serialDb,
            $descDb,
            $brandDb,
            $recvDb,
            $decomDb,
            $reason,
            $t1nDb,
            $t1dDb,
            $t2nDb,
            $t2dDb,
            $t3nDb,
            $t3dDb,
            $t1PathsJson,
            $t2PathsJson,
            $t3PathsJson,
            $attachDb
        );
        $okIns = $ins->execute();
        $newDecId = (int)$ins->insert_id;
        $ins->close();

        if ($okIns && $newDecId > 0) {
            $logDesc = 'Submitted equipment decommission request #' . $newDecId . ' for item ' . $itemCode . ' (' . $equipmentName . ').';
            logActivity($conn, 'Submit Decommission Request', 'decommission_request', $newDecId, $logDesc);
            inventoryLogActivity(
                $conn,
                inventoryActionWithItemCode('Submit Decommission Request', $itemCode),
                'DecommissionRequest',
                $newDecId,
                $logDesc,
                null,
                $itemCode
            );
            header('Location: inventory.php?view=decommission&status=decommission_sent');
            exit;
        }

        header('Location: inventory.php?view=decommission&status=error&message=' . rawurlencode('Could not save your request.'));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'review_decommission_request') {
        if (!$canReviewDecommission) {
            header('Location: inventory.php?view=list&status=error&message=' . rawurlencode('You are not allowed to review decommission requests.'));
            exit;
        }
        $requestId = (int)($_POST['request_id'] ?? 0);
        $newStatus = (string)($_POST['new_status'] ?? '');
        $remark = trim((string)($_POST['resolution_remark'] ?? ''));

        if ($requestId > 0 && ($newStatus === 'approved' || $newStatus === 'declined')) {
            $reviewerId = (int)$_SESSION['user_id'];
            $reviewerName = (string)($_SESSION['name'] ?? 'Supervisor');
            $stmt = $conn->prepare('
                UPDATE inventory_decommission_requests
                SET status = ?,
                    resolution_remark = ?,
                    reviewed_by_user_id = ?,
                    reviewed_by_name = ?,
                    resolved_at = NOW()
                WHERE id = ? AND status = \'pending\'
            ');
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
                        $itemCodeLog = '';
                        $q = $conn->prepare('SELECT item_code FROM inventory_decommission_requests WHERE id = ? LIMIT 1');
                        if ($q) {
                            $q->bind_param('i', $requestId);
                            $q->execute();
                            $rw = $q->get_result()->fetch_assoc();
                            $q->close();
                            $itemCodeLog = trim((string)($rw['item_code'] ?? ''));
                        }
                        $label = $newStatus === 'approved' ? 'Approve' : 'Decline';
                        $desc = "{$label}d decommission request #{$requestId}" . ($itemCodeLog !== '' ? " (Item ID: {$itemCodeLog})" : '') . ' by ' . $reviewerName . '.';
                        logActivity($conn, $label . ' Decommission Request', 'decommission_request', $requestId, $desc);
                        inventoryLogActivity(
                            $conn,
                            inventoryActionWithItemCode($label . ' Decommission Request', $itemCodeLog !== '' ? $itemCodeLog : 'REQ-' . $requestId),
                            'DecommissionRequest',
                            $requestId,
                            $desc,
                            $remark !== '' ? 'Remark: ' . $remark : null,
                            $itemCodeLog !== '' ? $itemCodeLog : null
                        );
                    }

                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    error_log('Employee decommission approval transaction failed: ' . $e->getMessage());
                }
            }
        }
        header('Location: inventory.php?view=decommission_review&status=review_updated');
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
                ii.brand_manufacturer,
                ii.`type` AS type,
                ii.item_condition,
                ii.remarks AS item_remarks,
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

    $dcCheck = $conn->query("SHOW TABLES LIKE 'inventory_decommission_requests'");
    if ($dcCheck && $dcCheck->num_rows > 0) {
        $myDStmt = $conn->prepare('
            SELECT *
            FROM inventory_decommission_requests
            WHERE employee_id = ?
            ORDER BY created_at DESC, id DESC
        ');
        if ($myDStmt) {
            $myDStmt->bind_param('i', $employeeDbId);
            $myDStmt->execute();
            $myDecommissionRequests = inventory_stmt_fetch_all_assoc($myDStmt);
            $myDStmt->close();
        }

        if ($canReviewDecommission) {
            $allRes = $conn->query("
                SELECT r.*, e.full_name AS requester_full_name, e.employee_id AS requester_code
                FROM inventory_decommission_requests r
                JOIN employees e ON e.id = r.employee_id
                ORDER BY CASE r.status WHEN 'pending' THEN 0 ELSE 1 END ASC, r.created_at DESC, r.id DESC
            ");
            if ($allRes) {
                while ($r = $allRes->fetch_assoc()) {
                    $allDecommissionRequests[] = $r;
                }
            }
        }
    }
}
if ($inventoryView === 'decommission_review' && !$canReviewDecommission) {
    header('Location: inventory.php?view=list');
    exit;
}

$decomAllocJson = '[]';
if (!$tableMissing) {
    $decomAllocJson = json_encode(array_map(static function (array $row): array {
        return [
            'allocationId' => (int)$row['id'],
            'itemId' => (string)($row['item_id'] ?? ''),
            'itemName' => (string)($row['item_name'] ?? ''),
            'description' => (string)($row['description'] ?? ''),
            'type' => (string)($row['type'] ?? ''),
            'brand' => (string)($row['brand_manufacturer'] ?? ''),
            'dateReceived' => (string)($row['date_received'] ?? ''),
            'itemRemarks' => (string)($row['item_remarks'] ?? ''),
        ];
    }, $allocatedItems), JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
}

switch ($inventoryView) {
    case 'request':
        $invSubtitle = 'View your request history and submit a new item request.';
        break;
    case 'decommission':
        $invSubtitle = 'Decommission request history and form to submit a new request.';
        break;
    case 'decommission_review':
        $invSubtitle = 'Review, approve, or decline employee decommission requests.';
        break;
    case 'list':
    default:
        $invSubtitle = 'Equipment and assets allocated to your account.';
        break;
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
    <?php if ($inventoryView === 'decommission'): ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
      .select2-container--default .select2-selection--single { min-height: 42px; border-color: #cbd5e1; border-radius: 0.5rem; }
      .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 2.25rem; padding-left: 12px; }
      .select2-container { min-width: 0; width: 100% !important; }
    </style>
    <?php endif; ?>
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
<body class="font-inter bg-[#f1f5f9] min-h-screen" data-inventory-view="<?php echo htmlspecialchars($inventoryView, ENT_QUOTES, 'UTF-8'); ?>">
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
            <a href="reimbursement.php" data-url="reimbursement.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2m6 4H9m6-8H9m10 14H5a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z" />
                </svg>
                <span>My Reimbursement</span>
            </a>
            <a href="compensation.php" data-url="compensation.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>My Compensation</span>
            </a>
            <?php include __DIR__ . '/include/sidebar-my-inventory-nav.php'; ?>
            <?php include __DIR__ . '/include/sidebar-performance-nav.php'; ?>
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
            <a href="module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
    </aside>

    <!-- Mobile sidebar backdrop -->
    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <main class="min-h-screen p-8 space-y-6 overflow-y-auto md:ml-64 md:pt-8 pt-16">
        <div id="main-inner" class="min-w-0 max-w-full">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">My Inventory</h1>
                    <p class="text-sm text-slate-500 mt-1"><?php echo htmlspecialchars($invSubtitle); ?></p>
                </div>
                <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                    <span><?php echo htmlspecialchars($department); ?></span>
                    <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                    <span><?php echo htmlspecialchars($position); ?></span>
                </div>
            </div>

            <div class="space-y-6">
                <?php if ($status === 'appeal_sent'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                            Appeal sent successfully. An admin will see it under inventory messages.
                        </div>
                    </div>
                <?php elseif ($status === 'request_sent'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                            Your item request was sent. An admin will review it under Inventory → Request.
                        </div>
                    </div>
                <?php elseif ($status === 'decommission_sent'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                            Decommission request saved. A supervisor or inventory admin will review it.
                        </div>
                    </div>
                <?php elseif ($status === 'review_updated'): ?>
                    <div class="p-6 pb-0">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                            Request status updated.
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
                    <?php include __DIR__ . '/include/inventory_main_tabs.inc.php'; ?>
                <?php endif; ?>
            </div>
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
                    <textarea name="employee_appeal" id="appealText" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g. This is not the item assigned to me." required></textarea>
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
    <?php if ($inventoryView === 'decommission'): ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <?php endif; ?>
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
        var invView = document.body.getAttribute('data-inventory-view') || 'list';
        if (invView === 'list' && $('#inventoryTable').length && $('#inventoryTable tbody tr').length > 0 && $('#inventoryTable tbody tr').first().find('td').length > 1) {
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
          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'index.php' || url === 'request.php' || url === 'progressive-discipline.php' || url === 'reimbursement.php' || pathOnly === 'inventory.php' || ['performance.php', 'performance-my-reviews.php', 'performance-form-review.php', 'performance-review-received.php', 'performance-review-submissions.php'].indexOf(pathOnly) !== -1 || ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'].indexOf(pathOnly) !== -1) {
            window.location.href = url;
            return;
          }

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner', function () {
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

        const decomDataEl = document.getElementById('decomAllocationData');
        const decomSelect = document.getElementById('decom_item_select');
        const descTa = document.getElementById('item_description_display');
        const remarksTa = document.getElementById('item_remarks_display');
        function applyDecomPrefill() {
          if (!decomDataEl || !decomSelect || !remarksTa) return;
          var rows = [];
          try { rows = JSON.parse(decomDataEl.textContent || '[]'); } catch (e) { return; }
          var id = parseInt(decomSelect.value, 10) || 0;
          if (!id) {
            if (descTa) descTa.value = '';
            remarksTa.value = '';
            return;
          }
          var row = null;
          for (var i = 0; i < rows.length; i++) {
            if (rows[i].allocationId === id) { row = rows[i]; break; }
          }
          if (!row) {
            if (descTa) descTa.value = '';
            remarksTa.value = '';
            return;
          }
          var d = row.description != null ? String(row.description) : '';
          if (descTa) {
            descTa.value = d.trim() !== '' ? d : "(No description on this item's inventory record.)";
          }
          var r = row.itemRemarks != null ? String(row.itemRemarks) : '';
          remarksTa.value = r.trim() !== '' ? r : "(No remarks on this item's inventory record.)";
        }
        if (decomSelect && window.jQuery && typeof $.fn.select2 === 'function') {
          $('#decom_item_select').select2({
            width: '100%',
            placeholder: 'Search or select an item…',
            allowClear: false
          });
          $('#decom_item_select').on('change select2:select', applyDecomPrefill);
          applyDecomPrefill();
        } else if (decomSelect) {
          decomSelect.addEventListener('change', applyDecomPrefill);
          applyDecomPrefill();
        }
      });
    </script>
</body>
</html>
