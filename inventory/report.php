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

function getInventoryReportRows(mysqli $conn): array
{
    $rows = [];
    $result = $conn->query("
        SELECT
            ii.item_name,
            ii.item_id,
            ii.description,
            e.full_name,
            e.employee_id AS employee_code
        FROM inventory_items ii
        LEFT JOIN inventory_item_allocations ia ON ia.inventory_item_id = ii.id AND ia.date_return IS NULL
        LEFT JOIN employees e ON e.id = ia.employee_id
        ORDER BY ii.item_name ASC, ii.item_id ASC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function getAllocatedToLabel(array $row): string
{
    $fullName = trim((string)($row['full_name'] ?? ''));
    $employeeCode = trim((string)($row['employee_code'] ?? ''));

    if ($fullName === '') {
        return 'Not Allocated';
    }
    if ($employeeCode === '') {
        return $fullName;
    }
    return $fullName . ' (' . $employeeCode . ')';
}

function outputPrintableInventoryReportFallback(array $rows): void
{
    $htmlRows = '';
    foreach ($rows as $row) {
        $htmlRows .= '<tr>';
        $htmlRows .= '<td>' . htmlspecialchars((string)($row['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)($row['item_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars(getAllocatedToLabel($row), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '</tr>';
    }

    if ($htmlRows === '') {
        $htmlRows = '<tr><td colspan="4">No records found.</td></tr>';
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Inventory Report</title>';
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
    echo '<h2>Inventory Report</h2>';
    echo '<p class="meta">Generated: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<table><thead><tr><th>Item Name</th><th>Item ID</th><th>Description</th><th>Allocated To</th></tr></thead><tbody>' . $htmlRows . '</tbody></table>';
    echo '</body></html>';
    exit;
}

$reportRows = getInventoryReportRows($conn);

$export = strtolower(trim((string)($_GET['export'] ?? '')));
if ($export === 'excel') {
    inventoryLogActivity($conn, inventoryActionWithItemCode('Export Inventory Report Excel', 'ALL'), 'Report Export', null, 'Admin exported inventory report to Excel.', null, 'ALL');
    $filename = 'inventory-report-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    if ($out !== false) {
        fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Item Name', 'Item ID', 'Description', 'Allocated To']);
        foreach ($reportRows as $row) {
            fputcsv($out, [
                (string)($row['item_name'] ?? ''),
                (string)($row['item_id'] ?? ''),
                (string)($row['description'] ?? ''),
                getAllocatedToLabel($row),
            ]);
        }
        fclose($out);
    }
    exit;
}

if ($export === 'pdf') {
    inventoryLogActivity($conn, inventoryActionWithItemCode('Export Inventory Report PDF', 'ALL'), 'Report Export', null, 'Admin exported inventory report to PDF.', null, 'ALL');
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists(\Dompdf\Dompdf::class)) {
        outputPrintableInventoryReportFallback($reportRows);
    }

    $htmlRows = '';
    foreach ($reportRows as $row) {
        $htmlRows .= '<tr>';
        $htmlRows .= '<td>' . htmlspecialchars((string)($row['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)($row['item_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '<td>' . htmlspecialchars(getAllocatedToLabel($row), ENT_QUOTES, 'UTF-8') . '</td>';
        $htmlRows .= '</tr>';
    }

    if ($htmlRows === '') {
        $htmlRows = '<tr><td colspan="4">No records found.</td></tr>';
    }

    $pdfHtml = '
        <html>
        <head>
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #0f172a; }
                h2 { margin: 0 0 4px 0; }
                .meta { margin: 0 0 12px 0; font-size: 11px; color: #475569; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
                th { background: #f8fafc; font-weight: bold; }
            </style>
        </head>
        <body>
            <h2>Inventory Report</h2>
            <p class="meta">Generated: ' . date('Y-m-d H:i:s') . '</p>
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Item ID</th>
                        <th>Description</th>
                        <th>Allocated To</th>
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
    $dompdf->stream('inventory-report-' . date('Ymd-His') . '.pdf', ['Attachment' => true]);
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
    <title>Inventory Report</title>
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

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Inventory Report</h1>
                <p class="text-sm text-slate-500">Item name, item id, description, and allocation owner.</p>
            </div>
            <div class="flex gap-2">
                <a href="report.php?export=excel" class="px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700">Export to Excel</a>
                <a href="report.php?export=pdf" class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700">Export to PDF</a>
            </div>
        </div>

        <?php if ($status === 'error'): ?>
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <?php echo htmlspecialchars($message !== '' ? $message : 'Something went wrong.'); ?>
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="overflow-x-auto">
                <table id="reportTable" class="display stripe hover w-full text-sm">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Item ID</th>
                            <th>Description</th>
                            <th>Allocated To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)($row['item_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($row['item_id'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($row['description'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars(getAllocatedToLabel($row)); ?></td>
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
            $('#reportTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']]
            });
        });
    </script>

    <script src="../admin/include/sidebar-dropdown.js"></script>
</body>
</html>
