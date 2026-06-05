<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\DepartmentService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentService $departments,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => $this->departments->list(),
            'hasPerfReviewCol' => $this->departments->hasPerformanceReviewColumn(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);

        try {
            $dept = $this->departments->create(
                $request->input('name'),
                $request->boolean('additional_performance_review')
            );
            $this->log('Create Department', (int) $dept->id, 'Created department: '.$dept->name);

            return back()->with('success', 'Department added.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);

        try {
            $this->departments->update(
                $id,
                $request->input('name'),
                $request->boolean('additional_performance_review')
            );
            $this->log('Update Department', $id, "Updated department #{$id} to: ".$request->input('name'));

            return back()->with('success', 'Department updated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->departments->delete($id);
            $this->log('Delete Department', $id, "Deleted department #{$id}");

            return back()->with('success', 'Department deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function log(string $action, int $entityId, string $description): void
    {
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');
        $this->activityLogger->log($adminId, $adminName, $action, $description, 'departments');
    }
}
