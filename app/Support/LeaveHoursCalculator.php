<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class LeaveHoursCalculator
{
    public const HOURS_PER_DAY = 8;

    /**
     * Compute leave from start until return (when the employee is back at work).
     *
     * @return array{total_hours: float, total_days: int, calendar_days: int, label: string}
     */
    public static function compute(
        string $startDate,
        string $startTime,
        string $endDate,
        string $endTime,
    ): array {
        $start = Carbon::parse($startDate.' '.$startTime, 'Asia/Manila');
        $return = Carbon::parse($endDate.' '.$endTime, 'Asia/Manila');

        if ($return->lte($start)) {
            throw new InvalidArgumentException('Return date & time must be after start date & time.');
        }

        $startDay = $start->copy()->startOfDay();
        $returnDay = $return->copy()->startOfDay();
        $calendarSpan = (int) $startDay->diffInDays($returnDay);
        $calendarDays = $calendarSpan + 1;

        if ($calendarSpan === 0) {
            $hours = round($start->diffInMinutes($return) / 60, 2);
            $hours = min((float) self::HOURS_PER_DAY, $hours);
        } else {
            // Each calendar day from start date up to (but not including) return date counts as one workday.
            $hours = round($calendarSpan * self::HOURS_PER_DAY, 2);
        }

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
