<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryActivityLogger;
use App\Services\Inventory\InventoryAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AllocationController extends Controller
{
    public function __construct(
        private InventoryAllocationService $allocations,
        private InventoryActivityLogger $activity,
    ) {}

    public function index(Request $request): View
    {
        $employeeId = (int) $request->query('employee_id', 0);

        return view('inventory.allocation.index', [
            'tableReady' => $this->allocations->ensureReady(),
            'employees' => $this->allocations->activeEmployees(),
            'availableItems' => $this->allocations->availableItems(),
            'allocationRows' => $this->allocations->activeAllocations(),
            'selectedEmployeeId' => $employeeId,
            'status' => (string) $request->query('status', ''),
            'message' => (string) $request->query('message', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $this->allocations->create(
                (int) $request->input('employee_id', 0),
                (int) $request->input('inventory_item_id', 0),
                trim((string) $request->input('date_received', '')),
            );

            return redirect()->route('inventory.allocation.index', ['status' => 'created']);
        } catch (\RuntimeException $e) {
            return redirect()->route('inventory.allocation.index', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function returnItem(Request $request): RedirectResponse
    {
        try {
            $this->allocations->returnItem(
                (int) $request->input('allocation_id', 0),
                trim((string) $request->input('date_return', '')),
                trim((string) $request->input('return_remarks', '')),
            );

            return redirect()->route('inventory.allocation.index', ['status' => 'returned']);
        } catch (\RuntimeException $e) {
            return redirect()->route('inventory.allocation.index', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function exportPdf(Request $request): Response
    {
        $employeeId = (int) $request->query('employee_id', 0);
        $targetItemCode = $employeeId > 0 ? 'FILTERED' : 'ALL';
        $this->activity->log(
            $this->activity::actionWithItemCode('Export Allocation Report PDF', $targetItemCode),
            'Allocation Report',
            $employeeId > 0 ? $employeeId : null,
            'Admin exported allocation report to PDF.',
            null,
            $targetItemCode,
        );
        $rows = $this->allocations->activeAllocations($employeeId);
        $titleSuffix = $employeeId > 0 ? ' (Filtered by Employee)' : '';
        $htmlRows = '';
        foreach ($rows as $row) {
            $label = ($row->full_name ?? '').' ('.($row->emp_code ?? '').')';
            $htmlRows .= '<tr>';
            $htmlRows .= '<td>'.e($label).'</td>';
            $htmlRows .= '<td>'.e($row->item_id ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->item_name ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->description ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->type ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->item_condition ?? '').'</td>';
            $htmlRows .= '<td>'.e($row->date_received ?? '').'</td>';
            $htmlRows .= '</tr>';
        }
        if ($htmlRows === '') {
            $htmlRows = '<tr><td colspan="7">No allocation records found.</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Allocation Report</title>
            <style>body{font-family:Arial,sans-serif;font-size:11px;margin:20px;}
            table{width:100%;border-collapse:collapse;}th,td{border:1px solid #cbd5e1;padding:6px;}
            th{background:#f8fafc;}</style></head><body>
            <p><a href="#" onclick="window.print();return false;">Print / Save as PDF</a></p>
            <h2>Inventory Item Allocation Report'.e($titleSuffix).'</h2>
            <p>Generated: '.date('Y-m-d H:i:s').'</p>
            <table><thead><tr><th>Employee</th><th>Item ID</th><th>Item Name</th><th>Description</th><th>Type</th><th>Condition</th><th>Date Received</th></tr></thead>
            <tbody>'.$htmlRows.'</tbody></table></body></html>';

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
