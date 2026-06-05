<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    public function log(int $userId, string $userName, string $action, string $description, string $entityType = 'auth'): void
    {
        if ($userId <= 0 || ! Schema::hasTable('activity_logs')) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => $userId,
            'user_name' => $userName,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $userId,
            'description' => $description,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
