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

function getAllocationRows(mysqli $conn, int $employeeId = 0): array
{
    $rows = [];
    $sql = "
        SELECT
            ia.id,
            ia.employee_id,
            ia.inventory_item_id,
            ia.date_received,
            ia.return_remarks,
            e.full_name,
            e.employee_id AS emp_code,
            ii.item_id,
            ii.item_name,
            ii.description,
            ii.type,
            ii.item_condition
        FROM inventory_item_allocations ia
        JOIN employees e ON e.id = ia.employee_id
        JOIN inventory_items ii ON ii.id = ia.inventory_item_id
        WHERE ia.date_return IS NULL
    ";

    if ($employeeId > 0) {
        $sql .= " AND ia.employee_id = ?";
    }

    $sql .= " ORDER BY e.full_name ASC, ia.date_received DESC, ia.id DESC";

    if ($employeeId > 0) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();
        return $rows;
    }

    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function outputPrintableAllocationFallback(array $rows, bool $isFiltered): void
{
    $titleSuffix = $isFiltered ? ' (Filtered by Employee)' : '';
    $htmlRows = '';
    foreach ($rows as $row) {
        $employeeLabel = (string)$row['full_name'] . ' (' . (string)$row['emp_code'] . ')';
        $htmlRows .= '<tr>';
        $htmlRows .= '<td>' . htmlspecialchars($employeeLabel, ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['item_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['item_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['type'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['item_condition'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['date_received'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '</tr>';
    }

    if ($htmlRows === '') {
        $htmlRows = '<tr><td colspan="7">No allocation records found.</td></tr>';
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Allocation Report</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; color: #0f172a; }
        h2 { margin: 0 0 4px 0; }
        .meta { margin: 0 0 10px 0; font-size: 11px; color: #475569; }
        .notice { margin: 0 0 12px 0; padding: 8px 10px; border: 1px solid #fde68a; background: #fffbeb; color: #92400e; border-radius: 6px; }
        .actions { margin-bottom: 12px; }
        .btn { display: inline-block; padding: 8px 12px; background: #dc2626; color: #fff; text-decoration: none; border-radius: 6px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; }
        @media print { .actions, .notice { display: none; } body { margin: 0; } }
    </style></head><body>';
    echo '<div class="actions"><a href="#" class="btn" onclick="window.print(); return false;">Print / Save as PDF</a></div>';
    echo '<p class="notice">PDF library is unavailable on this server. Use "Print / Save as PDF" to export.</p>';
    echo '<h2>Inventory Item Allocation Report' . htmlspecialchars($titleSuffix, ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p class="meta">Generated: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<table><thead><tr><th>Employee</th><th>Item ID</th><th>Item Name</th><th>Description</th><th>Type</th><th>Condition</th><th>Date Received</th></tr></thead><tbody>' . $htmlRows . '</tbody></table>';
    echo '</body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $inventoryItemId = (int)($_POST['inventory_item_id'] ?? 0);
        $dateReceived = trim((string)($_POST['date_received'] ?? ''));

        if ($employeeId <= 0 || $inventoryItemId <= 0 || $dateReceived === '') {
            header('Location: allocation.php?status=error&message=Please+fill+in+all+required+fields.');
            exit;
        }

        $checkStmt = $conn->prepare('
            SELECT id
            FROM inventory_item_allocations
            WHERE inventory_item_id = ? AND date_return IS NULL
            LIMIT 1
        ');
        $checkStmt->bind_param('i', $inventoryItemId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $alreadyAllocated = $checkResult && $checkResult->num_rows > 0;
        $checkStmt->close();

        if ($alreadyAllocated) {
            header('Location: allocation.php?status=error&message=Selected+item+is+already+allocated.');
            exit;
        }

        $insertStmt = $conn->prepare('
            INSERT INTO inventory_item_allocations (inventory_item_id, employee_id, date_received)
            VALUES (?, ?, ?)
        ');
        $insertStmt->bind_param('iis', $inventoryItemId, $employeeId, $dateReceived);
        $ok = $insertStmt->execute();
        $insertStmt->close();

        if ($ok) {
            $desc = 'Allocated inventory item #' . $inventoryItemId . ' to employee #' . $employeeId . ' (date received: ' . $dateReceived . ').';
            $itemCode = inventoryGetItemCodeByItemDbId($conn, $inventoryItemId);
            inventoryLogActivity($conn, inventoryActionWithItemCode('Create Allocation', $itemCode), 'Allocation', $inventoryItemId, $desc, null, $itemCode);
            header('Location: allocation.php?status=created');
        } else {
            $itemCode = inventoryGetItemCodeByItemDbId($conn, $inventoryItemId);
            inventoryLogActivity($conn, inventoryActionWithItemCode('Create Allocation Failed', $itemCode), 'Allocation', $inventoryItemId, 'Failed to allocate inventory item #' . $inventoryItemId . ' to employee #' . $employeeId . '.', null, $itemCode);
            header('Location: allocation.php?status=error&message=Unable+to+save+allocation.');
        }
        exit;
    }

    if ($action === 'return') {
        $allocationId = (int)($_POST['allocation_id'] ?? 0);
        $dateReturn = trim((string)($_POST['date_return'] ?? ''));
        $returnRemarks = trim((string)($_POST['return_remarks'] ?? ''));

        if ($allocationId <= 0 || $dateReturn === '') {
            header('Location: allocation.php?status=error&message=Please+provide+Date+Return.');
            exit;
        }

        $checkStmt = $conn->prepare('
            SELECT date_received
            FROM inventory_item_allocations
            WHERE id = ? AND date_return IS NULL
            LIMIT 1
        ');
        $checkStmt->bind_param('i', $allocationId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existing = $checkResult ? $checkResult->fetch_assoc() : null;
        $checkStmt->close();

        if (!$existing) {
            header('Location: allocation.php?status=error&message=Allocation+record+not+found+or+already+returned.');
            exit;
        }

        $dateReceived = (string)($existing['date_received'] ?? '');
        if ($dateReceived !== '' && $dateReturn < $dateReceived) {
            header('Location: allocation.php?status=error&message=Date+Return+cannot+be+earlier+than+Date+Received.');
            exit;
        }

        $updateStmt = $conn->prepare('
            UPDATE inventory_item_allocations
            SET date_return = ?, return_remarks = NULLIF(?, \'\')
            WHERE id = ? AND date_return IS NULL
        ');
        $updateStmt->bind_param('ssi', $dateReturn, $returnRemarks, $allocationId);
        $ok = $updateStmt->execute();
        $affected = $updateStmt->affected_rows;
        $updateStmt->close();

        if ($ok && $affected > 0) {
            $desc = 'Returned allocation #' . $allocationId . ' (date return: ' . $dateReturn . ').';
            $itemCode = inventoryGetItemCodeByAllocationId($conn, $allocationId);
            inventoryLogActivity($conn, inventoryActionWithItemCode('Return Allocation', $itemCode), 'Allocation', $allocationId, $desc, null, $itemCode);
            header('Location: allocation.php?status=returned');
        } else {
            $itemCode = inventoryGetItemCodeByAllocationId($conn, $allocationId);
            inventoryLogActivity($conn, inventoryActionWithItemCode('Return Allocation Failed', $itemCode), 'Allocation', $allocationId, 'Failed to return allocation #' . $allocationId . '.', null, $itemCode);
            header('Location: allocation.php?status=error&message=Unable+to+process+return.');
        }
        exit;
    }
}

$employees = [];
$employeeResult = $conn->query("
    SELECT id, employee_id, full_name
    FROM employees
    WHERE status = 'Active'
    ORDER BY full_name ASC
");
if ($employeeResult) {
    while ($row = $employeeResult->fetch_assoc()) {
        $employees[] = $row;
    }
}

$availableItems = [];
$itemResult = $conn->query("
    SELECT ii.id, ii.item_id, ii.item_name, ii.description
    FROM inventory_items ii
    LEFT JOIN inventory_item_allocations ia ON ia.inventory_item_id = ii.id AND ia.date_return IS NULL
    WHERE ia.id IS NULL
    ORDER BY ii.item_name ASC, ii.item_id ASC
");
if ($itemResult) {
    while ($row = $itemResult->fetch_assoc()) {
        $availableItems[] = $row;
    }
}

$allocations = getAllocationRows($conn);

$selectedEmployeeIdForExport = (int)($_GET['employee_id'] ?? 0);
$export = strtolower(trim((string)($_GET['export'] ?? '')));
if ($export === 'pdf') {
    $targetItemCode = $selectedEmployeeIdForExport > 0 ? 'FILTERED' : 'ALL';
    inventoryLogActivity($conn, inventoryActionWithItemCode('Export Allocation Report PDF', $targetItemCode), 'Allocation Report', $selectedEmployeeIdForExport > 0 ? $selectedEmployeeIdForExport : null, 'Admin exported allocation report to PDF.', null, $targetItemCode);
    $rowsForPdf = getAllocationRows($conn, $selectedEmployeeIdForExport);

    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists(\Dompdf\Dompdf::class)) {
        outputPrintableAllocationFallback($rowsForPdf, $selectedEmployeeIdForExport > 0);
    }

    $htmlRows = '';
    foreach ($rowsForPdf as $row) {
        $employeeLabel = (string)$row['full_name'] . ' (' . (string)$row['emp_code'] . ')';
        $htmlRows .= '<tr>';
        $htmlRows .= '<td>' . htmlspecialchars($employeeLabel, ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['item_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['item_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['description'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['type'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['item_condition'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)$row['date_received'], ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '</tr>';
    }

    if ($htmlRows === '') {
        $htmlRows = '<tr><td colspan="7">No allocation records found.</td></tr>';
    }

    $titleSuffix = $selectedEmployeeIdForExport > 0 ? ' (Filtered by Employee)' : '';
    $pdfHtml = '
        <html>
        <head>
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #0f172a; }
                h2 { margin: 0 0 4px 0; }
                .meta { margin: 0 0 12px 0; font-size: 10px; color: #475569; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; }
                th { background: #f8fafc; font-weight: bold; }
            </style>
        </head>
        <body>
            <h2>Inventory Item Allocation Report' . htmlspecialchars($titleSuffix, ENT_QUOTES, 'UTF-8') . '</h2>
            <p class="meta">Generated: ' . date('Y-m-d H:i:s') . '</p>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Item ID</th>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Condition</th>
                        <th>Date Received</th>
                    </tr>
                </thead>
                <tbody>' . $htmlRows . '</tbody>
            </table>
        </body>
        </html>
    ';

    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
    $dompdf->loadHtml($pdfHtml);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('allocation-report-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
    exit;
}

$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            color: #334155 !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            margin-left: 6px !important;
            font-size: 13px !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            border-color: #FA9800 !important;
            background: #FA9800 !important;
            color: #ffffff !important;
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
        <h1 class="text-2xl font-semibold text-slate-800 mb-6">Item Allocation</h1>

        <?php if ($status === 'created'): ?>
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">Item allocation saved successfully.</div>
        <?php elseif ($status === 'returned'): ?>
            <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 text-sm">Item returned successfully. Allocation removed from employee.</div>
        <?php elseif ($status === 'error'): ?>
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <?php echo htmlspecialchars($message !== '' ? $message : 'Something went wrong.'); ?>
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Allocate Item to Employee</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Employee</label>
                    <select name="employee_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo (int)$employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['full_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Item</label>
                    <select name="inventory_item_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Select item</option>
                        <?php foreach ($availableItems as $item): ?>
                            <option value="<?php echo (int)$item['id']; ?>">
                                <?php
                                echo htmlspecialchars(
                                    $item['item_id'] . ' - ' . $item['item_name'] .
                                    ((string)$item['description'] !== '' ? ' (' . $item['description'] . ')' : '')
                                );
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-slate-600 mb-1">Date Received</label>
                    <input type="date" name="date_received" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                </div>

                <div class="md:col-span-3">
                    <button type="submit" class="px-4 py-2 bg-[#FA9800] text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
                        Save Allocation
                    </button>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Allocated Items</h2>
                    <p class="text-sm text-slate-500">Choose an employee first. Table is empty by default.</p>
                </div>
                <div class="w-full md:w-[520px]">
                    <label class="block text-sm text-slate-600 mb-1">Employee Filter</label>
                    <select id="employeeFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select employee to view allocations</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo (int)$employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['full_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="allocationTable" class="display stripe hover w-full text-sm">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Condition</th>
                            <th>Date Received</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <a id="exportPdfBtn" href="allocation.php?export=pdf" class="hidden inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 transition whitespace-nowrap">
                    Export to PDF
                </a>
            </div>
        </section>
    </main>

    <div id="returnModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="p-5 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-800">Return Item</h3>
                <p class="text-sm text-slate-500 mt-1">Set Date Return to remove this item from employee allocation.</p>
            </div>
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="action" value="return">
                <input type="hidden" name="allocation_id" id="returnAllocationId">
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Date Return</label>
                    <input type="date" name="date_return" id="returnDate" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Remarks (Optional)</label>
                    <textarea name="return_remarks" id="returnRemarks" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Ilagay kung may sira o issue sa item."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancelReturnBtn" class="px-4 py-2 rounded-lg text-sm bg-slate-200 text-slate-700 hover:bg-slate-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-[#FA9800] text-white hover:opacity-90">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        const allocationRows = <?php echo json_encode($allocations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const selectedEmployeeFromQuery = <?php echo (int)$selectedEmployeeIdForExport; ?>;

        $(function () {
            const table = $('#allocationTable').DataTable({
                pageLength: 10,
                language: {
                    emptyTable: 'Select an employee to view allocated items.'
                }
            });

            function renderByEmployee(employeeId) {
                table.clear();
                const exportBtn = document.getElementById('exportPdfBtn');
                const shouldShowExport = Boolean(employeeId);
                exportBtn.classList.toggle('hidden', !shouldShowExport);
                exportBtn.href = employeeId
                    ? 'allocation.php?export=pdf&employee_id=' + encodeURIComponent(employeeId)
                    : 'allocation.php?export=pdf';

                if (!employeeId) {
                    table.draw();
                    return;
                }

                const filtered = allocationRows.filter(function (row) {
                    return String(row.employee_id) === String(employeeId);
                });

                filtered.forEach(function (row) {
                    const employeeLabel = row.full_name + ' (' + row.emp_code + ')';
                    const returnBtnHtml =
                        '<button type="button" class="returnBtn px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500 text-white hover:bg-amber-600" data-allocation-id="' +
                        String(row.id) +
                        '">Return</button>';
                    table.row.add([
                        employeeLabel,
                        row.item_id ?? '',
                        row.item_name ?? '',
                        row.description ?? '',
                        row.type ?? '',
                        row.item_condition ?? '',
                        row.date_received ?? '',
                        returnBtnHtml
                    ]);
                });

                table.draw();
            }

            $('#employeeFilter').on('change', function () {
                renderByEmployee(this.value);
            });

            if (selectedEmployeeFromQuery > 0) {
                $('#employeeFilter').val(String(selectedEmployeeFromQuery));
                renderByEmployee(String(selectedEmployeeFromQuery));
            }

            const returnModal = document.getElementById('returnModal');
            const returnAllocationIdInput = document.getElementById('returnAllocationId');
            const returnDateInput = document.getElementById('returnDate');
            const returnRemarksInput = document.getElementById('returnRemarks');
            const cancelReturnBtn = document.getElementById('cancelReturnBtn');

            function closeReturnModal() {
                returnModal.classList.add('hidden');
                returnAllocationIdInput.value = '';
                returnDateInput.value = '';
                returnRemarksInput.value = '';
            }

            $('#allocationTable').on('click', '.returnBtn', function () {
                const allocationId = this.dataset.allocationId || '';
                returnAllocationIdInput.value = allocationId;
                returnDateInput.value = new Date().toISOString().slice(0, 10);
                returnModal.classList.remove('hidden');
            });

            cancelReturnBtn.addEventListener('click', closeReturnModal);
            returnModal.addEventListener('click', function (event) {
                if (event.target === returnModal) {
                    closeReturnModal();
                }
            });
        });
    </script>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
