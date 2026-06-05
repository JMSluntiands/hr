<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\UserLogin;
use App\Services\HrSession;

class EmployeeContext
{
    public function __construct(private HrSession $hrSession) {}

    public function user(): ?UserLogin
    {
        $id = $this->hrSession->userId();

        return $id ? UserLogin::query()->find($id) : null;
    }

    public function employee(): ?Employee
    {
        $user = $this->user();
        if (! $user?->email) {
            return null;
        }

        return Employee::query()
            ->whereRaw('TRIM(LOWER(email)) = ?', [strtolower(trim($user->email))])
            ->first();
    }

    public function requireEmployee(): Employee
    {
        $employee = $this->employee();
        if (! $employee) {
            abort(403, 'Your login is not linked to an employee record.');
        }

        return $employee;
    }
}
