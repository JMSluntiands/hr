<?php

namespace App\Support;

use Carbon\Carbon;

class ManilaTime
{
    public static function now(): Carbon
    {
        return Carbon::now('Asia/Manila');
    }

    public static function todayYmd(): string
    {
        return self::now()->format('Y-m-d');
    }
}
