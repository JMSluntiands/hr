<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\HrSession;
use App\Services\ProgressiveDisciplineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressiveDisciplineController extends Controller
{
    public function __construct(
        private ProgressiveDisciplineService $discipline,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $tableReady = $this->discipline->ensureTable();

        return view('admin.progressive-discipline.index', [
            'tableReady' => $tableReady,
            'employees' => $this->discipline->employeesForSelect(),
            'records' => $tableReady ? $this->discipline->listRecords() : collect(),
            'disciplineLevels' => ProgressiveDisciplineService::DISCIPLINE_LEVELS,
            'statuses' => ProgressiveDisciplineService::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $adminId = (int) $this->hrSession->userId();
        if ($adminId <= 0) {
            return redirect()->route('login');
        }

        try {
            $recordId = $this->discipline->create($request->all(), $adminId);
            $level = trim((string) $request->input('discipline_level', ''));
            $employeeId = (int) $request->input('employee_id', 0);
            $adminName = (string) session(HrSession::NAME, 'Admin');
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Create Discipline Record',
                "Added {$level} for employee ID {$employeeId}",
                'progressive_discipline'
            );

            return $this->flash('Discipline record has been added.');
        } catch (\InvalidArgumentException $e) {
            return $this->flash($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->flash($e->getMessage());
        } catch (\Throwable) {
            return $this->flash('Failed to add discipline record.');
        }
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $status = trim((string) $request->input('status', ''));
        if (! $this->discipline->updateStatus($id, $status)) {
            return $this->flash('Invalid update request.');
        }

        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');
        $this->activityLogger->log(
            $adminId,
            $adminName,
            'Update Discipline Status',
            "Updated discipline record #{$id} status to {$status}",
            'progressive_discipline'
        );

        return $this->flash('Discipline status updated.');
    }

    private function flash(string $message): RedirectResponse
    {
        return redirect()->route('admin.progressive-discipline.index')
            ->with('progressive_discipline_msg', $message);
    }
}
