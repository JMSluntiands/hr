<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\IncidentReportService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IncidentReportAttachmentController extends Controller
{
    public function __construct(private IncidentReportService $incidentReports) {}

    public function show(int $id): BinaryFileResponse
    {
        if (! $this->incidentReports->isTableReady()) {
            abort(404);
        }

        $report = DB::table('incident_reports')->where('id', $id)->first();
        if (! $report) {
            abort(404);
        }

        $userId = (int) session('user_id', 0);
        $role = strtolower((string) session('role', ''));

        if (! $this->incidentReports->canAccessAttachment($report, $userId, $role, false)) {
            abort(403);
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
}
