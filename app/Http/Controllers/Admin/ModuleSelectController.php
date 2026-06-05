<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleSelectController extends Controller
{
    public function show(): View
    {
        return view('admin.module-select');
    }

    public function store(Request $request, HrSession $hrSession): RedirectResponse
    {
        $module = strtolower(trim((string) $request->input('module', '')));

        $allowed = ['hr', 'inventory', 'workforce', 'permission'];
        if (! in_array($module, $allowed, true)) {
            return back()->withErrors(['module' => 'Invalid module selected.']);
        }

        if ($module === 'workforce') {
            $hrSession->setAdminModule('workforce');

            return redirect()->route('admin.workforce.building');
        }

        $hrSession->setAdminModule($module);

        return redirect()->route('home');
    }
}
