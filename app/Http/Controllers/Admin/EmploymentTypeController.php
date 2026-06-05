<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\EmploymentTypeService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmploymentTypeController extends Controller
{
    public function __construct(
        private EmploymentTypeService $employmentTypes,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        return view('admin.employment-types.index', [
            'types' => $this->employmentTypes->list(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);

        try {
            $type = $this->employmentTypes->create($request->input('name'));
            $this->log('Create Employment Type', (int) $type->id, 'Created employment type: '.$type->name);

            return back()->with('success', 'Employment type added.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);

        try {
            $this->employmentTypes->update($id, $request->input('name'));
            $this->log('Update Employment Type', $id, "Updated employment type #{$id} to: ".$request->input('name'));

            return back()->with('success', 'Employment type updated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->employmentTypes->delete($id);
            $this->log('Delete Employment Type', $id, "Deleted employment type #{$id}");

            return back()->with('success', 'Employment type deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function log(string $action, int $entityId, string $description): void
    {
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');
        $this->activityLogger->log($adminId, $adminName, $action, $description, 'employment_types');
    }
}
