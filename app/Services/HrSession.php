<?php

namespace App\Services;

use App\Models\UserLogin;

class HrSession
{
    public const USER_ID = 'user_id';

    public const ROLE = 'role';

    public const NAME = 'name';

    public const LAST_ACTIVITY = 'last_activity';

    public const ADMIN_MODULE = 'admin_module';

    public const EMPLOYEE_MODULE = 'employee_module';

    public const IS_DEFAULT_PASSWORD = 'is_default_password';

    public const LOGIN_CACHE_BUSTER = 'login_cache_buster';

    public function syncFromUser(UserLogin $user, bool $isDefaultPassword = false): void
    {
        session([
            self::USER_ID => $user->id,
            self::ROLE => $user->role ?? 'employee',
            self::NAME => $user->displayName(),
            self::LAST_ACTIVITY => time(),
            self::IS_DEFAULT_PASSWORD => $isDefaultPassword,
            self::LOGIN_CACHE_BUSTER => bin2hex(random_bytes(8)),
        ]);

        session()->forget([self::ADMIN_MODULE, self::EMPLOYEE_MODULE]);
        session()->regenerate();
    }

    public function userId(): ?int
    {
        $id = session(self::USER_ID);

        return $id !== null ? (int) $id : null;
    }

    public function role(): string
    {
        return strtolower((string) session(self::ROLE, ''));
    }

    public function isLoggedIn(): bool
    {
        return $this->userId() !== null && $this->userId() > 0;
    }

    public function touch(): void
    {
        session([self::LAST_ACTIVITY => time()]);
    }

    public function setAdminModule(?string $module): void
    {
        if ($module === null || $module === '') {
            session()->forget(self::ADMIN_MODULE);
        } else {
            session([self::ADMIN_MODULE => $module]);
        }
    }

    public function setEmployeeModule(?string $module): void
    {
        if ($module === null || $module === '') {
            session()->forget(self::EMPLOYEE_MODULE);
        } else {
            session([self::EMPLOYEE_MODULE => $module]);
        }
    }

    public function adminModule(): string
    {
        return (string) session(self::ADMIN_MODULE, '');
    }

    public function employeeModule(): string
    {
        return (string) session(self::EMPLOYEE_MODULE, '');
    }
}
