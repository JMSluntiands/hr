<?php

namespace App\Services\Inventory;

use App\Services\HrSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryActivityLogger
{
    public function __construct(
        private InventorySchemaService $schema,
    ) {}

    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        string $description = '',
        ?string $changeDetails = null,
        ?string $itemCode = null,
    ): void {
        $this->schema->ensureActivityLogsTable();

        if (! Schema::hasTable('inventory_activity_logs')) {
            return;
        }

        $userId = (int) session(HrSession::USER_ID, 0);
        if ($userId <= 0) {
            return;
        }

        $userName = (string) session(HrSession::NAME, 'Unknown');
        $entityType = trim($entityType) !== '' ? trim($entityType) : 'Item';

        DB::table('inventory_activity_logs')->insert([
            'user_id' => $userId,
            'user_name' => $userName,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'item_code' => $itemCode !== null && $itemCode !== '' ? $itemCode : null,
            'description' => $description !== '' ? $description : null,
            'change_details' => $changeDetails !== null && $changeDetails !== '' ? $changeDetails : null,
            'ip_address' => $this->clientIp(),
            'created_at' => now(),
        ]);
    }

    public static function actionWithItemCode(string $action, string $itemCode): string
    {
        $label = trim($itemCode) !== '' ? trim($itemCode) : 'NO-ID';

        return trim($action).' [ITEM: '.$label.']';
    }

    public function itemCodeByItemId(int $itemDbId): string
    {
        if ($itemDbId <= 0 || ! Schema::hasTable('inventory_items')) {
            return '';
        }

        return trim((string) DB::table('inventory_items')->where('id', $itemDbId)->value('item_id'));
    }

    public function itemCodeByAllocationId(int $allocationId): string
    {
        if ($allocationId <= 0 || ! Schema::hasTable('inventory_item_allocations')) {
            return '';
        }

        return trim((string) DB::table('inventory_item_allocations as ia')
            ->join('inventory_items as ii', 'ii.id', '=', 'ia.inventory_item_id')
            ->where('ia.id', $allocationId)
            ->value('ii.item_id'));
    }

    /**
     * @param  array<string, mixed>  $after
     */
    public function buildItemChangeDetails(object $before, array $after, array $finalImagePaths): string
    {
        $changes = [];
        $pairs = [
            'item_name' => 'Item name',
            'description' => 'Description',
            'type' => 'Type',
            'brand_manufacturer' => 'Brand / Manufacturer',
            'item_condition' => 'Item condition',
            'remarks' => 'Remarks',
            'date_arrived' => 'Date arrived',
        ];

        foreach ($pairs as $field => $label) {
            $old = trim((string) ($before->{$field} ?? ''));
            $new = trim((string) ($after[$field] ?? ''));
            if ($old !== $new) {
                $changes[] = $label.': '.($old ?: '(empty)').' → '.($new ?: '(empty)');
            }
        }

        $oldPaths = $this->imagePathsFromRow($before);
        if (json_encode($oldPaths) !== json_encode($finalImagePaths)) {
            $changes[] = 'Item pictures: '.(count($oldPaths) ? count($oldPaths).' file(s)' : '(none)')
                .' → '.(count($finalImagePaths) ? count($finalImagePaths).' file(s)' : '(none)');
        }

        return implode("\n", $changes);
    }

    /**
     * @return list<string>
     */
    public function imagePathsFromRow(object $row): array
    {
        $json = trim((string) ($row->item_image_paths ?? ''));
        $legacy = trim((string) ($row->item_image_path ?? ''));
        $paths = [];

        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $p) {
                    if (is_string($p) && trim($p) !== '') {
                        $paths[] = trim($p);
                    }
                }
            }
        }
        if ($paths === [] && $legacy !== '') {
            $paths[] = $legacy;
        }

        return array_values(array_unique($paths));
    }

    private function clientIp(): ?string
    {
        $request = request();
        if (! $request) {
            return null;
        }

        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $parts = explode(',', (string) $forwarded);

            return trim($parts[0]);
        }

        $ip = $request->ip();

        return $ip !== null && $ip !== '' ? $ip : null;
    }
}
