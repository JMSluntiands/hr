<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountManagementService
{
    private ?string $userLoginIdColumn = null;

    public function userLoginIdColumn(): string
    {
        if ($this->userLoginIdColumn !== null) {
            return $this->userLoginIdColumn;
        }

        if (! Schema::hasTable('user_login')) {
            return 'id';
        }

        if (Schema::hasColumn('user_login', 'id')) {
            return 'id';
        }

        if (Schema::hasColumn('user_login', 'user_id')) {
            return 'user_id';
        }

        return 'id';
    }

    public function schemaMessage(): ?string
    {
        if (! Schema::hasTable('user_login')) {
            return 'The user_login table is missing in this database.';
        }
        if (! Schema::hasColumn('user_login', 'email')) {
            return 'The user_login.email column is missing in this database.';
        }
        $idCol = $this->userLoginIdColumn();
        if (! Schema::hasColumn('user_login', $idCol)) {
            return 'The user_login id column is missing (id/user_id).';
        }

        return null;
    }

    public function hasLastPasswordChangeColumn(): bool
    {
        return Schema::hasTable('user_login')
            && Schema::hasColumn('user_login', 'last_password_change');
    }

    /**
     * @return Collection<int, object>
     */
    public function listAccounts(): Collection
    {
        $accounts = collect();
        if ($this->schemaMessage() !== null) {
            return $accounts;
        }

        $idCol = $this->userLoginIdColumn();
        $hasLast = $this->hasLastPasswordChangeColumn();
        $hasRole = Schema::hasColumn('user_login', 'role');

        $select = ["{$idCol} as id", 'email'];
        if ($hasRole) {
            $select[] = 'role';
        }
        if ($hasLast) {
            $select[] = 'last_password_change';
        }

        $rows = DB::table('user_login')->select($select)->orderBy('email')->get();
        foreach ($rows as $row) {
            $accounts->push((object) [
                'id' => (int) $row->id,
                'email' => (string) $row->email,
                'role' => $hasRole ? (string) ($row->role ?? 'employee') : 'employee',
                'last_password_change' => $hasLast ? ($row->last_password_change ?? null) : null,
                'has_account' => true,
                'employee_id' => 0,
                'has_last_change_column' => $hasLast,
            ]);
        }

        if (
            Schema::hasTable('employees')
            && Schema::hasColumn('employees', 'email')
            && Schema::hasColumn('employees', 'id')
        ) {
            $missing = DB::table('employees as e')
                ->leftJoin('user_login as u', function ($join) use ($idCol) {
                    $join->on(DB::raw('u.email COLLATE utf8mb4_unicode_ci'), '=', DB::raw('e.email COLLATE utf8mb4_unicode_ci'));
                })
                ->whereNull("u.{$idCol}")
                ->whereNotNull('e.email')
                ->where('e.email', '!=', '')
                ->orderBy('e.email')
                ->get(['e.id as employee_id', 'e.email']);

            foreach ($missing as $row) {
                $accounts->push((object) [
                    'id' => 0,
                    'email' => (string) $row->email,
                    'role' => 'employee',
                    'last_password_change' => null,
                    'has_account' => false,
                    'employee_id' => (int) $row->employee_id,
                    'has_last_change_column' => $hasLast,
                ]);
            }
        }

        return $accounts;
    }

    public function generateRandomPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }

    /**
     * @return array{email: string, password: string}
     */
    public function createEmployeeAccount(int $employeeId): array
    {
        $employee = DB::table('employees')->where('id', $employeeId)->first(['id', 'full_name', 'email']);
        if (! $employee) {
            throw new \RuntimeException('Employee not found.');
        }

        $email = trim((string) ($employee->email ?? ''));
        if ($email === '') {
            throw new \RuntimeException('Employee email is missing.');
        }

        if (DB::table('user_login')->where('email', $email)->exists()) {
            throw new \RuntimeException('Account already exists for this employee.');
        }

        $plain = $this->generateRandomPassword(10);
        $id = DB::table('user_login')->insertGetId([
            'email' => $email,
            'password' => md5($plain),
            'role' => 'employee',
        ]);

        return ['id' => (int) $id, 'email' => $email, 'password' => $plain];
    }

    /**
     * @return array{email: string, password: string}
     */
    public function resetEmployeePassword(int $accountId): array
    {
        $idCol = $this->userLoginIdColumn();
        $account = DB::table('user_login')->where($idCol, $accountId)->first(['email', 'role']);
        if (! $account) {
            throw new \RuntimeException('Account not found.');
        }

        if (strtolower((string) ($account->role ?? '')) !== 'employee') {
            throw new \RuntimeException('Credentials email is only available for employee accounts.');
        }

        $email = trim((string) ($account->email ?? ''));
        if ($email === '') {
            throw new \RuntimeException('Employee email is missing.');
        }

        $plain = $this->generateRandomPassword(10);
        $update = ['password' => md5($plain)];
        if ($this->hasLastPasswordChangeColumn()) {
            $update['last_password_change'] = now();
        }

        DB::table('user_login')->where($idCol, $accountId)->update($update);

        return ['email' => $email, 'password' => $plain];
    }

    public function updateRole(int $accountId, string $role): void
    {
        $role = strtolower(trim($role));
        if (! in_array($role, ['admin', 'employee'], true)) {
            throw new \RuntimeException('Invalid role.');
        }

        $idCol = $this->userLoginIdColumn();
        $updated = DB::table('user_login')->where($idCol, $accountId)->update(['role' => $role]);
        if (! $updated) {
            throw new \RuntimeException('Failed to update role.');
        }
    }
}
