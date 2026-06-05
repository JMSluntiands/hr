<?php

namespace App\Services;

use App\Models\StaffPerformanceReview;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PerformanceReviewService
{
    public const RATING_COLUMNS = [
        'accuracy_rating',
        'cross_ref_rating',
        'comprehension_rating',
        'teamwork_support_rating',
        'initiative_learning_rating',
        'daily_output_rating',
        'task_management_rating',
        'communication_delays_rating',
    ];

    public function ensureTable(): void
    {
        if (Schema::hasTable('staff_performance_reviews')) {
            $this->migrateExtraCompetencies();

            return;
        }

        DB::statement("CREATE TABLE IF NOT EXISTS `staff_performance_reviews` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `employee_id` INT(11) NOT NULL,
            `review_date` DATE NOT NULL,
            `staff_name` VARCHAR(255) NOT NULL,
            `supervisor_name` VARCHAR(255) NOT NULL,
            `accuracy_rating` TINYINT NOT NULL,
            `accuracy_explanation` TEXT NOT NULL,
            `cross_ref_rating` TINYINT NOT NULL,
            `cross_ref_explanation` TEXT NOT NULL,
            `comprehension_rating` TINYINT NOT NULL,
            `comprehension_explanation` TEXT NOT NULL,
            `teamwork_support_rating` TINYINT NOT NULL,
            `teamwork_support_explanation` TEXT NOT NULL,
            `initiative_learning_rating` TINYINT NOT NULL,
            `initiative_learning_explanation` TEXT NOT NULL,
            `daily_output_rating` TINYINT NOT NULL,
            `daily_output_explanation` TEXT NOT NULL,
            `task_management_rating` TINYINT NOT NULL,
            `task_management_explanation` TEXT NOT NULL,
            `communication_delays_rating` TINYINT NOT NULL,
            `communication_delays_explanation` TEXT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_employee` (`employee_id`),
            KEY `idx_review_date` (`review_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->migrateExtraCompetencies();
    }

    private function migrateExtraCompetencies(): void
    {
        if (! Schema::hasTable('staff_performance_reviews')) {
            return;
        }

        $pairs = [
            'teamwork_support_rating' => 'TINYINT NULL',
            'teamwork_support_explanation' => 'TEXT NULL',
            'initiative_learning_rating' => 'TINYINT NULL',
            'initiative_learning_explanation' => 'TEXT NULL',
            'daily_output_rating' => 'TINYINT NULL',
            'daily_output_explanation' => 'TEXT NULL',
            'task_management_rating' => 'TINYINT NULL',
            'task_management_explanation' => 'TEXT NULL',
            'communication_delays_rating' => 'TINYINT NULL',
            'communication_delays_explanation' => 'TEXT NULL',
        ];

        foreach ($pairs as $column => $definition) {
            if (! Schema::hasColumn('staff_performance_reviews', $column)) {
                DB::statement("ALTER TABLE `staff_performance_reviews` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function listForAdmin(): Collection
    {
        $this->ensureTable();

        if (! Schema::hasTable('staff_performance_reviews')) {
            return collect();
        }

        return DB::table('staff_performance_reviews as r')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->select([
                'r.*',
                'e.full_name as employee_full_name',
                'e.department as employee_department',
                'e.employee_id as employee_code',
            ])
            ->orderByDesc('r.created_at')
            ->get();
    }

    public function ratingOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $n = (int) $value;

        return ($n >= 1 && $n <= 5) ? $n : null;
    }

    public function ratingsSummary(object $row): string
    {
        $parts = [];
        foreach (self::RATING_COLUMNS as $col) {
            $v = $this->ratingOrNull($row->{$col} ?? null);
            $parts[] = $v !== null ? (string) $v : '—';
        }

        return implode(' / ', $parts);
    }
}
