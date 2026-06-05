<?php

if (! function_exists('hr_performance_employee_url')) {
    /**
     * @param  string  $legacyFile  e.g. performance.php
     * @param  string  $laravelPath  e.g. performance/form-review
     */
    function hr_performance_employee_url(string $legacyFile, string $laravelPath, string $query = ''): string
    {
        $embedded = defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED;
        $base = defined('HR_APP_URL') ? rtrim(HR_APP_URL, '/') : '';

        if ($embedded && $base !== '') {
            $url = $base.'/employee/'.ltrim($laravelPath, '/');
        } else {
            $url = $legacyFile;
        }

        if ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').ltrim($query, '?&');
        }

        return $url;
    }
}
