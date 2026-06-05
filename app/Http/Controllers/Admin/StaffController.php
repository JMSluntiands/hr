<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HrSession;
use App\Services\StaffOnboardingService;
use App\Services\StaffProfileService;
use App\Services\StaffUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffOnboardingService $onboarding,
        private readonly StaffProfileService $profile,
        private readonly StaffUpdateService $updateService,
        private readonly HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $query = Employee::query()->orderByDesc('created_at');
        if (! Schema::hasColumn('employees', 'created_at')) {
            $query = Employee::query()->orderByDesc('id');
        }

        $employees = $query->get();
        $departments = Employee::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        return view('admin.staff.index', compact('employees', 'departments'));
    }

    public function create(): View
    {
        $options = $this->onboarding->formOptions();

        return view('admin.staff.create', [
            'departmentOptions' => $options['departments'],
            'employmentTypeOptions' => $options['employmentTypes'],
            'nextEmployeeId' => $this->onboarding->previewEmployeeId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->onboarding->store(
            $request->except('_token'),
            $request->file('employee_signature'),
            (int) ($this->hrSession->userId() ?? 0),
            (string) session(HrSession::NAME, 'Admin'),
        );

        if (! $result['ok']) {
            return redirect()
                ->route('admin.staff.create')
                ->withInput()
                ->with('error', implode("\n", $result['errors'] ?? ['Could not add employee.']));
        }

        return redirect()
            ->route('admin.staff.create')
            ->with('success', $result['message']);
    }

    public function show(int $employee): View|RedirectResponse
    {
        $data = $this->profile->load($employee);
        if ($data === null) {
            return redirect()->route('admin.staff.index');
        }

        $data['staffDocumentAdded'] = (bool) session()->pull('staff_document_added', false);

        return view('admin.staff.show', $data);
    }

    public function edit(int $employee): View|RedirectResponse
    {
        $model = Employee::query()->find($employee);
        if (! $model) {
            return redirect()->route('admin.staff.index');
        }

        $options = $this->onboarding->formOptions();

        return view('admin.staff.edit', [
            'employee' => $model,
            'departmentOptions' => $options['departments'],
            'employmentTypeOptions' => $options['employmentTypes'],
            'employeeCompensation' => $this->updateService->loadCompensation($employee),
            'hasResignationOnFile' => trim((string) ($model->resignation_letter_path ?? '')) !== '',
        ]);
    }

    public function update(Request $request, int $employee): RedirectResponse
    {
        $result = $this->updateService->update(
            $employee,
            $request->except(['_token', '_method']),
            $request->file('resignation_letter'),
            (int) ($this->hrSession->userId() ?? 0),
            (string) session(HrSession::NAME, 'Admin'),
        );

        if (! $result['ok']) {
            return redirect()
                ->route('admin.staff.edit', $employee)
                ->withInput()
                ->with('error', implode("\n", $result['errors'] ?? ['Could not update employee.']));
        }

        return redirect()
            ->route('admin.staff.index')
            ->with('success', $result['message'])
            ->with('staff_updated', true);
    }
}
