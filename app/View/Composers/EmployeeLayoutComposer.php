<?php

namespace App\View\Composers;

use App\Services\EmployeeContext;
use App\Services\EmployeePerformanceNavService;
use App\Services\HrSession;
use Illuminate\View\View;

class EmployeeLayoutComposer
{
    public function __construct(
        private EmployeeContext $employeeContext,
        private EmployeePerformanceNavService $performanceNav,
    ) {}

    public function compose(View $view): void
    {
        $employee = $this->employeeContext->employee();
        $view->with([
            'employee' => $employee,
            'employeeName' => $employee?->full_name ?? session(HrSession::NAME, 'Employee'),
            'employeePhoto' => $employee?->profile_picture ?? null,
            'performanceReviewNavEnabled' => $this->performanceNav->showPerformanceSection(),
            'performanceFormReviewNav' => $this->performanceNav->showFormReviewInSidebar(),
        ]);
    }
}
