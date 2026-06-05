<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Services\ActivityLogger;
use App\Services\AdminPermissionService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DocumentRequestController extends Controller
{
    public function __construct(
        private AdminPermissionService $permissions,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $list = DocumentRequest::query()
            ->with('employee')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.documents.index', compact('list'));
    }

    public function approve(int $id): RedirectResponse
    {
        $doc = DocumentRequest::findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_document_request', (int) $doc->employee_id)) {
            return back()->with('error', 'No permission for this department.');
        }

        $data = ['status' => 'Approved', 'approved_by' => $adminId, 'approved_at' => now()];
        if (Schema::hasColumn('document_requests', 'approved_by_name')) {
            $data['approved_by_name'] = $adminName;
        }
        $doc->update($data);
        $this->activityLogger->log($adminId, $adminName, 'Approve Document Request', 'Approved document request.', 'Document Request');

        return back()->with('success', 'Document request approved. Complete issuance in legacy tools if needed.');
    }

    public function decline(int $id): RedirectResponse
    {
        $doc = DocumentRequest::findOrFail($id);
        $adminId = (int) $this->hrSession->userId();

        if (! $this->permissions->canApprove($adminId, 'approve_document_request', (int) $doc->employee_id)) {
            return back()->with('error', 'No permission for this department.');
        }

        $doc->update(['status' => 'Rejected', 'approved_at' => now(), 'approved_by' => $adminId]);

        return back()->with('success', 'Document request declined.');
    }
}
