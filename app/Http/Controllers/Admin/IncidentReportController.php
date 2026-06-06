<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\HrSession;
use App\Services\IncidentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IncidentReportController extends Controller
{
    private const LIST_ROUTE = 'admin.incident-reports.index';

    private const SUBMITTED_ROUTE = 'admin.incident-reports.submitted';

    public function __construct(
        private IncidentReportService $incidentReports,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(Request $request): View
    {
        $typeFilter = trim((string) $request->query('incident_type', ''));
        if ($typeFilter !== '' && ! in_array($typeFilter, $this->incidentReports->allowedTypes(), true)) {
            $typeFilter = '';
        }

        return view('admin.incident-reports.index', [
            'tableReady' => $this->incidentReports->isTableReady(),
            'pendingCount' => $this->incidentReports->countPending(),
            'reports' => $this->incidentReports->listApproved([
                'date_from' => trim((string) $request->query('date_from', '')),
                'date_to' => trim((string) $request->query('date_to', '')),
                'employee' => trim((string) $request->query('employee', '')),
                'incident_type' => $typeFilter,
            ]),
            'dateFrom' => trim((string) $request->query('date_from', '')),
            'dateTo' => trim((string) $request->query('date_to', '')),
            'employeeQ' => trim((string) $request->query('employee', '')),
            'typeFilter' => $typeFilter,
            'allowedTypes' => $this->incidentReports->allowedTypes(),
            'editUrlBase' => url('/admin/incident-report-edit.php'),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        if ($id <= 0) {
            return $this->flashToList('Invalid delete request.');
        }

        if (! $this->incidentReports->isTableReady()) {
            return $this->flashToList('Could not delete report.');
        }

        $adminId = (int) $this->hrSession->userId();
        if ($adminId <= 0) {
            return redirect()->route('login');
        }

        if ($this->incidentReports->deleteReport($id)) {
            $adminName = (string) session(HrSession::NAME, 'Admin');
            $this->activityLogger->log(
                $adminId,
                $adminName,
                'Delete Incident Report',
                "Deleted incident report #$id",
                'incident_reports'
            );

            return $this->flashToList('Report deleted.');
        }

        return $this->flashToList('Report not found.');
    }

    public function submitted(Request $request): View
    {
        $statusFilter = trim((string) $request->query('status', 'Pending'));
        if (! in_array($statusFilter, $this->incidentReports->allowedReviewStatuses(), true)) {
            $statusFilter = 'Pending';
        }

        $typeFilter = trim((string) $request->query('incident_type', ''));
        if ($typeFilter !== '' && ! in_array($typeFilter, $this->incidentReports->allowedTypes(), true)) {
            $typeFilter = '';
        }

        return view('admin.incident-reports.submitted', [
            'tableReady' => $this->incidentReports->isTableReady(),
            'reports' => $this->incidentReports->listForReview([
                'status' => $statusFilter,
                'employee' => trim((string) $request->query('employee', '')),
                'incident_type' => $typeFilter,
            ]),
            'statusFilter' => $statusFilter,
            'employeeQ' => trim((string) $request->query('employee', '')),
            'typeFilter' => $typeFilter,
            'allowedStatuses' => $this->incidentReports->allowedReviewStatuses(),
            'allowedTypes' => $this->incidentReports->allowedTypes(),
            'listUrl' => route(self::LIST_ROUTE),
            'editUrlBase' => url('/admin/incident-report-edit.php'),
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        return $this->review($id, 'Approved', 'Approve Incident Report');
    }

    public function decline(int $id): RedirectResponse
    {
        return $this->review($id, 'Declined', 'Decline Incident Report');
    }

    public function create(): View
    {
        return view('admin.incident-reports.create', [
            'tableReady' => $this->incidentReports->isTableReady(),
            'record' => [
                'report_date' => now()->format('Y-m-d'),
                'report_time' => now()->format('H:i'),
            ],
            'mode' => 'create',
            'submitLabel' => 'Save report',
            'formAction' => route('admin.incident-reports.store'),
            'cancelUrl' => route(self::LIST_ROUTE),
            'listUrl' => route(self::LIST_ROUTE),
            'typeDescriptions' => $this->incidentReports->typeDescriptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->incidentReports->isTableReady()) {
            return $this->flashToList('Incident reports table is not ready. Contact HR or run database setup.');
        }

        $data = $this->incidentReports->normalizeFormInput($request->all());
        if (! $this->incidentReports->validateRequired($data)) {
            return $this->flashToList('Please complete all required fields.');
        }

        $userId = (int) $this->hrSession->userId();
        if ($userId <= 0) {
            return redirect()->route('login');
        }

        try {
            $reportId = $this->incidentReports->createAsAdmin(
                $userId,
                $data,
                $request->file('attachment')
            );

            $adminName = (string) session(HrSession::NAME, 'Admin');
            $this->activityLogger->log(
                $userId,
                $adminName,
                'Create Incident Report',
                "Created incident report by admin user #$userId",
                'incident_reports'
            );

            return $this->flashToList('Incident report saved.');
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Attachment') || str_contains($msg, 'file type') || str_contains($msg, '5MB')) {
                $msg = 'Attachment invalid or upload failed.';
            }

            return $this->flashToList($msg);
        } catch (\Throwable) {
            return $this->flashToList('Could not save report.');
        }
    }

    private function review(int $id, string $status, string $logAction): RedirectResponse
    {
        if ($id <= 0) {
            return $this->flashToSubmitted('Invalid review request.');
        }

        if (! $this->incidentReports->isTableReady()) {
            return $this->flashToSubmitted('Database error.');
        }

        $adminId = (int) $this->hrSession->userId();
        if ($adminId <= 0) {
            return redirect()->route('login');
        }

        if ($this->incidentReports->updateReviewStatus($id, $status, $adminId)) {
            $adminName = (string) session(HrSession::NAME, 'Admin');
            $this->activityLogger->log(
                $adminId,
                $adminName,
                $logAction,
                "Set incident report #$id status to $status",
                'incident_reports'
            );

            return $this->flashToSubmitted('Incident status updated to '.$status.'.');
        }

        return $this->flashToSubmitted('Incident not found or already updated.');
    }

    public function attachment(int $id): BinaryFileResponse|RedirectResponse
    {
        if (! $this->incidentReports->isTableReady()) {
            abort(404);
        }

        $report = \Illuminate\Support\Facades\DB::table('incident_reports')->where('id', $id)->first();
        if (! $report) {
            abort(404);
        }

        $full = $this->incidentReports->resolveAttachmentPath($report->attachment_path ?? null);
        if ($full === null) {
            abort(404);
        }

        $mime = $this->incidentReports->attachmentMime($full);
        $filename = basename($full);

        return response()->file($full, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
        ]);
    }

    private function flashToSubmitted(string $message): RedirectResponse
    {
        return redirect()->route(self::SUBMITTED_ROUTE)->with('incident_report_flash', $message);
    }

    private function flashToList(string $message): RedirectResponse
    {
        return redirect()->route(self::LIST_ROUTE)->with('incident_report_flash', $message);
    }
}
