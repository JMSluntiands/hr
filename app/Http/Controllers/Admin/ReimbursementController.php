<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reimbursement;
use App\Services\ActivityLogger;
use App\Services\AdminPermissionService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReimbursementController extends Controller
{
    public function __construct(
        private AdminPermissionService $permissions,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $list = Reimbursement::query()
            ->with('employee')
            ->where('status', 'Pending')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.reimbursements.index', compact('list'));
    }

    public function list(): View
    {
        $approved = Reimbursement::query()
            ->with('employee')
            ->where('status', 'Approved')
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->get();

        $forAttachment = $approved->filter(fn (Reimbursement $r) => empty($r->admin_receipt_path));
        $completed = $approved->filter(fn (Reimbursement $r) => ! empty($r->admin_receipt_path));

        return view('admin.reimbursements.list', [
            'forAttachment' => $forAttachment,
            'completed' => $completed,
        ]);
    }

    public function attachReceipt(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'admin_receipt' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120000',
        ]);

        $item = Reimbursement::findOrFail($id);
        if ($item->status !== 'Approved') {
            return redirect()->route('admin.reimbursements.list.index')
                ->with('error', 'Only approved reimbursements can receive an admin receipt.');
        }

        if (! empty($item->admin_receipt_path)) {
            return redirect()->route('admin.reimbursements.list.index')
                ->with('error', 'A reimbursement receipt is already attached.');
        }

        $file = $request->file('admin_receipt');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $safeName = 'admin_receipt_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(6)).'.'.$extension;

        $dir = base_path('uploads/reimbursements/admin-proof');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return redirect()->route('admin.reimbursements.list.index')
                ->with('error', 'Failed to create upload directory.');
        }

        $file->move($dir, $safeName);
        $dbPath = 'reimbursements/admin-proof/'.$safeName;

        $data = [
            'admin_receipt_path' => $dbPath,
        ];
        if (Schema::hasColumn('reimbursements', 'admin_receipt_original_name')) {
            $data['admin_receipt_original_name'] = $originalName;
        }
        if (Schema::hasColumn('reimbursements', 'reimbursed_at')) {
            $data['reimbursed_at'] = now();
        }

        $item->update($data);

        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');
        $this->activityLogger->log(
            $adminId,
            $adminName,
            'Attach Reimbursement Receipt',
            'Attached admin reimbursement receipt proof.',
            'Reimbursement'
        );

        return redirect()->route('admin.reimbursements.list.index')
            ->with('success', 'Reimbursement receipt attached successfully.');
    }

    public function report(Request $request): View
    {
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $rows = collect();
        $total = 0.0;

        if ($this->isValidDateYmd($from) && $this->isValidDateYmd($to)) {
            $rows = Reimbursement::query()
                ->with('employee')
                ->where('status', 'Approved')
                ->whereNotNull('admin_receipt_path')
                ->where('admin_receipt_path', '!=', '')
                ->when(
                    Schema::hasColumn('reimbursements', 'reimbursed_at'),
                    fn ($q) => $q->whereDate('reimbursed_at', '>=', $from)->whereDate('reimbursed_at', '<=', $to),
                    fn ($q) => $q->whereDate('approved_at', '>=', $from)->whereDate('approved_at', '<=', $to),
                )
                ->orderBy('reimbursed_at')
                ->get();

            $total = (float) $rows->sum('amount');
        }

        return view('admin.reimbursements.report', [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'total' => $total,
            'filtered' => $this->isValidDateYmd($from) && $this->isValidDateYmd($to),
        ]);
    }

    private function isValidDateYmd(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);

        return $dt instanceof \DateTime && $dt->format('Y-m-d') === $value;
    }

    public function approve(int $id): RedirectResponse
    {
        $item = Reimbursement::findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_reimbursement', (int) $item->employee_id)) {
            return back()->with('error', 'No permission for this department.');
        }

        $data = [
            'status' => 'Approved',
            'rejection_reason' => null,
            'approved_by' => $adminId,
            'approved_at' => now(),
        ];
        if (Schema::hasColumn('reimbursements', 'approved_by_name')) {
            $data['approved_by_name'] = $adminName;
        }

        $item->update($data);
        $this->activityLogger->log($adminId, $adminName, 'Approve Reimbursement', 'Approved reimbursement request.', 'Reimbursement');

        return back()->with('success', 'Reimbursement approved.');
    }

    public function decline(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:2000']);
        $item = Reimbursement::findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_reimbursement', (int) $item->employee_id)) {
            return back()->with('error', 'No permission for this department.');
        }

        $data = [
            'status' => 'Rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'approved_by' => $adminId,
            'approved_at' => now(),
        ];
        if (Schema::hasColumn('reimbursements', 'approved_by_name')) {
            $data['approved_by_name'] = $adminName;
        }

        $item->update($data);
        $this->activityLogger->log($adminId, $adminName, 'Decline Reimbursement', 'Declined reimbursement request.', 'Reimbursement');

        return back()->with('success', 'Reimbursement declined.');
    }
}
