<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\EmployeeContext;
use App\Support\LeaveHoursCalculator;
use App\Support\ManilaTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LeaveRequestController extends Controller
{
    public function store(Request $request, EmployeeContext $context): JsonResponse
    {
        $validated = $request->validate([
            'leave_type' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'required|string|max:2000',
        ]);

        $employee = $context->employee();
        if (! $employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee ID not found.']);
        }

        if ($validated['start_date'] < ManilaTime::todayYmd()) {
            return response()->json(['status' => 'error', 'message' => 'Start date cannot be in the past']);
        }

        try {
            $computed = LeaveHoursCalculator::compute(
                $validated['start_date'],
                $validated['start_time'],
                $validated['end_date'],
                $validated['end_time'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        $payload = [
            'employee_id' => $employee->id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $computed['total_days'],
            'days' => $computed['total_days'],
            'reason' => $validated['reason'],
            'status' => 'Pending',
            'created_at' => now(),
        ];

        if (Schema::hasColumn('leave_requests', 'start_time')) {
            $payload['start_time'] = $validated['start_time'].':00';
        }
        if (Schema::hasColumn('leave_requests', 'end_time')) {
            $payload['end_time'] = $validated['end_time'].':00';
        }
        if (Schema::hasColumn('leave_requests', 'total_hours')) {
            $payload['total_hours'] = $computed['total_hours'];
        }

        LeaveRequest::query()->create($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave request submitted ('.$computed['label'].').',
        ]);
    }
}
