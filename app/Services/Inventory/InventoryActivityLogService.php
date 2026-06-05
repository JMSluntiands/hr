<?php

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class InventoryActivityLogService
{
    public function __construct(
        private InventorySchemaService $schema,
    ) {}

    /**
     * @return Collection<int, object>
     */
    public function recentLogs(int $limit = 500): Collection
    {
        $this->schema->ensureActivityLogsTable();

        if (! Schema::hasTable('inventory_activity_logs')) {
            return collect();
        }

        return \Illuminate\Support\Facades\DB::table('inventory_activity_logs')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function tableExists(): bool
    {
        $this->schema->ensureActivityLogsTable();

        return Schema::hasTable('inventory_activity_logs');
    }
}
