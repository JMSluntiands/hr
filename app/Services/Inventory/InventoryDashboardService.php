<?php

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryDashboardService
{
    public function __construct(
        private InventorySchemaService $schema,
    ) {}

    public function isReady(): bool
    {
        $this->schema->ensureCoreTables();

        return Schema::hasTable('inventory_items');
    }

    /**
     * @return array<string, int>
     */
    public function itemCountsByCategory(): array
    {
        $counts = array_fill_keys(InventoryItemCatalog::ITEM_OPTIONS, 0);

        if (! Schema::hasTable('inventory_items')) {
            return $counts;
        }

        $rows = DB::table('inventory_items')
            ->select('item_name', DB::raw('COUNT(*) as total_count'))
            ->whereNull('decommissioned_at')
            ->groupBy('item_name')
            ->get();

        foreach ($rows as $row) {
            $name = (string) $row->item_name;
            if (array_key_exists($name, $counts)) {
                $counts[$name] = (int) $row->total_count;
            }
        }

        return $counts;
    }

    /**
     * @return list<array{name: string, count: int, bgClass: string, iconSvg: string}>
     */
    public function overviewCards(): array
    {
        $counts = $this->itemCountsByCategory();
        $cards = [];
        $index = 0;

        foreach ($counts as $name => $count) {
            $cards[] = [
                'name' => $name,
                'count' => $count,
                'bgClass' => InventoryItemCatalog::CARD_BACKGROUNDS[$index % count(InventoryItemCatalog::CARD_BACKGROUNDS)],
                'iconSvg' => InventoryItemCatalog::cardIconSvg($name),
            ];
            $index++;
        }

        return $cards;
    }

    public function unreadAppealCount(): int
    {
        if (! Schema::hasTable('inventory_item_allocations')) {
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
    public function recentAppeals(int $limit = 5): Collection
    {
        if (! Schema::hasTable('inventory_item_allocations')
            || ! Schema::hasTable('employees')
            || ! Schema::hasTable('inventory_items')) {
            return collect();
        }

        return DB::table('inventory_item_allocations as ia')
            ->join('employees as e', 'e.id', '=', 'ia.employee_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'ia.inventory_item_id')
            ->select([
                'ia.id',
                'ia.employee_appeal',
                'ia.employee_appeal_at',
                'ia.admin_viewed_at',
                'e.full_name',
                'e.employee_id as employee_code',
                'ii.item_id',
                'ii.item_name',
            ])
            ->whereNotNull('ia.employee_appeal')
            ->whereRaw("TRIM(ia.employee_appeal) <> ''")
            ->whereNull('ii.decommissioned_at')
            ->orderByRaw('CASE WHEN ia.admin_viewed_at IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByDesc('ia.employee_appeal_at')
            ->orderByDesc('ia.id')
            ->limit($limit)
            ->get();
    }

    public function pendingRequestCount(): int
    {
        if (! Schema::hasTable('inventory_item_requests')) {
            return 0;
        }

        return (int) DB::table('inventory_item_requests')->where('status', 'pending')->count();
    }

    public function pendingDecommissionCount(): int
    {
        if (! Schema::hasTable('inventory_decommission_requests')) {
            return 0;
        }

        return (int) DB::table('inventory_decommission_requests')->where('status', 'pending')->count();
    }
}
