<?php
/**
 * Philippine Standard Time (Asia/Manila) helpers for the HR system.
 */

if (!function_exists('hr_init_timezone')) {
    function hr_init_timezone(): void
    {
        date_default_timezone_set('Asia/Manila');
    }
}

if (!function_exists('hr_mysql_set_timezone')) {
    function hr_mysql_set_timezone(mysqli $conn): void
    {
        if (!$conn->query("SET time_zone = '+08:00'")) {
            error_log('MySQL SET time_zone failed: ' . $conn->error);
        }
    }
}

if (!function_exists('hr_today_ymd')) {
    function hr_today_ymd(): string
    {
        hr_init_timezone();

        return date('Y-m-d');
    }
}

if (!function_exists('hr_now_datetime')) {
    function hr_now_datetime(): string
    {
        hr_init_timezone();

        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('hr_format_datetime_manila')) {
    /**
     * Format a MySQL DATETIME/TIMESTAMP string for display as Philippine civil time.
     */
    function hr_format_datetime_manila($mysqlDatetime, string $format = 'M d, Y h:i A'): string
    {
        if ($mysqlDatetime === null || trim((string) $mysqlDatetime) === '') {
            return '—';
        }
        try {
            $dt = new DateTimeImmutable(trim((string) $mysqlDatetime), new DateTimeZone('Asia/Manila'));

            return $dt->format($format);
        } catch (Throwable $e) {
            return '—';
        }
    }
}

if (!function_exists('hr_format_date_manila')) {
    /**
     * Format a MySQL DATE (or date portion) for display.
     */
    function hr_format_date_manila($mysqlDate, string $format = 'M d, Y'): string
    {
        if ($mysqlDate === null || trim((string) $mysqlDate) === '') {
            return '—';
        }
        $s = trim((string) $mysqlDate);
        if ($s === '') {
            return '—';
        }
        if (strlen($s) === 10 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            $s .= ' 00:00:00';
        }
        try {
            $dt = new DateTimeImmutable($s, new DateTimeZone('Asia/Manila'));

            return $dt->format($format);
        } catch (Throwable $e) {
            return '—';
        }
    }
}

if (!function_exists('hr_time_ago')) {
    function hr_time_ago($datetime): string
    {
        if ($datetime === null || trim((string) $datetime) === '') {
            return '';
        }
        hr_init_timezone();
        $timestamp = strtotime((string) $datetime);
        if ($timestamp === false) {
            return '';
        }
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff . ' sec' . ($diff !== 1 ? 's' : '') . ' ago';
        }
        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);

            return $mins . ' min' . ($mins !== 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return $hours . ' hour' . ($hours !== 1 ? 's' : '') . ' ago';
        }
        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            if ($days === 1) {
                return 'Yesterday';
            }

            return $days . ' days ago';
        }

        return hr_format_date_manila(date('Y-m-d H:i:s', $timestamp));
    }
}

hr_init_timezone();
