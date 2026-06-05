<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\AdminPermissionService;
use App\Services\HrSession;
use App\Services\LeaveRequestPresenter;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        private AdminPermissionService $permissions,
        private LeaveService $leaveService,
        private LeaveRequestPresenter $presenter,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $adminId = (int) ($this->hrSession->userId() ?? 0);

        $requests = LeaveRequest::query()
            ->with('employee')
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LeaveRequest $lr) => $this->enrichRow($lr, $adminId));

        return view('admin.leaves.requests', [
            'requests' => $requests,
            'pendingCount' => $requests->count(),
        ]);
    }

    public function history(): View
    {
        $adminId = (int) ($this->hrSession->userId() ?? 0);

        $requests = LeaveRequest::query()
            ->with('employee')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LeaveRequest $lr) => $this->enrichRow($lr, $adminId));

        return view('admin.leaves.history', [
            'requests' => $requests,
            'hasCancellationReason' => Schema::hasColumn('leave_requests', 'cancellation_reason'),
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $leave = LeaveRequest::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();

        if (! $this->permissions->canApprove($adminId, 'approve_leave', (int) $leave->employee_id)) {
            return back()->with('error', 'You do not have permission to approve requests for this department.');
        }

        if ($leave->status !== 'Pending') {
            return back()->with('error', 'This request is no longer pending.');
        }

        try {
            $this->leaveService->approve($leave, $adminId, (string) session(HrSession::NAME, 'Admin'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to approve: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.leave-requests.index')
            ->with('success', 'Leave request approved.');
    }

    public function decline(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:2000']);
        $leave = LeaveRequest::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();

        if (! $this->permissions->canApprove($adminId, 'approve_leave', (int) $leave->employee_id)) {
            return back()->with('error', 'You do not have permission to decline requests for this department.');
        }

        $this->leaveService->decline(
            $leave,
            $adminId,
            (string) session(HrSession::NAME, 'Admin'),
            $request->input('rejection_reason')
        );

        return redirect()
            ->route('admin.leave-requests.index')
            ->with('success', 'Leave request declined.');
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichRow(LeaveRequest $leave, int $adminId): array
    {
        $row = $this->presenter->toRow($leave);
        $row['can_approve'] = ($row['status'] === 'Pending')
            && $this->permissions->canApprove($adminId, 'approve_leave', (int) $leave->employee_id);

        return $row;
    }
}
