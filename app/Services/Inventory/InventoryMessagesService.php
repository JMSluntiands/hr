<?php

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryMessagesService
{
    public function __construct(
        private InventorySchemaService $schema,
        private InventoryActivityLogger $activity,
    ) {}

    public function ensureReady(): bool
    {
        $this->schema->ensureCoreTables();

        return Schema::hasTable('inventory_item_allocations');
    }

    public function unreadCount(): int
    {
        if (! $this->ensureReady()) {
            return 0;
        }

        return (int) DB::table('inventory_item_allocations')
            ->whereNotNull('employee_appeal')
            ->whereRaw("TRIM(employee_appeal) <> ''")
            ->whereNull('admin_viewed_at')
            ->count();
    }

    /**
     * @return Collection<int, object>
     */
    public function listMessages(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_item_allocations as ia')
            ->join('inventory_items as ii', 'ii.id', '=', 'ia.inventory_item_id')
            ->join('employees as e', 'e.id', '=', 'ia.employee_id')
            ->select([
                'ia.id',
                'ia.employee_appeal',
                'ia.employee_appeal_remarks',
                'ia.employee_appeal_at',
                'ia.admin_viewed_at',
                'ii.item_id',
                'ii.item_name',
                'e.full_name',
                'e.employee_id as employee_code',
            ])
            ->whereNotNull('ia.employee_appeal')
            ->whereRaw("TRIM(ia.employee_appeal) <> ''")
            ->orderByRaw('CASE WHEN ia.admin_viewed_at IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('ia.employee_appeal_at')
            ->orderByDesc('ia.id')
            ->get();
    }

    public function markRead(int $allocationId): void
    {
        if ($allocationId <= 0 || ! $this->ensureReady()) {
            return;
        }

        DB::table('inventory_item_allocations')
            ->where('id', $allocationId)
            ->whereNotNull('employee_appeal')
            ->whereRaw("TRIM(employee_appeal) <> ''")
            ->update(['admin_viewed_at' => now()]);

        $itemCode = $this->activity->itemCodeByAllocationId($allocationId);
        $this->activity->log(
            $this->activity::actionWithItemCode('Mark Message Read', $itemCode),
            'Message',
            $allocationId,
            'Admin marked inventory appeal message as read (allocation #'.$allocationId.').',
            null,
            $itemCode,
        );
    }

    public function markAllRead(): void
    {
        if (! $this->ensureReady()) {
            return;
        }

        DB::table('inventory_item_allocations')
            ->whereNotNull('employee_appeal')
            ->whereRaw("TRIM(employee_appeal) <> ''")
            ->whereNull('admin_viewed_at')
            ->update(['admin_viewed_at' => now()]);

        $this->activity->log(
            $this->activity::actionWithItemCode('Mark All Messages Read', 'MULTI'),
            'Message',
            null,
            'Admin marked all unread inventory appeal messages as read.',
            null,
            'MULTI',
        );
    }
}
