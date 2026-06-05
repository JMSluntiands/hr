<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrivacyPolicyService
{
    public function ensureColumn(): void
    {
        if (! Schema::hasTable('user_login')) {
            return;
        }

        if (! Schema::hasColumn('user_login', 'privacy_policy_accepted_at')) {
            $after = Schema::hasColumn('user_login', 'last_login') ? 'last_login' : 'role';
            DB::statement("ALTER TABLE user_login ADD COLUMN privacy_policy_accepted_at DATETIME NULL DEFAULT NULL AFTER {$after}");
        }
    }

    public function currentVersion(): string
    {
        return (string) config('hr.privacy_policy_version', '1');
    }

    public function hasAccepted(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $this->ensureColumn();

        $row = DB::table('user_login')
            ->where('id', $userId)
            ->first(['privacy_policy_accepted_at']);

        return $row && ! empty($row->privacy_policy_accepted_at);
    }

    public function accept(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->ensureColumn();

        DB::table('user_login')->where('id', $userId)->update([
            'privacy_policy_accepted_at' => now(),
        ]);
    }
}
