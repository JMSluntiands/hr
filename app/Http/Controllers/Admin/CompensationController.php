<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\CompensationService;
use App\Services\HrSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationController extends Controller
{
    public function __construct(
        private CompensationService $compensation,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        return view('admin.compensation.index', [
            'adjustments' => $this->compensation->listAdjustments(),
            'todayYmd' => now()->format('Y-m-d'),
            'adminName' => (string) session(HrSession::NAME, 'Admin'),
        ]);
    }

    public function employees(): JsonResponse
    {
        $employees = $this->compensation->activeEmployees()->map(fn ($e) => [
            'id' => (int) $e->id,
            'employee_id' => $e->employee_id,
            'full_name' => $e->full_name,
        ])->values();

        return response()->json(['status' => 'success', 'data' => $employees]);
    }

    public function employeeSalary(Request $request): JsonResponse
    {
        $employeeId = (int) $request->query('employee_id');
        if ($employeeId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Employee ID is required'], 422);
        }

        return response()->json([
            'status' => 'success',
            'salary' => $this->compensation->currentSalary($employeeId),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|min:1',
            'previous_salary' => 'required|numeric|min:0',
            'new_salary' => 'required|numeric|min:0',
            'reason' => 'required|in:Promotion,Annual Increase,Adjustment,Other',
            'date_approved' => 'required|date',
        ]);

        $approvedBy = trim((string) $request->input('approved_by', session(HrSession::NAME, 'Admin')));
        if ($approvedBy === '') {
            $approvedBy = 'Admin';
        }

        try {
            $saved = $this->compensation->saveAdjustment(
                (int) $validated['employee_id'],
                (float) $validated['previous_salary'],
                (float) $validated['new_salary'],
                $validated['reason'],
                $validated['date_approved'],
                $approvedBy,
            );

            $adminId = (int) $this->hrSession->userId();
            $this->activityLogger->log(
                $adminId,
                $approvedBy,
                'Salary Adjustment',
                sprintf(
                    'Adjusted salary for employee #%d: %s → %s (%s)',
                    $saved['employee_id'],
                    number_format($saved['previous_salary'], 2),
                    number_format($saved['new_salary'], 2),
                    $saved['reason']
                ),
                'compensation'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Salary adjustment saved successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save salary adjustment.',
            ], 500);
        }
    }
}
