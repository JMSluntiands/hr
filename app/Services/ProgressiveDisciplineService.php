<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProgressiveDisciplineService
{
    /** @var list<string> */
    public const DISCIPLINE_LEVELS = [
        'Verbal Warning',
        'Written Warning',
        'Final Warning',
        'Suspension',
        'Termination',
    ];

    /** @var list<string> */
    public const STATUSES = ['Active', 'Resolved', 'Escalated'];

    public function isTableReady(): bool
    {
        return Schema::hasTable('progressive_discipline_records');
    }

    public function ensureTable(): bool
    {
        if ($this->isTableReady()) {
            return true;
        }

        if (! Schema::hasTable('employees')) {
            return false;
        }

        DB::statement("CREATE TABLE IF NOT EXISTS `progressive_discipline_records` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `incident_date` date NOT NULL,
            `offense_type` varchar(120) NOT NULL,
            `discipline_level` enum('Verbal Warning','Written Warning','Final Warning','Suspension','Termination') NOT NULL,
            `description` text NOT NULL,
            `action_taken` text DEFAULT NULL,
            `status` enum('Active','Resolved','Escalated') NOT NULL DEFAULT 'Active',
            `issued_by` int(11) NOT NULL,
            `next_review_date` date DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_employee_id` (`employee_id`),
            KEY `idx_status` (`status`),
            KEY `idx_incident_date` (`incident_date`),
            CONSTRAINT `fk_progressive_discipline_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        return $this->isTableReady();
    }

    /**
     * @return Collection<int, object>
     */
    public function employeesForSelect(): Collection
    {
        if (! Schema::hasTable('employees')) {
            return collect();
        }

        return DB::table('employees')
            ->select('id', 'employee_id', 'full_name', 'department', 'position')
            ->orderBy('full_name')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function listRecords(): Collection
    {
        if (! $this->isTableReady()) {
            return collect();
        }

        return DB::table('progressive_discipline_records as pdr')
            ->leftJoin('employees as e', 'e.id', '=', 'pdr.employee_id')
            ->select([
                'pdr.id',
                'pdr.employee_id',
                'pdr.incident_date',
                'pdr.offense_type',
                'pdr.discipline_level',
                'pdr.description',
                'pdr.action_taken',
                'pdr.status',
                'pdr.next_review_date',
                'pdr.created_at',
                'e.employee_id as emp_code',
                'e.full_name',
                'e.department',
                'e.position',
            ])
            ->orderByDesc('pdr.created_at')
            ->limit(500)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, int $issuedBy): int
    {
        if (! $this->ensureTable()) {
            throw new \RuntimeException('Progressive discipline table is missing. Run setup first.');
        }

        $employeeId = (int) ($input['employee_id'] ?? 0);
        $incidentDate = trim((string) ($input['incident_date'] ?? ''));
        $offenseType = trim((string) ($input['offense_type'] ?? ''));
        $disciplineLevel = trim((string) ($input['discipline_level'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $actionTaken = trim((string) ($input['action_taken'] ?? ''));
        $nextReviewDate = trim((string) ($input['next_review_date'] ?? ''));

        if (
            $employeeId <= 0 || $incidentDate === '' || $offenseType === ''
            || $description === '' || ! in_array($disciplineLevel, self::DISCIPLINE_LEVELS, true)
        ) {
            throw new \InvalidArgumentException('Please complete all required fields.');
        }

        return (int) DB::table('progressive_discipline_records')->insertGetId([
            'employee_id' => $employeeId,
            'incident_date' => $incidentDate,
            'offense_type' => $offenseType,
            'discipline_level' => $disciplineLevel,
            'description' => $description,
            'action_taken' => $actionTaken !== '' ? $actionTaken : null,
            'issued_by' => $issuedBy,
            'next_review_date' => $nextReviewDate !== '' ? $nextReviewDate : null,
            'created_at' => now(),
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (! $this->isTableReady()) {
            return false;
        }

        if ($id <= 0 || ! in_array($status, self::STATUSES, true)) {
            return false;
        }

        return DB::table('progressive_discipline_records')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]) > 0;
    }
}
