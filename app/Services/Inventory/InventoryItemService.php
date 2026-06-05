<?php

namespace App\Services\Inventory;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryItemService
{
    private const MAX_IMAGES = 20;

    public function __construct(
        private InventorySchemaService $schema,
        private InventoryActivityLogger $activity,
    ) {}

    public function ensureReady(): bool
    {
        $this->schema->ensureCoreTables();

        return Schema::hasTable('inventory_items');
    }

    /**
     * @return Collection<int, object>
     */
    public function listActiveItems(): Collection
    {
        if (! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_items as ii')
            ->select([
                'ii.id',
                'ii.item_id',
                'ii.item_name',
                'ii.description',
                'ii.brand_manufacturer',
                'ii.type',
                'ii.item_condition',
                'ii.remarks',
                'ii.item_image_path',
                'ii.item_image_paths',
                'ii.date_arrived',
                DB::raw("(
                    SELECT e.full_name
                    FROM inventory_item_allocations ia
                    INNER JOIN employees e ON e.id = ia.employee_id
                    WHERE ia.inventory_item_id = ii.id AND ia.date_return IS NULL
                    ORDER BY ia.date_received DESC, ia.id DESC
                    LIMIT 1
                ) AS allocated_to_name"),
            ])
            ->whereNull('ii.decommissioned_at')
            ->orderByDesc('ii.id')
            ->get();
    }

    /**
     * @return array<int, list<array<string, string>>>
     */
    public function allocationHistoryByItemId(): array
    {
        if (! Schema::hasTable('inventory_item_allocations')) {
            return [];
        }

        $map = [];
        $rows = DB::table('inventory_item_allocations as ia')
            ->leftJoin('employees as e', 'e.id', '=', 'ia.employee_id')
            ->select([
                'ia.inventory_item_id',
                'e.full_name',
                'e.employee_id as employee_code',
                'ia.date_received',
                'ia.date_return',
                'ia.return_remarks',
            ])
            ->orderByDesc('ia.date_received')
            ->orderByDesc('ia.id')
            ->get();

        foreach ($rows as $row) {
            $itemId = (int) $row->inventory_item_id;
            $map[$itemId] ??= [];
            $map[$itemId][] = [
                'employee_name' => (string) ($row->full_name ?? ''),
                'employee_code' => (string) ($row->employee_code ?? ''),
                'date_received' => (string) ($row->date_received ?? ''),
                'date_return' => (string) ($row->date_return ?? ''),
                'return_remarks' => (string) ($row->return_remarks ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    public function imagePathsFromRow(object|array $row): array
    {
        $data = is_array($row) ? $row : (array) $row;
        $json = trim((string) ($data['item_image_paths'] ?? ''));
        $legacy = trim((string) ($data['item_image_path'] ?? ''));
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

    public function find(int $id): ?object
    {
        if (! $this->ensureReady()) {
            return null;
        }

        return DB::table('inventory_items')->where('id', $id)->first();
    }

    public function delete(int $id): bool
    {
        if (! $this->ensureReady()) {
            return false;
        }

        $item = $this->find($id);
        if (! $item) {
            return false;
        }

        $itemCode = (string) ($item->item_id ?? '');
        $deleted = DB::table('inventory_items')->where('id', $id)->delete() > 0;

        if ($deleted) {
            $details = "Item ID: {$itemCode}\n"
                .'Item name: '.($item->item_name ?? '')."\n"
                .'Description: '.(trim((string) ($item->description ?? '')) ?: '(empty)')."\n"
                .'Type: '.(trim((string) ($item->type ?? '')) ?: '(empty)')."\n"
                .'Condition: '.(trim((string) ($item->item_condition ?? '')) ?: '(empty)');
            $this->activity->log(
                $this->activity::actionWithItemCode('Delete Item', $itemCode),
                'Item',
                $id,
                'Deleted inventory item record #'.$id.'.',
                $details,
                $itemCode,
            );
        } else {
            $this->activity->log(
                $this->activity::actionWithItemCode('Delete Item Failed', $itemCode),
                'Item',
                $id,
                'Failed to delete inventory item record #'.$id.'.',
                null,
                $itemCode,
            );
        }

        return $deleted;
    }

    /**
     * @return Collection<int, object>
     */
    public function listAllocationHistoryRows(): Collection
    {
        if (! Schema::hasTable('inventory_item_allocations') || ! $this->ensureReady()) {
            return collect();
        }

        return DB::table('inventory_item_allocations as ia')
            ->join('inventory_items as ii', 'ii.id', '=', 'ia.inventory_item_id')
            ->leftJoin('employees as e', 'e.id', '=', 'ia.employee_id')
            ->select([
                'ia.id',
                'ii.item_id',
                'ii.item_name',
                'e.full_name',
                'e.employee_id as employee_code',
                'ia.date_received',
                'ia.date_return',
                'ia.return_remarks',
            ])
            ->orderByDesc('ia.id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $newFiles
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function create(array $input, array $newFiles = []): int
    {
        if (! $this->ensureReady()) {
            return 0;
        }

        $itemName = trim((string) ($input['item_name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $brandManufacturer = trim((string) ($input['brand_manufacturer'] ?? ''));
        $type = trim((string) ($input['type'] ?? ''));
        $itemCondition = trim((string) ($input['item_condition'] ?? ''));
        $remarks = trim((string) ($input['remarks'] ?? ''));
        $dateArrived = trim((string) ($input['date_arrived'] ?? ''));

        if (! isset(InventoryItemCatalog::PREFIXES[$itemName])) {
            throw new \InvalidArgumentException('Invalid item name.');
        }
        if (! in_array($itemCondition, InventoryItemCatalog::CONDITIONS, true)) {
            throw new \InvalidArgumentException('Invalid item condition.');
        }

        $finalPaths = $this->storeUploadedImages($newFiles);
        if (count($finalPaths) > self::MAX_IMAGES) {
            throw new \InvalidArgumentException('Too many images (maximum '.self::MAX_IMAGES.').');
        }

        $prefix = InventoryItemCatalog::PREFIXES[$itemName];
        $generatedId = $this->generateItemId($prefix);
        $pathsJson = $finalPaths === [] ? null : json_encode($finalPaths, JSON_UNESCAPED_SLASHES);
        $firstPath = $finalPaths[0] ?? null;

        $newId = (int) DB::table('inventory_items')->insertGetId([
            'item_id' => $generatedId,
            'item_name' => $itemName,
            'description' => $description,
            'brand_manufacturer' => $brandManufacturer !== '' ? $brandManufacturer : null,
            'type' => $type,
            'item_condition' => $itemCondition,
            'remarks' => $remarks,
            'item_image_path' => $firstPath,
            'item_image_paths' => $pathsJson,
            'date_arrived' => $dateArrived !== '' ? $dateArrived : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($newId > 0) {
            $picNote = count($finalPaths) === 0 ? 'none' : count($finalPaths).' image(s)';
            $createDetails = "Item name: {$itemName}\n"
                .'Description: '.($description ?: '(empty)')."\n"
                .'Brand / Manufacturer: '.($brandManufacturer ?: '(empty)')."\n"
                .'Type: '.($type ?: '(empty)')."\n"
                ."Item condition: {$itemCondition}\n"
                .'Remarks: '.($remarks ?: '(empty)')."\n"
                .'Date arrived: '.($dateArrived ?: '(empty)')."\n"
                .'Item pictures: '.$picNote;
            $this->activity->log(
                $this->activity::actionWithItemCode('Create Item', $generatedId),
                'Item',
                $newId,
                'Created new inventory item: '.$itemName.'.',
                $createDetails,
                $generatedId,
            );
        } else {
            $this->activity->log(
                $this->activity::actionWithItemCode('Create Item Failed', 'NO-ID'),
                'Item',
                null,
                'Failed to create inventory item: '.$itemName.'.',
            );
        }

        return $newId;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $newFiles
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function update(int $id, array $input, array $newFiles = []): bool
    {
        $item = $this->find($id);
        if (! $item) {
            return false;
        }

        $itemName = trim((string) ($input['item_name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $brandManufacturer = trim((string) ($input['brand_manufacturer'] ?? ''));
        $type = trim((string) ($input['type'] ?? ''));
        $itemCondition = trim((string) ($input['item_condition'] ?? ''));
        $remarks = trim((string) ($input['remarks'] ?? ''));
        $dateArrived = trim((string) ($input['date_arrived'] ?? ''));

        if (! isset(InventoryItemCatalog::PREFIXES[$itemName])) {
            throw new \InvalidArgumentException('Invalid item name.');
        }
        if (! in_array($itemCondition, InventoryItemCatalog::CONDITIONS, true)) {
            throw new \InvalidArgumentException('Invalid item condition.');
        }

        $existingPaths = $this->parsePathsJson((string) ($input['current_image_paths'] ?? '[]'));
        $newPaths = $this->storeUploadedImages($newFiles);
        $finalPaths = array_values(array_unique(array_merge($existingPaths, $newPaths)));

        if (count($finalPaths) > self::MAX_IMAGES) {
            throw new \InvalidArgumentException('Too many images (maximum '.self::MAX_IMAGES.').');
        }

        $prefix = InventoryItemCatalog::PREFIXES[$itemName];
        $currentItemId = (string) ($item->item_id ?? '');
        $finalItemId = (str_starts_with($currentItemId, $prefix) && $currentItemId !== '')
            ? $currentItemId
            : $this->generateItemId($prefix);

        $pathsJson = $finalPaths === [] ? null : json_encode($finalPaths, JSON_UNESCAPED_SLASHES);
        $firstPath = $finalPaths[0] ?? null;

        $after = [
            'item_name' => $itemName,
            'description' => $description,
            'brand_manufacturer' => $brandManufacturer,
            'type' => $type,
            'item_condition' => $itemCondition,
            'remarks' => $remarks,
            'date_arrived' => $dateArrived,
        ];
        $changeDetails = $this->activity->buildItemChangeDetails($item, $after, $finalPaths);

        $ok = DB::table('inventory_items')
            ->where('id', $id)
            ->update([
                'item_id' => $finalItemId,
                'item_name' => $itemName,
                'description' => $description,
                'brand_manufacturer' => $brandManufacturer !== '' ? $brandManufacturer : null,
                'type' => $type,
                'item_condition' => $itemCondition,
                'remarks' => $remarks,
                'item_image_path' => $firstPath,
                'item_image_paths' => $pathsJson,
                'date_arrived' => $dateArrived !== '' ? $dateArrived : null,
                'updated_at' => now(),
            ]) > 0;

        $logCode = $finalItemId !== '' ? $finalItemId : (string) ($item->item_id ?? '');
        if ($ok) {
            $desc = $changeDetails !== '' ? 'Updated inventory item.' : 'Updated inventory item (no field changes).';
            $this->activity->log(
                $this->activity::actionWithItemCode('Update Item', $logCode),
                'Item',
                $id,
                $desc,
                $changeDetails !== '' ? $changeDetails : null,
                $logCode,
            );
        } else {
            $this->activity->log(
                $this->activity::actionWithItemCode('Update Item Failed', $logCode),
                'Item',
                $id,
                'Failed to update inventory item record #'.$id.'.',
                null,
                $logCode,
            );
        }

        return $ok;
    }

    /**
     * @return list<string>
     */
    private function parsePathsJson(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }
        $paths = [];
        foreach ($decoded as $p) {
            if (is_string($p) && trim($p) !== '') {
                $paths[] = trim($p);
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    private function storeUploadedImages(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            if ($file->getSize() > 10 * 1024 * 1024) {
                throw new \RuntimeException('Image must be 10MB or smaller.');
            }
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                throw new \RuntimeException('Invalid image type.');
            }
            $dir = base_path('uploads/items');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $basename = 'item_'.date('YmdHis').'_'.bin2hex(random_bytes(5)).'.'.$ext;
            $file->move($dir, $basename);
            $paths[] = 'uploads/items/'.$basename;
        }

        return $paths;
    }

    private function generateItemId(string $prefix): string
    {
        $like = $prefix.'%';
        $last = DB::table('inventory_items')
            ->where('item_id', 'like', $like)
            ->orderByDesc('id')
            ->value('item_id');

        $next = 1;
        if ($last) {
            $next = (int) preg_replace('/[^0-9]/', '', substr((string) $last, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
