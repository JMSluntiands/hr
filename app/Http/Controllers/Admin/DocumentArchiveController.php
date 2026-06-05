<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocumentUpload;
use App\Services\ActivityLogger;
use App\Services\AdminPermissionService;
use App\Services\DocumentArchiveService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentArchiveController extends Controller
{
    public function __construct(
        private DocumentArchiveService $documentArchiveService,
        private AdminPermissionService $permissions,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        return view('admin.document-archive.index', [
            'schemaOk' => $this->documentArchiveService->schemaReady(),
            'pendingRemovals' => $this->documentArchiveService->pendingRemovals(),
            'archivedList' => $this->documentArchiveService->archivedList(),
            'adminId' => (int) $this->hrSession->userId(),
        ]);
    }

    public function file(int $id): BinaryFileResponse
    {
        $diskPath = $this->documentArchiveService->resolveArchivePath($id);
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
        if (! $this->documentArchiveService->schemaReady()) {
            return redirect()->route('admin.document-archive.index')
                ->with('error', 'Archive tables are not set up. Run database/setup_document_deletion_archive.php first.');
        }

        $upload = EmployeeDocumentUpload::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_document_removal', (int) $upload->employee_id)) {
            return back()->with('error', 'You do not have permission for this department.');
        }

        try {
            $this->documentArchiveService->approveRemoval($upload, $adminId, $adminName);
            $empName = $upload->employee?->full_name ?? 'Unknown';
            $docType = $upload->document_type ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Approve Document Removal',
                "Archived {$docType} for {$empName} after approved removal",
                'Document Archive'
            );

            return redirect()->route('admin.document-archive.index')
                ->with('success', 'Removal approved. File is in Document Archive and removed from the employee profile.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed: '.$e->getMessage());
        }
    }

    public function reject(int $id): RedirectResponse
    {
        if (! $this->documentArchiveService->schemaReady()) {
            return redirect()->route('admin.document-archive.index')
                ->with('error', 'Archive tables are not set up.');
        }

        $upload = EmployeeDocumentUpload::with('employee')->findOrFail($id);
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');

        if (! $this->permissions->canApprove($adminId, 'approve_document_removal', (int) $upload->employee_id)) {
            return back()->with('error', 'You do not have permission for this department.');
        }

        try {
            $this->documentArchiveService->rejectRemoval($upload);
            $empName = $upload->employee?->full_name ?? 'Unknown';
            $docType = $upload->document_type ?? 'Unknown';
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Reject Document Removal',
                "Declined removal for {$docType} — {$empName} (document stays active)",
                'Document'
            );

            return redirect()->route('admin.document-archive.index')
                ->with('success', 'Removal request declined. The employee keeps access to the document.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
