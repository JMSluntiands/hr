<?php

namespace App\Services\Inventory;

use App\Services\HrSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryDecommissionService
{
    public function __construct(
        private InventorySchemaService $schema,
        private InventoryActivityLogger $activity,
    ) {}

    public function ensureReady(): bool
    {
        $this->schema->ensureCoreTables();

        return Schema::hasTable('inventory_decommission_requests');
    }

    public function pendingCount(): int
    {
        if (! $this->ensureReady()) {
            return 0;
        }

        return (int) DB::table('inventory_decommission_requests')->where('status', 'pending')->count();
    }

    /**
     * @return Collection<int, object>
     */
    public function pendingAndDeclined(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_decommission_requests as r')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->select($this->requestColumns('r', 'e'))
            ->whereIn('r.status', ['pending', 'declined'])
            ->orderByRaw("CASE r.status WHEN 'pending' THEN 0 ELSE 1 END ASC")
            ->orderByDesc('r.created_at')
            ->orderByDesc('r.id')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function approved(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_decommission_requests as r')
            ->join('employees as e', 'e.id', '=', 'r.employee_id')
            ->leftJoin('inventory_items as ii', $this->itemCodeJoinOn())
            ->select(array_merge(
                $this->requestColumns('r', 'e'),
                [
                    'ii.item_name as live_item_name',
                    'ii.description as live_description',
                    'ii.type as live_type',
                    'ii.brand_manufacturer as live_brand',
                    'ii.item_condition as live_condition',
                    'ii.remarks as live_remarks',
                    'ii.date_arrived as live_date_arrived',
                ]
            ))
            ->where('r.status', 'approved')
            ->orderByDesc('r.resolved_at')
            ->orderByDesc('r.id')
            ->get();
    }

    /**
     * @throws \RuntimeException
     */
    public function updateStatus(int $requestId, string $status, string $remark = ''): void
    {
        if (! in_array($status, ['approved', 'declined'], true) || $requestId <= 0) {
            return;
        }

        DB::transaction(function () use ($requestId, $status, $remark) {
            $reviewerId = (int) session(HrSession::USER_ID, 0);
            $reviewerName = (string) session(HrSession::NAME, 'Admin');

            $affected = DB::table('inventory_decommission_requests')
                ->where('id', $requestId)
                ->where('status', 'pending')
                ->update([
                    'status' => $status,
                    'resolution_remark' => $remark !== '' ? $remark : null,
                    'reviewed_by_user_id' => $reviewerId > 0 ? $reviewerId : null,
                    'reviewed_by_name' => $reviewerName,
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                return;
            }

            if ($status === 'approved' && ! $this->finalizeApproved($requestId, $remark)) {
                throw new \RuntimeException('Could not finalize decommission for inventory item.');
            }

            $label = $status === 'approved' ? 'Approve' : 'Decline';
            $itemCode = trim((string) DB::table('inventory_decommission_requests')
                ->where('id', $requestId)
                ->value('item_code'));
            $reviewerName = (string) session(HrSession::NAME, 'Admin');
            $desc = "{$label}d decommission request #{$requestId}"
                .($itemCode !== '' ? " (Item ID: {$itemCode})" : '')
                ." by {$reviewerName}.";
            $this->activity->log(
                $this->activity::actionWithItemCode($label.' Decommission Request', $itemCode !== '' ? $itemCode : 'REQ-'.$requestId),
                'DecommissionRequest',
                $requestId,
                $desc,
                $remark !== '' ? 'Remark: '.$remark : null,
                $itemCode !== '' ? $itemCode : null,
            );
        });
    }

    private function finalizeApproved(int $requestId, string $resolutionRemark): bool
    {
        $row = DB::table('inventory_decommission_requests')
            ->where('id', $requestId)
            ->where('status', 'approved')
            ->value('inventory_item_allocation_id');

        if ($row === null) {
            return true;
        }

        $allocId = (int) $row;
        if ($allocId <= 0) {
            return true;
        }

        $inventoryItemId = (int) DB::table('inventory_item_allocations')
            ->where('id', $allocId)
            ->value('inventory_item_id');

        if ($inventoryItemId <= 0) {
            return true;
        }

        $returnRemarks = 'Decommission approved (request #'.$requestId.').';
        $trimRemark = trim($resolutionRemark);
        if ($trimRemark !== '') {
            $note = mb_strlen($trimRemark) > 400 ? mb_substr($trimRemark, 0, 400, 'UTF-8') : $trimRemark;
            $returnRemarks .= ' Note: '.$note;
        }

        DB::table('inventory_item_allocations')
            ->where('id', $allocId)
            ->whereNull('date_return')
            ->update([
                'date_return' => now()->toDateString(),
                'return_remarks' => $returnRemarks,
                'updated_at' => now(),
            ]);

        return DB::table('inventory_items')
            ->where('id', $inventoryItemId)
            ->update([
                'decommissioned_at' => DB::raw('COALESCE(decommissioned_at, NOW())'),
                'item_condition' => 'Decommissioned',
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Legacy inventory_items vs decommission_requests may use different utf8mb4 collations.
     */
    private function itemCodeJoinOn(): \Closure
    {
        return function ($join) {
            $join->whereRaw('ii.item_id COLLATE utf8mb4_unicode_ci = r.item_code COLLATE utf8mb4_unicode_ci');
        };
    }

    /**
     * @return list<string>
     */
    private function requestColumns(string $r, string $e): array
    {
        return [
            "{$r}.id",
            "{$r}.company_name",
            "{$r}.request_employee_name",
            "{$r}.equipment_name",
            "{$r}.item_code",
            "{$r}.equipment_type",
            "{$r}.serial_number",
            "{$r}.equipment_description",
            "{$r}.brand_manufacturer",
            "{$r}.item_date_received",
            "{$r}.date_decommissioning",
            "{$r}.reason_decommissioning",
            "{$r}.test_1_notes",
            "{$r}.test_1_date",
            "{$r}.test_2_notes",
            "{$r}.test_2_date",
            "{$r}.test_3_notes",
            "{$r}.test_3_date",
            "{$r}.test_1_attachment_paths",
            "{$r}.test_2_attachment_paths",
            "{$r}.test_3_attachment_paths",
            "{$r}.attachment_path",
            "{$r}.status",
            "{$r}.resolution_remark",
            "{$r}.reviewed_by_name",
            "{$r}.resolved_at",
            "{$r}.created_at",
            "{$e}.full_name",
            "{$e}.employee_id as employee_code",
        ];
    }

    /**
     * @return list<string>
     */
    public static function decodeAttachmentPaths(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }
        $paths = [];
        foreach ($decoded as $p) {
            $s = trim((string) $p);
            if ($s !== '') {
                $paths[] = $s;
            }
        }

        return $paths;
    }
}
