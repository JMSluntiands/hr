<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleSelectController extends Controller
{
    public function show(HrSession $hrSession): View
    {
        $hrSession->setEmployeeModule(null);

        return view('employee.module-select');
    }

    public function store(Request $request, HrSession $hrSession): RedirectResponse
    {
        $module = strtolower(trim((string) $request->input('module', '')));
        $allowed = ['profile', 'timekeeping'];

        if (! in_array($module, $allowed, true)) {
            return back()->withErrors(['module' => 'Invalid module selected.']);
        }

        if ($module === 'timekeeping') {
            $hrSession->setEmployeeModule('timekeeping');

            return redirect()->route('employee.timekeeping.building');
        }

        $hrSession->setEmployeeModule($module);

        return redirect()->route('home');
    }
}
