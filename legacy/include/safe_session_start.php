<?php

/**
 * Safe session start for legacy PHP when Laravel (or another bootstrap) already started the session.
 */
if (! function_exists('hr_safe_session_start')) {
    function hr_safe_session_start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        return session_start();
    }
}
