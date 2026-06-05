<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveSummaryService
{
    /**
     * Approved leave days used per employee (no allocation totals).
     *
     * @return list<object>
     */
    public function forYear(?int $year = null): array
    {
        $year = $year ?? (int) date('Y');

        if (! Schema::hasTable('employees') || ! Schema::hasTable('leave_requests')) {
            return [];
        }

        $sql = "SELECT
                e.id,
                e.employee_id,
                e.full_name,
                e.department,
                COALESCE((
                    SELECT SUM(CASE
                        WHEN lr.start_date = lr.end_date THEN 1
                        ELSE COALESCE(lr.total_days, lr.days, DATEDIFF(lr.end_date, lr.start_date) + 1)
                    END)
                    FROM leave_requests lr
                    WHERE lr.employee_id = e.id
                    AND lr.leave_type = 'Sick Leave'
                    AND lr.status = 'Approved'
                    AND YEAR(lr.start_date) = ?
                ), 0) AS sl_used,
                COALESCE((
                    SELECT SUM(CASE
                        WHEN lr.start_date = lr.end_date THEN 1
                        ELSE COALESCE(lr.total_days, lr.days, DATEDIFF(lr.end_date, lr.start_date) + 1)
                    END)
                    FROM leave_requests lr
                    WHERE lr.employee_id = e.id
                    AND lr.leave_type = 'Vacation Leave'
                    AND lr.status = 'Approved'
                    AND YEAR(lr.start_date) = ?
                ), 0) AS vl_used
            FROM employees e
            WHERE e.status = 'Active'
            ORDER BY e.full_name ASC";

        $rows = DB::select($sql, [$year, $year]);

        return array_map(function ($row) {
            $row->sl_used = (int) $row->sl_used;
            $row->vl_used = (int) $row->vl_used;

            return $row;
        }, $rows);
    }
}
