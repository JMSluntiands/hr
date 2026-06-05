<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

class HrDateTime
{
    public static function formatDateTime(mixed $value, string $format = 'M d, Y h:i A'): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return (new DateTimeImmutable(trim((string) $value), new DateTimeZone('Asia/Manila')))->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    public static function formatDate(mixed $value, string $format = 'M d, Y'): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return (new DateTimeImmutable(trim((string) $value), new DateTimeZone('Asia/Manila')))->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }
}
