<?php

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryAllocationService
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

    /**
     * @return Collection<int, object>
     */
    public function activeEmployees(): Collection
    {
        return DB::table('employees')
            ->select(['id', 'employee_id', 'full_name'])
            ->where('status', 'Active')
            ->orderBy('full_name')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function availableItems(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_items as ii')
            ->leftJoin('inventory_item_allocations as ia', function ($join) {
                $join->on('ia.inventory_item_id', '=', 'ii.id')
                    ->whereNull('ia.date_return');
            })
            ->select(['ii.id', 'ii.item_id', 'ii.item_name', 'ii.description'])
            ->whereNull('ia.id')
            ->whereNull('ii.decommissioned_at')
            ->orderBy('ii.item_name')
            ->orderBy('ii.item_id')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function activeAllocations(int $employeeId = 0): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        $query = DB::table('inventory_item_allocations as ia')
            ->join('employees as e', 'e.id', '=', 'ia.employee_id')
            ->join('inventory_items as ii', 'ii.id', '=', 'ia.inventory_item_id')
            ->select([
                'ia.id',
                'ia.employee_id',
                'ia.inventory_item_id',
                'ia.date_received',
                'ia.return_remarks',
                'e.full_name',
                'e.employee_id as emp_code',
                'ii.item_id',
                'ii.item_name',
                'ii.description',
                'ii.type',
                'ii.item_condition',
            ])
            ->whereNull('ia.date_return')
            ->whereNull('ii.decommissioned_at')
            ->orderBy('e.full_name')
            ->orderByDesc('ia.date_received')
            ->orderByDesc('ia.id');

        if ($employeeId > 0) {
            $query->where('ia.employee_id', $employeeId);
        }

        return $query->get();
    }

    /**
     * @throws \RuntimeException
     */
    public function create(int $employeeId, int $inventoryItemId, string $dateReceived): void
    {
        if ($employeeId <= 0 || $inventoryItemId <= 0 || $dateReceived === '') {
            throw new \RuntimeException('Please fill in all required fields.');
        }

        $already = DB::table('inventory_item_allocations as ia')
            ->join('inventory_items as ii', 'ii.id', '=', 'ia.inventory_item_id')
            ->where('ia.inventory_item_id', $inventoryItemId)
            ->whereNull('ia.date_return')
            ->whereNull('ii.decommissioned_at')
            ->exists();

        if ($already) {
            throw new \RuntimeException('Selected item is already allocated.');
        }

        DB::table('inventory_item_allocations')->insert([
            'inventory_item_id' => $inventoryItemId,
            'employee_id' => $employeeId,
            'date_received' => $dateReceived,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @throws \RuntimeException
     */
    public function returnItem(int $allocationId, string $dateReturn, string $returnRemarks = ''): void
    {
        if ($allocationId <= 0 || $dateReturn === '') {
            throw new \RuntimeException('Please provide Date Return.');
        }

        $existing = DB::table('inventory_item_allocations')
            ->where('id', $allocationId)
            ->whereNull('date_return')
            ->first();

        if (! $existing) {
            throw new \RuntimeException('Allocation record not found or already returned.');
        }

        $dateReceived = (string) ($existing->date_received ?? '');
        if ($dateReceived !== '' && $dateReturn < $dateReceived) {
            throw new \RuntimeException('Date Return cannot be earlier than Date Received.');
        }

        $updated = DB::table('inventory_item_allocations')
            ->where('id', $allocationId)
            ->whereNull('date_return')
            ->update([
                'date_return' => $dateReturn,
                'return_remarks' => $returnRemarks !== '' ? $returnRemarks : null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            $itemCode = $this->activity->itemCodeByAllocationId($allocationId);
            $this->activity->log(
                $this->activity::actionWithItemCode('Return Allocation Failed', $itemCode),
                'Allocation',
                $allocationId,
                'Failed to return allocation #'.$allocationId.'.',
                null,
                $itemCode,
            );
            throw new \RuntimeException('Unable to process return.');
        }

        $itemCode = $this->activity->itemCodeByAllocationId($allocationId);
        $this->activity->log(
            $this->activity::actionWithItemCode('Return Allocation', $itemCode),
            'Allocation',
            $allocationId,
            "Returned allocation #{$allocationId} (date return: {$dateReturn}).",
            null,
            $itemCode,
        );
    }
}
