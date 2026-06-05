<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\EmployeeContext;
use Illuminate\View\View;

class TimeoffController extends Controller
{
    public function index(EmployeeContext $context): View
    {
        $employee = $context->requireEmployee();
        $year = (int) date('Y');

        $requests = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('employee.timeoff.index', compact('employee', 'requests', 'year'));
    }
}
