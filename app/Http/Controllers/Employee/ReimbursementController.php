<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Services\EmployeeContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReimbursementController extends Controller
{
    public function index(EmployeeContext $context): View
    {
        $employee = $context->requireEmployee();
        $rows = Reimbursement::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->get();

        return view('employee.reimbursements.index', compact('rows', 'employee'));
    }

    public function store(Request $request, EmployeeContext $context): JsonResponse
    {
        $validated = $request->validate([
            'expense_type' => 'required|string|max:100',
            'expense_description' => 'required|string|max:2000',
            'purchased_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $employee = $context->requireEmployee();
        $receiptPath = null;
        $receiptName = null;

        if ($request->hasFile('receipt')) {
            $dir = base_path('uploads/reimbursements');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file = $request->file('receipt');
            $receiptName = $file->getClientOriginalName();
            $filename = 'receipt_'.date('Ymd_His').'_'.Str::random(12).'.'.$file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $receiptPath = 'reimbursements/'.$filename;
        }

        Reimbursement::query()->create([
            'employee_id' => $employee->id,
            'expense_type' => $validated['expense_type'],
            'expense_description' => $validated['expense_description'],
            'purchased_date' => $validated['purchased_date'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'receipt_path' => $receiptPath,
            'receipt_original_name' => $receiptName,
            'status' => 'Pending',
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Reimbursement request submitted.']);
    }
}
