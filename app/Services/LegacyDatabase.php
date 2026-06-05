<?php

namespace App\Services;

class LegacyDatabase
{
    private static ?\mysqli $connection = null;

    public static function connection(): ?\mysqli
    {
        if (self::$connection !== null) {
            return self::$connection ?: null;
        }

        $conn = false;
        require base_path('database/db.php');
        self::$connection = ($conn instanceof \mysqli) ? $conn : false;

        return self::$connection ?: null;
    }

    public static function ensureStaffSchema(): void
    {
        $conn = self::connection();
        if (! $conn) {
            return;
        }

        $base = base_path('legacy/include');
        require_once $base.'/ensure_departments_performance_column.php';
        require_once $base.'/ensure_employment_types_table.php';
        require_once $base.'/ensure_employees_employment_type_id_column.php';
        require_once $base.'/ensure_employees_staff_columns.php';

        ensure_departments_performance_column($conn);
        ensure_employment_types_table($conn);
        ensure_employees_employment_type_id_column($conn);
        ensure_employees_staff_columns($conn);
    }
}
