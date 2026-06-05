<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompensationService
{
    private const VALID_REASONS = ['Promotion', 'Annual Increase', 'Adjustment', 'Other'];

    public function ensureAdjustmentsTable(): void
    {
        if (Schema::hasTable('employee_salary_adjustments')) {
            return;
        }

        DB::statement("CREATE TABLE IF NOT EXISTS `employee_salary_adjustments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `employee_id` int(11) NOT NULL,
            `previous_salary` decimal(10,2) NOT NULL,
            `new_salary` decimal(10,2) NOT NULL,
            `reason` enum('Promotion','Annual Increase','Adjustment','Other') DEFAULT 'Adjustment',
            `approved_by` varchar(255) DEFAULT NULL,
            `date_approved` date NOT NULL,
            `notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_employee_id` (`employee_id`),
            KEY `idx_date_approved` (`date_approved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * @return Collection<int, object>
     */
    public function listAdjustments(): Collection
    {
        $this->ensureAdjustmentsTable();

        return DB::table('employee_salary_adjustments as esa')
            ->leftJoin('employees as e', 'esa.employee_id', '=', 'e.id')
            ->select([
                'esa.*',
                'e.full_name',
                'e.employee_id as employee_code',
            ])
            ->orderByDesc('esa.date_approved')
            ->orderByDesc('esa.created_at')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function activeEmployees(): Collection
    {
        if (! Schema::hasTable('employees')) {
            return collect();
        }

        return DB::table('employees')
            ->select('id', 'employee_id', 'full_name')
            ->where(function ($q) {
                $q->where('status', 'Active')->orWhereNull('status');
            })
            ->orderBy('full_name')
            ->get();
    }

    public function currentSalary(int $employeeId): string
    {
        $salary = null;

        if (Schema::hasTable('employee_compensation')) {
            $row = DB::table('employee_compensation')
                ->where('employee_id', $employeeId)
                ->value('basic_salary_monthly');
            if ($row !== null && (float) $row != 0.0) {
                $salary = (float) $row;
            }
        }

        if ($salary === null || $salary == 0.0) {
            $this->ensureAdjustmentsTable();
            $latest = DB::table('employee_salary_adjustments')
                ->where('employee_id', $employeeId)
                ->orderByDesc('date_approved')
                ->orderByDesc('created_at')
                ->value('new_salary');
            if ($latest !== null) {
                $salary = (float) $latest;
            }
        }

        return number_format($salary ?? 0.0, 2, '.', '');
    }

    /**
     * @return array{employee_id: int, previous_salary: float, new_salary: float, reason: string}
     */
    public function saveAdjustment(
        int $employeeId,
        float $previousSalary,
        float $newSalary,
        string $reason,
        string $dateApproved,
        string $approvedBy,
    ): array {
        $reason = trim($reason);
        if (! in_array($reason, self::VALID_REASONS, true)) {
            throw new \InvalidArgumentException('Invalid reason.');
        }

        $this->ensureAdjustmentsTable();

        DB::table('employee_salary_adjustments')->insert([
            'employee_id' => $employeeId,
            'previous_salary' => $previousSalary,
            'new_salary' => $newSalary,
            'reason' => $reason,
            'approved_by' => $approvedBy,
            'date_approved' => $dateApproved,
            'created_at' => now(),
        ]);

        if (Schema::hasTable('employee_compensation')) {
            $exists = DB::table('employee_compensation')->where('employee_id', $employeeId)->exists();
            if ($exists) {
                DB::table('employee_compensation')
                    ->where('employee_id', $employeeId)
                    ->update([
                        'basic_salary_monthly' => $newSalary,
                        'updated_at' => now(),
                    ]);
            }
        }

        return [
            'employee_id' => $employeeId,
            'previous_salary' => $previousSalary,
            'new_salary' => $newSalary,
            'reason' => $reason,
        ];
    }
}
