<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeeContext;
use App\Services\StaffProfileService;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(EmployeeContext $context, StaffProfileService $profile): View
    {
        $employee = $context->requireEmployee();
        $data = $profile->load($employee->id);

        return view('employee.profile.show', $data);
    }
}
