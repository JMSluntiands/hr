<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Services\EmployeeContext;
use App\Support\ManilaTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        if ($request->boolean('is_bulk')) {
            return $this->storeBulk($request, $context);
        }

        $validated = $request->validate([
            'expense_type' => 'required|string|max:100',
            'expense_description' => 'required|string|max:2000',
            'purchased_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $employee = $context->requireEmployee();
        [$receiptPath, $receiptName] = $this->uploadReceipt($request->file('receipt'));

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
            'created_at' => ManilaTime::now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Reimbursement request submitted for review.']);
    }

    private function storeBulk(Request $request, EmployeeContext $context): JsonResponse
    {
        $types = $request->input('bulk_expense_type', []);
        $descriptions = $request->input('bulk_expense_description', []);
        $dates = $request->input('bulk_purchased_date', []);
        $amounts = $request->input('bulk_amount', []);
        $bulkNotes = trim((string) $request->input('bulk_notes', ''));

        if (! is_array($types) || count($types) === 0) {
            return response()->json(['status' => 'error', 'message' => 'No bulk items found. Add at least one item.']);
        }

        $employee = $context->requireEmployee();
        $inserted = 0;
        $errors = [];
        $itemCount = count($types);

        for ($i = 0; $i < $itemCount; $i++) {
            $expenseType = trim((string) ($types[$i] ?? ''));
            $description = trim((string) ($descriptions[$i] ?? ''));
            $purchasedDate = trim((string) ($dates[$i] ?? ''));
            $amount = (float) ($amounts[$i] ?? 0);

            if ($expenseType === '' || $description === '' || $purchasedDate === '' || $amount <= 0) {
                $errors[] = 'Item '.($i + 1).': missing or invalid fields.';

                continue;
            }

            [$receiptPath, $receiptName, $uploadError] = $this->uploadReceiptByKey($request, 'bulk_receipt_'.$i);
            if ($uploadError) {
                $errors[] = 'Item '.($i + 1).': '.$uploadError;

                continue;
            }

            Reimbursement::query()->create([
                'employee_id' => $employee->id,
                'expense_type' => $expenseType,
                'expense_description' => $description,
                'purchased_date' => $purchasedDate,
                'amount' => $amount,
                'notes' => $bulkNotes !== '' ? $bulkNotes : null,
                'receipt_path' => $receiptPath,
                'receipt_original_name' => $receiptName,
                'status' => 'Pending',
                'created_at' => ManilaTime::now(),
            ]);
            $inserted++;
        }

        if ($inserted === 0) {
            return response()->json(['status' => 'error', 'message' => 'No items were saved. '.implode(' ', $errors)]);
        }

        $message = $inserted.' reimbursement request'.($inserted > 1 ? 's' : '').' submitted for review.';
        if (count($errors) > 0) {
            $message = $inserted.' of '.$itemCount.' items submitted. Issues: '.implode(' ', $errors);
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    /** @return array{0: ?string, 1: ?string} */
    private function uploadReceipt(?UploadedFile $file): array
    {
        if (! $file) {
            return [null, null];
        }

        $dir = base_path('uploads/reimbursements');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $receiptName = $file->getClientOriginalName();
        $filename = 'receipt_'.date('Ymd_His').'_'.Str::random(12).'.'.$file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return ['reimbursements/'.$filename, $receiptName];
    }

    /** @return array{0: ?string, 1: ?string, 2: ?string} */
    private function uploadReceiptByKey(Request $request, string $key): array
    {
        if (! $request->hasFile($key)) {
            return [null, null, null];
        }

        $file = $request->file($key);
        if (! $file->isValid()) {
            return [null, null, 'Receipt upload failed.'];
        }

        [$path, $name] = $this->uploadReceipt($file);

        return [$path, $name, null];
    }
}
