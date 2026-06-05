<?php

namespace App\Services\Inventory;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryEmployeeRequestService
{
    public function __construct(
        private InventorySchemaService $schema,
        private InventoryActivityLogger $activity,
    ) {}

    public function ensureReady(): bool
    {
        $this->schema->ensureCoreTables();

        return Schema::hasTable('inventory_item_requests');
    }

    public function pendingCount(): int
    {
        if (! $this->ensureReady()) {
            return 0;
        }

        return (int) DB::table('inventory_item_requests')->where('status', 'pending')->count();
    }

    /**
     * @return Collection<int, object>
     */
    public function listRequests(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_item_requests as r')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->select([
                'r.id',
                'r.item_name',
                'r.details',
                'r.status',
                'r.admin_remark',
                'r.resolved_at',
                'r.created_at',
                'e.full_name',
                'e.employee_id as employee_code',
            ])
            ->orderByRaw("CASE r.status WHEN 'pending' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('r.created_at')
            ->orderByDesc('r.id')
            ->get();
    }

    public function updateStatus(int $requestId, string $status, string $adminRemark = ''): bool
    {
        if (! in_array($status, ['approved', 'rejected'], true) || ! $this->ensureReady()) {
            return false;
        }

        $updated = DB::table('inventory_item_requests')
            ->where('id', $requestId)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'admin_remark' => $adminRemark !== '' ? $adminRemark : null,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]) > 0;

        if ($updated) {
            $label = $status === 'approved' ? 'Approve' : 'Reject';
            $this->activity->log(
                'Inventory Item Request '.$label,
                'Request',
                $requestId,
                'Admin '.strtolower($label).'ed inventory item request #'.$requestId.'.',
                $adminRemark !== '' ? 'Admin remark: '.$adminRemark : null,
            );
        }

        return $updated;
    }
}
