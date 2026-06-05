<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccountChangeRequest;
use App\Services\ActivityLogger;
use App\Services\AdminPermissionService;
use App\Services\BankRequestService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BankRequestController extends Controller
{
    public function __construct(
        private AdminPermissionService $permissions,
        private BankRequestService $bankRequestService,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $list = collect();
        if (Schema::hasTable('bank_account_change_requests')) {
            $list = BankAccountChangeRequest::query()
                ->with('employee')
                ->where('status', 'Pending')
                ->orderByDesc('requested_at')
                ->get();
        }

        $adminId = (int) $this->hrSession->userId();

        return view('admin.bank-requests.index', [
            'list' => $list,
            'adminId' => $adminId,
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        if (! Schema::hasTable('bank_account_change_requests')) {
            return redirect()->route('admin.bank-requests.index')->with('error', 'Bank requests are not available.');
        }

        $item = BankAccountChangeRequest::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_bank_change', (int) $item->employee_id)) {
            return back()->with('error', 'You do not have permission to approve requests for this department.');
        }

        try {
            $this->bankRequestService->approve($item, $adminId, $adminName);
            $empName = $item->employee?->full_name ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Approve Bank Account Change',
                "Approved bank account change for {$empName}",
                'Bank Request'
            );

            return redirect()->route('admin.bank-requests.index')
                ->with('success', 'Bank account change approved and updated. Employee can refresh My Compensation to see their bank details.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed: '.$e->getMessage());
        }
    }

    public function decline(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:2000']);

        if (! Schema::hasTable('bank_account_change_requests')) {
            return redirect()->route('admin.bank-requests.index')->with('error', 'Bank requests are not available.');
        }

        $item = BankAccountChangeRequest::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_bank_change', (int) $item->employee_id)) {
            return back()->with('error', 'You do not have permission to decline requests for this department.');
        }

        try {
            $this->bankRequestService->decline($item, $adminId, $adminName, $request->input('rejection_reason'));
            $empName = $item->employee?->full_name ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Decline Bank Account Change',
                "Declined bank account change for {$empName}",
                'Bank Request'
            );

            return redirect()->route('admin.bank-requests.index')
                ->with('success', 'Bank account change declined.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
