<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserLogin extends Model
{
    protected $table = 'user_login';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'password',
        'role',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'email', 'email');
    }

    public function isAdmin(): bool
    {
        return strtolower((string) $this->role) === 'admin';
    }

    public function isEmployee(): bool
    {
        return strtolower((string) $this->role) === 'employee';
    }

    public function displayName(): string
    {
        $name = trim((string) ($this->employee?->full_name ?? ''));

        return $name !== '' ? $name : (string) $this->email;
    }

    public function isEmployeeActive(): bool
    {
        $status = strtolower((string) ($this->employee?->status ?? 'active'));

        return $status === 'active';
    }
}
