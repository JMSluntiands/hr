<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IncidentReportService
{
    public function isTableReady(): bool
    {
        return $this->ensureTable();
    }

    public function ensureTable(): bool
    {
        if (! Schema::hasTable('incident_reports')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `incident_reports` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `submitted_by_user_id` int(11) NOT NULL,
                `company_name` varchar(255) NOT NULL DEFAULT '',
                `employee_name` varchar(255) NOT NULL DEFAULT '',
                `location_area` varchar(255) NOT NULL DEFAULT '',
                `incident_date` date NOT NULL,
                `incident_time` varchar(32) NOT NULL DEFAULT '',
                `incident_type` varchar(160) NOT NULL,
                `incident_details` text NOT NULL,
                `witness_name` varchar(255) NOT NULL DEFAULT '',
                `anyone_injured` enum('No','Yes') NOT NULL DEFAULT 'No',
                `injury_types` varchar(500) DEFAULT NULL,
                `injury_details` text,
                `report_date` date NOT NULL,
                `report_time` varchar(32) NOT NULL DEFAULT '',
                `action_taken` text,
                `attachment_path` varchar(500) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_submitter` (`submitted_by_user_id`),
                KEY `idx_incident_date` (`incident_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        return $this->ensureApprovalColumns();
    }

    private function ensureApprovalColumns(): bool
    {
        if (! Schema::hasTable('incident_reports')) {
            return false;
        }

        if (! Schema::hasColumn('incident_reports', 'review_status')) {
            DB::statement("ALTER TABLE `incident_reports` ADD COLUMN `review_status` ENUM('Pending','Approved','Declined') NOT NULL DEFAULT 'Pending' AFTER `attachment_path`");
        }
        if (! Schema::hasColumn('incident_reports', 'reviewed_by_user_id')) {
            DB::statement('ALTER TABLE `incident_reports` ADD COLUMN `reviewed_by_user_id` INT(11) DEFAULT NULL AFTER `review_status`');
        }
        if (! Schema::hasColumn('incident_reports', 'reviewed_at')) {
            DB::statement('ALTER TABLE `incident_reports` ADD COLUMN `reviewed_at` DATETIME DEFAULT NULL AFTER `reviewed_by_user_id`');
        }

        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function typeDescriptions(): array
    {
        return [
            'Safety & Health' => [
                'Physical injury or accident (on-site)',
                'Ergonomic or home office-related injury (remote)',
                'Mental health or wellbeing concern',
            ],
            'Near Miss' => [
                'An event that could have resulted in harm but did not',
            ],
            'Property & Equipment Damage' => [
                'Damage to office or company-issued equipment',
                'Damage to personal equipment used for work',
                'Power or utility disruption affecting work',
            ],
            'Security Breach' => [
                'Unauthorized physical access to office premises',
                'Unauthorized access to home workspace or work materials',
            ],
            'Confidentiality / Data Breach' => [
                'Improper sharing of sensitive or confidential information',
                'Data exposed via unsecured network or public environment (remote)',
                'Accidental disclosure through messaging, email, or screen sharing',
            ],
            'IT / System Failure' => [
                'Network or internet outage',
                'Hardware or software malfunction',
                'Unauthorized VPN, remote access, or cloud platform failure',
            ],
            'Misconduct / Policy Violation' => [
                'Breach of workplace code of conduct',
                'Non-compliance with remote work policy',
                'Unauthorized use of company data, tools, or systems',
            ],
            'Workplace Harassment / Bullying' => [
                'Occurring in person, via messaging, email, or video calls',
            ],
            'Communication or Coordination' => [
                'Miscommunication leading to errors or missed deadlines',
                'Failure to escalate or report critical information',
            ],
            'Environmental Incident' => [
                'Unsafe working conditions on-site or at a remote location',
            ],
            'Others' => [
                "Any incident that doesn't fall under the categories above",
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedTypes(): array
    {
        return array_keys($this->typeDescriptions());
    }

    /**
     * @return list<string>
     */
    public function allowedReviewStatuses(): array
    {
        return ['Pending', 'Approved', 'Declined'];
    }

    public function reviewStatusSql(string $alias = 'ir'): string
    {
        return "COALESCE(NULLIF(TRIM({$alias}.review_status), ''), 'Pending')";
    }

    public function countPending(): int
    {
        if (! $this->isTableReady() || ! Schema::hasColumn('incident_reports', 'review_status')) {
            return 0;
        }

        return (int) DB::table('incident_reports')
            ->whereRaw($this->reviewStatusSql('incident_reports')." = 'Pending'")
            ->count();
    }

    /**
     * @param  array{status?: string, employee?: string, incident_type?: string}  $filters
     * @return Collection<int, object>
     */
    public function listForReview(array $filters): Collection
    {
        if (! $this->isTableReady()) {
            return collect();
        }

        $statusFilter = trim((string) ($filters['status'] ?? 'Pending'));
        if (! in_array($statusFilter, $this->allowedReviewStatuses(), true)) {
            $statusFilter = 'Pending';
        }

        $employeeQ = trim((string) ($filters['employee'] ?? ''));
        $typeFilter = trim((string) ($filters['incident_type'] ?? ''));
        if ($typeFilter !== '' && ! in_array($typeFilter, $this->allowedTypes(), true)) {
            $typeFilter = '';
        }

        $statusExpr = $this->reviewStatusSql('ir');

        $query = DB::table('incident_reports as ir')
            ->leftJoin('user_login as ul', 'ul.id', '=', 'ir.submitted_by_user_id')
            ->leftJoin('employees as e', function ($join) {
                $join->whereRaw('e.email COLLATE utf8mb4_unicode_ci = ul.email COLLATE utf8mb4_unicode_ci');
            })
            ->select([
                'ir.*',
                DB::raw("{$statusExpr} as review_status_display"),
                DB::raw("COALESCE(e.full_name, ul.email, CONCAT('User #', ir.submitted_by_user_id)) as submitter_display"),
            ])
            ->whereRaw("{$statusExpr} = ?", [$statusFilter]);

        if ($employeeQ !== '') {
            $query->where('ir.employee_name', 'like', '%'.$employeeQ.'%');
        }
        if ($typeFilter !== '') {
            $query->where('ir.incident_type', $typeFilter);
        }

        return $query->orderByDesc('ir.created_at')->limit(500)->get();
    }

    /**
     * @param  array{date_from?: string, date_to?: string, employee?: string, incident_type?: string}  $filters
     * @return Collection<int, object>
     */
    public function listApproved(array $filters): Collection
    {
        if (! $this->isTableReady()) {
            return collect();
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $employeeQ = trim((string) ($filters['employee'] ?? ''));
        $typeFilter = trim((string) ($filters['incident_type'] ?? ''));
        if ($typeFilter !== '' && ! in_array($typeFilter, $this->allowedTypes(), true)) {
            $typeFilter = '';
        }

        $query = DB::table('incident_reports as ir')
            ->leftJoin('user_login as ul', 'ul.id', '=', 'ir.submitted_by_user_id')
            ->leftJoin('employees as e', function ($join) {
                $join->whereRaw('e.email COLLATE utf8mb4_unicode_ci = ul.email COLLATE utf8mb4_unicode_ci');
            })
            ->select([
                'ir.*',
                DB::raw("COALESCE(e.full_name, ul.email, CONCAT('User #', ir.submitted_by_user_id)) as submitter_display"),
            ])
            ->where('ir.review_status', 'Approved');

        if ($dateFrom !== '') {
            $query->where('ir.incident_date', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->where('ir.incident_date', '<=', $dateTo);
        }
        if ($employeeQ !== '') {
            $query->where('ir.employee_name', 'like', '%'.$employeeQ.'%');
        }
        if ($typeFilter !== '') {
            $query->where('ir.incident_type', $typeFilter);
        }

        return $query->orderByDesc('ir.created_at')->limit(500)->get();
    }

    public function deleteReport(int $reportId): bool
    {
        if (! $this->isTableReady()) {
            return false;
        }

        $row = DB::table('incident_reports')->where('id', $reportId)->first();
        if (! $row) {
            return false;
        }

        if (DB::table('incident_reports')->where('id', $reportId)->delete() === 0) {
            return false;
        }

        $path = (string) ($row->attachment_path ?? '');
        if ($path !== '' && str_starts_with($path, 'uploads/incident_reports/')) {
            $full = base_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }

        return true;
    }

    public function updateReviewStatus(int $reportId, string $status, int $adminUserId): bool
    {
        if (! in_array($status, ['Approved', 'Declined'], true)) {
            return false;
        }

        $this->ensureTable();

        return DB::table('incident_reports')
            ->where('id', $reportId)
            ->update([
                'review_status' => $status,
                'reviewed_by_user_id' => $adminUserId,
                'reviewed_at' => now(),
            ]) > 0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAsAdmin(int $userId, array $data, ?UploadedFile $attachment = null): int
    {
        $this->ensureTable();

        $attachmentPath = $this->storeUpload($userId, $attachment);

        $id = (int) DB::table('incident_reports')->insertGetId([
            'submitted_by_user_id' => $userId,
            'company_name' => $data['company_name'],
            'employee_name' => $data['employee_name'],
            'location_area' => $data['location_area'],
            'incident_date' => $data['incident_date'],
            'incident_time' => $data['incident_time'],
            'incident_type' => $data['incident_type'],
            'incident_details' => $data['incident_details'],
            'witness_name' => $data['witness_name'],
            'anyone_injured' => $data['anyone_injured'],
            'injury_types' => $data['injury_types'],
            'injury_details' => $data['injury_details'],
            'report_date' => $data['report_date'],
            'report_time' => $data['report_time'],
            'action_taken' => $data['action_taken'],
            'attachment_path' => $attachmentPath,
            'review_status' => 'Approved',
            'reviewed_by_user_id' => $userId,
            'reviewed_at' => now(),
            'created_at' => now(),
        ]);

        return $id;
    }

    /**
     * @throws \RuntimeException
     */
    public function storeUpload(int $userId, ?UploadedFile $file): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \RuntimeException('Attachment must be 5MB or smaller.');
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'], true)) {
            throw new \RuntimeException('Invalid file type.');
        }

        $dir = base_path('uploads/incident_reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $basename = 'ir_'.$userId.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($dir, $basename);

        return 'uploads/incident_reports/'.$basename;
    }

    public function resolveAttachmentPath(?string $relativePath): ?string
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '' || ! str_starts_with($relativePath, 'uploads/incident_reports/')) {
            return null;
        }

        $candidates = [
            base_path($relativePath),
            base_path('legacy/'.$relativePath),
        ];

        foreach ($candidates as $full) {
            if (is_file($full)) {
                return $full;
            }
        }

        return null;
    }

    public function attachmentMime(string $absolutePath): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    public function canAccessAttachment(object $report, int $userId, string $role, bool $adminContext): bool
    {
        if ($adminContext && strtolower($role) === 'admin') {
            return true;
        }

        return (int) ($report->submitted_by_user_id ?? 0) === $userId;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeFormInput(array $input): array
    {
        $anyoneInjured = trim((string) ($input['anyone_injured'] ?? 'No')) === 'Yes' ? 'Yes' : 'No';
        $injuryTypes = null;
        $injuryDetails = null;
        if ($anyoneInjured === 'Yes') {
            $injuryTypesRaw = trim((string) ($input['injury_types'] ?? ''));
            $injuryDetailsRaw = trim((string) ($input['injury_details'] ?? ''));
            $injuryTypes = $injuryTypesRaw !== '' ? $injuryTypesRaw : null;
            $injuryDetails = $injuryDetailsRaw !== '' ? $injuryDetailsRaw : null;
        }

        $actionTaken = trim((string) ($input['action_taken'] ?? ''));

        return [
            'company_name' => trim((string) ($input['company_name'] ?? '')),
            'employee_name' => trim((string) ($input['employee_name'] ?? '')),
            'location_area' => trim((string) ($input['location_area'] ?? '')),
            'incident_date' => trim((string) ($input['incident_date'] ?? '')),
            'incident_time' => trim((string) ($input['incident_time'] ?? '')),
            'incident_type' => trim((string) ($input['incident_type'] ?? '')),
            'incident_details' => trim((string) ($input['incident_details'] ?? '')),
            'witness_name' => trim((string) ($input['witness_name'] ?? '')) ?: '',
            'anyone_injured' => $anyoneInjured,
            'injury_types' => $injuryTypes,
            'injury_details' => $injuryDetails,
            'report_date' => trim((string) ($input['report_date'] ?? '')),
            'report_time' => trim((string) ($input['report_time'] ?? '')),
            'action_taken' => $actionTaken !== '' ? $actionTaken : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateRequired(array $data): bool
    {
        if (
            $data['company_name'] === '' || $data['employee_name'] === '' || $data['location_area'] === ''
            || $data['incident_date'] === '' || $data['incident_time'] === '' || $data['incident_details'] === ''
            || $data['report_date'] === '' || $data['report_time'] === ''
        ) {
            return false;
        }

        return in_array($data['incident_type'], $this->allowedTypes(), true);
    }
}
