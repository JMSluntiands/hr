<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryActivityLogger;
use App\Services\Inventory\InventoryReportService;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private InventoryReportService $reports,
        private InventoryActivityLogger $activity,
    ) {}

    public function index(): View
    {
        return view('inventory.report.index', [
            'tableReady' => $this->reports->ensureReady(),
            'rows' => $this->reports->reportRows(),
        ]);
    }

    public function exportExcel(): StreamedResponse
    {
        $this->activity->log(
            $this->activity::actionWithItemCode('Export Inventory Report Excel', 'ALL'),
            'Report Export',
            null,
            'Admin exported inventory report to Excel.',
            null,
            'ALL',
        );

        $rows = $this->reports->reportRows();
        $filename = 'inventory-report-'.date('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Item Name', 'Item ID', 'Description', 'Allocated To']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) ($row->item_name ?? ''),
                    (string) ($row->item_id ?? ''),
                    (string) ($row->description ?? ''),
                    InventoryReportService::allocatedToLabel($row),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(): Response
    {
        $this->activity->log(
            $this->activity::actionWithItemCode('Export Inventory Report PDF', 'ALL'),
            'Report Export',
            null,
            'Admin exported inventory report to PDF.',
            null,
            'ALL',
        );

        $rows = $this->reports->reportRows();
        $htmlRows = '';
        foreach ($rows as $row) {
            $htmlRows .= '<tr>';
            $htmlRows .= '<td>'.e($row->item_name ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->item_id ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->description ?? '').'</td>';
            $htmlRows .= '<td>'.e(InventoryReportService::allocatedToLabel($row)).'</td>';
            $htmlRows .= '</tr>';
        }
        if ($htmlRows === '') {
            $htmlRows = '<tr><td colspan="4">No records found.</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Inventory Report</title>
            <style>body{font-family:Arial,sans-serif;font-size:12px;margin:20px;}
            table{width:100%;border-collapse:collapse;}th,td{border:1px solid #cbd5e1;padding:6px;}
            th{background:#f8fafc;}@media print{.actions,.notice{display:none;}}</style></head><body>
            <div class="actions"><a href="#" onclick="window.print();return false;" style="padding:8px 12px;background:#dc2626;color:#fff;border-radius:6px;text-decoration:none;">Print / Save as PDF</a></div>
            <p class="notice" style="background:#fffbeb;border:1px solid #fde68a;padding:8px;">Use Print / Save as PDF to export.</p>
            <h2>Inventory Report</h2><p>Generated: '.date('Y-m-d H:i:s').'</p>
            <table><thead><tr><th>Item Name</th><th>Item ID</th><th>Description</th><th>Allocated To</th></tr></thead>
            <tbody>'.$htmlRows.'</tbody></table></body></html>';

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
