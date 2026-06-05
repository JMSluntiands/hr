<?php

namespace App\Services;

use App\Models\Department;

class EmployeePerformanceNavService
{
    public function __construct(private EmployeeContext $employeeContext) {}

    public function departmentPerformanceReviewEnabled(): bool
    {
        $employee = $this->employeeContext->employee();
        $departmentName = trim((string) ($employee?->department ?? ''));
        if ($departmentName === '') {
            return false;
        }

        $enabled = Department::query()
            ->where('name', $departmentName)
            ->value('additional_performance_review');

        return (bool) $enabled;
    }

    public function isPerformanceReviewSupervisor(): bool
    {
        $employee = $this->employeeContext->employee();

        return $employee && (int) ($employee->performance_review_supervisor ?? 0) === 1;
    }

    public function showPerformanceSection(): bool
    {
        return $this->departmentPerformanceReviewEnabled();
    }

    public function showFormReviewInSidebar(): bool
    {
        return $this->showPerformanceSection() && $this->isPerformanceReviewSupervisor();
    }
}
