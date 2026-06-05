<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocumentUpload;
use App\Services\ActivityLogger;
use App\Services\AdminPermissionService;
use App\Services\DocumentUploadService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentUploadController extends Controller
{
    public function __construct(
        private AdminPermissionService $permissions,
        private DocumentUploadService $documentUploadService,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $list = collect();
        if (Schema::hasTable('employee_document_uploads')) {
            $list = EmployeeDocumentUpload::query()
                ->with('employee')
                ->where('status', 'Pending')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('admin.document-uploads.index', [
            'list' => $list,
            'adminId' => (int) $this->hrSession->userId(),
        ]);
    }

    public function file(int $id): BinaryFileResponse|RedirectResponse
    {
        if (! Schema::hasTable('employee_document_uploads')) {
            abort(404);
        }

        $upload = EmployeeDocumentUpload::findOrFail($id);
        $diskPath = $this->documentUploadService->resolveDiskPath($upload);

        if ($diskPath === null) {
            abort(404);
        }

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];
        $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        return response()->file($diskPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($diskPath).'"',
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        if (! Schema::hasTable('employee_document_uploads')) {
            return redirect()->route('admin.document-uploads.index')->with('error', 'Document uploads are not available.');
        }

        $upload = EmployeeDocumentUpload::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_document_upload', (int) $upload->employee_id)) {
            return back()->with('error', 'You do not have permission to approve uploads for this department.');
        }

        try {
            $this->documentUploadService->approve($upload, $adminId, $adminName);
            $empName = $upload->employee?->full_name ?? 'Unknown';
            $docType = $upload->document_type ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Approve Document File',
                "Approved {$docType} upload for {$empName}",
                'Document File'
            );

            return redirect()->route('admin.document-uploads.index')
                ->with('success', 'Document file approved and moved to document files.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to approve: '.$e->getMessage());
        }
    }

    public function decline(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:2000']);

        if (! Schema::hasTable('employee_document_uploads')) {
            return redirect()->route('admin.document-uploads.index')->with('error', 'Document uploads are not available.');
        }

        $upload = EmployeeDocumentUpload::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_document_upload', (int) $upload->employee_id)) {
            return back()->with('error', 'You do not have permission to decline uploads for this department.');
        }

        try {
            $this->documentUploadService->decline($upload, $adminId, $adminName, $request->input('rejection_reason'));
            $empName = $upload->employee?->full_name ?? 'Unknown';
            $docType = $upload->document_type ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Decline Document File',
                "Declined {$docType} upload for {$empName}",
                'Document File'
            );

            return redirect()->route('admin.document-uploads.index')
                ->with('success', 'Document file declined.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
