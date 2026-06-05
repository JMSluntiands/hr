<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class LeaveHoursCalculator
{
    public const HOURS_PER_DAY = 8;

    /**
     * @return array{total_hours: float, total_days: int, calendar_days: int, label: string}
     */
    public static function compute(
        string $startDate,
        string $startTime,
        string $endDate,
        string $endTime,
    ): array {
        $start = Carbon::parse($startDate.' '.$startTime, 'Asia/Manila');
        $end = Carbon::parse($endDate.' '.$endTime, 'Asia/Manila');

        if ($end->lte($start)) {
            throw new InvalidArgumentException('Return date & time must be after start date & time.');
        }

        $minutes = $start->diffInMinutes($end);
        $hours = round($minutes / 60, 2);
        $calendarDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $dayEquivalents = max(1, (int) ceil($hours / self::HOURS_PER_DAY));

        return [
            'total_hours' => $hours,
            'total_days' => $dayEquivalents,
            'calendar_days' => $calendarDays,
            'label' => self::formatLabel($hours, $dayEquivalents, $calendarDays),
        ];
    }

    public static function formatLabel(float $hours, int $dayEquivalents, int $calendarDays): string
    {
        $h = floor($hours);
        $m = (int) round(($hours - $h) * 60);
        $timePart = $m > 0 ? "{$h} hr {$m} min" : "{$h} hr".($h === 1 ? '' : 's');
        $equiv = $dayEquivalents === 1 ? '1 day' : "{$dayEquivalents} days";

        if ($calendarDays > 1) {
            return "{$timePart} ({$equiv} at ".self::HOURS_PER_DAY." hrs/day · spans {$calendarDays} calendar days)";
        }

        return "{$timePart} ({$equiv} at ".self::HOURS_PER_DAY.' hrs/day)';
    }

    public static function formatHoursShort(?float $hours): string
    {
        if ($hours === null || $hours <= 0) {
            return '—';
        }
        $h = floor($hours);
        $m = (int) round(($hours - $h) * 60);

        return $m > 0 ? sprintf('%d:%02d', $h, $m) : ((int) $h).'h';
    }
}
