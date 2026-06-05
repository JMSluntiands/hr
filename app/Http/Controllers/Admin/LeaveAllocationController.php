<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LeaveAllocationController extends Controller
{
    /** Leave allocation was removed; approve requests via Leave Request instead. */
    public function index(): RedirectResponse
    {
        return redirect()
            ->route('admin.leave-requests.index')
            ->with('success', 'Leave allocation is no longer used. Review and approve leave requests here.');
    }
}
