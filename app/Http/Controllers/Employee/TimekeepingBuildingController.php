<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TimekeepingBuildingController extends Controller
{
    public function __invoke(): View
    {
        return view('employee.timekeeping.building');
    }
}
