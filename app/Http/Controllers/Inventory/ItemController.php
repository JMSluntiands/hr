<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryItemCatalog;
use App\Services\Inventory\InventoryItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function __construct(
        private InventoryItemService $items,
    ) {}

    public function index(Request $request): View
    {
        $tab = strtolower((string) $request->query('tab', 'add'));
        if (! in_array($tab, ['add', 'list', 'history'], true)) {
            $tab = 'add';
        }

        $editId = (int) $request->query('edit_id', 0);
        $editItem = null;
        if ($editId > 0) {
            $tab = 'add';
            $editItem = $this->items->find($editId);
        }

        $data = [
            'tableReady' => $this->items->ensureReady(),
            'activeTab' => $tab,
            'itemOptions' => InventoryItemCatalog::ITEM_OPTIONS,
            'itemConditions' => InventoryItemCatalog::CONDITIONS,
            'itemPrefixes' => InventoryItemCatalog::PREFIXES,
            'status' => (string) $request->query('status', ''),
            'message' => (string) $request->query('message', ''),
            'printStickerBase' => url('/inventory/print-sticker.php'),
            'editItem' => $editItem,
            'editItemImagePaths' => $editItem ? $this->items->imagePathsFromRow($editItem) : [],
        ];

        if ($tab === 'list') {
            $data['items'] = $this->items->listActiveItems()->map(function ($item) {
                $paths = $this->items->imagePathsFromRow($item);
                $item->image_paths = $paths;
                $item->image_paths_json = json_encode($paths, JSON_UNESCAPED_SLASHES);

                return $item;
            });
            $data['itemHistoryMap'] = $this->items->allocationHistoryByItemId();
        }

        if ($tab === 'history') {
            $data['historyRows'] = $this->items->listAllocationHistoryRows();
        }

        return view('inventory.items.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $files = $this->normalizeUploadedFiles($request);
            $id = (int) $request->input('id', 0);

            if ($id > 0) {
                $ok = $this->items->update($id, $request->all(), $files);

                return $this->redirectItems('add', $ok ? ['status' => 'updated'] : [
                    'status' => 'error',
                    'message' => 'Unable to update item',
                ]);
            }

            $newId = $this->items->create($request->all(), $files);
            if ($newId > 0) {
                return $this->redirectItems('add', ['status' => 'created']);
            }

            return $this->redirectItems('add', [
                'status' => 'error',
                'message' => 'Unable to create item',
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->redirectItems('add', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        try {
            $files = $this->normalizeUploadedFiles($request);
            $ok = $this->items->update($item, $request->all(), $files);

            return $this->redirectItems('list', $ok ? ['status' => 'updated'] : [
                'status' => 'error',
                'message' => 'Unable to update item',
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return $this->redirectItems('list', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function destroy(int $item): RedirectResponse
    {
        if ($this->items->delete($item)) {
            return $this->redirectItems('list', ['status' => 'deleted']);
        }

        return $this->redirectItems('list', [
            'status' => 'error',
            'message' => 'Unable to delete item',
        ]);
    }

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    private function normalizeUploadedFiles(Request $request): array
    {
        $files = $request->file('item_images', []);
        if (! is_array($files)) {
            return $files ? [$files] : [];
        }

        $single = $request->file('item_image');
        if ($single && $single->isValid()) {
            $files[] = $single;
        }

        return array_values(array_filter($files));
    }

    /**
     * @param  array<string, string>  $query
     */
    private function redirectItems(string $tab, array $query = []): RedirectResponse
    {
        return redirect()->route('inventory.items.index', array_merge(['tab' => $tab], array_filter($query)));
    }
}
