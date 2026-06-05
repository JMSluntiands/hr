<?php

namespace App\Services;

use App\Models\UserLogin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthService
{
    public function __construct(
        private HrSession $hrSession,
        private ActivityLogger $activityLogger,
    ) {}

    public function allowedDomain(): string
    {
        return (string) config('hr.allowed_email_domain', 'luntiands.com');
    }

    public function emailAllowed(string $email): bool
    {
        $domain = $this->allowedDomain();

        return str_ends_with(strtolower(trim($email)), '@'.$domain);
    }

    public function findByCredentials(string $email, string $password): ?UserLogin
    {
        return UserLogin::query()
            ->where('email', $email)
            ->where('password', md5($password))
            ->first();
    }

    public function findByEmail(string $email): ?UserLogin
    {
        return UserLogin::query()->where('email', $email)->first();
    }

    public function validateActiveEmployee(UserLogin $user): ?string
    {
        if (! $user->isEmployeeActive()) {
            return 'Your account is inactive. Please contact HR.';
        }

        return null;
    }

    public function detectDefaultPassword(string $password): bool
    {
        $defaults = ['password123', '123456789', 'password', 'admin123', '123456'];
        if (in_array(strtolower($password), array_map('strtolower', $defaults), true)) {
            return true;
        }

        return (bool) preg_match('/^[0-9]{6,}$/', $password) || strlen($password) <= 6;
    }

    public function login(UserLogin $user, string $plainPassword): void
    {
        $this->hrSession->syncFromUser($user, $this->detectDefaultPassword($plainPassword));
        $this->touchLastLogin($user->id);
        $this->activityLogger->log(
            (int) $user->id,
            $user->displayName(),
            'Login',
            'User logged in successfully',
        );
    }

    public function loginGoogle(UserLogin $user): void
    {
        $this->hrSession->syncFromUser($user, false);
        session([HrSession::IS_DEFAULT_PASSWORD => false]);
        $this->touchLastLogin($user->id);
    }

    private function touchLastLogin(int $userId): void
    {
        if (! Schema::hasColumn('user_login', 'last_login')) {
            return;
        }

        DB::table('user_login')->where('id', $userId)->update(['last_login' => now()]);
    }
}
