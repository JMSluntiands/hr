<?php

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryReportService
{
    public function __construct(
        private InventorySchemaService $schema,
    ) {}

    public function ensureReady(): bool
    {
        $this->schema->ensureCoreTables();

        return Schema::hasTable('inventory_items');
    }

    /**
     * @return Collection<int, object>
     */
    public function reportRows(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_items as ii')
            ->leftJoin('inventory_item_allocations as ia', function ($join) {
                $join->on('ia.inventory_item_id', '=', 'ii.id')
                    ->whereNull('ia.date_return');
            })
            ->leftJoin('employees as e', 'e.id', '=', 'ia.employee_id')
            ->select([
                'ii.item_name',
                'ii.item_id',
                'ii.description',
                'e.full_name',
                'e.employee_id as employee_code',
            ])
            ->whereNull('ii.decommissioned_at')
            ->orderBy('ii.item_name')
            ->orderBy('ii.item_id')
            ->get();
    }

    public static function allocatedToLabel(object $row): string
    {
        $fullName = trim((string) ($row->full_name ?? ''));
        $code = trim((string) ($row->employee_code ?? ''));
        if ($fullName === '') {
            return 'Not Allocated';
        }

        return $code !== '' ? "{$fullName} ({$code})" : $fullName;
    }
}
