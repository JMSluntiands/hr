<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\UserLogin;
use App\Services\HrSession;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(HrSession $hrSession): View
    {
        $user = UserLogin::query()->find($hrSession->userId());
        $employee = null;
        $unlinked = true;

        if ($user) {
            $employee = Employee::query()->where('email', $user->email)->first();
            $unlinked = $employee === null;
        }

        return view('employee.dashboard', [
            'user' => $user,
            'employee' => $employee,
            'unlinked' => $unlinked,
            'isDefaultPassword' => (bool) session(HrSession::IS_DEFAULT_PASSWORD),
        ]);
    }
}
