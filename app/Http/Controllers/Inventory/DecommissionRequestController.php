<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryDecommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DecommissionRequestController extends Controller
{
    public function __construct(
        private InventoryDecommissionService $decommission,
    ) {}

    public function index(Request $request): View
    {
        return view('inventory.decommission.index', [
            'tableReady' => $this->decommission->ensureReady(),
            'requests' => $this->decommission->pendingAndDeclined(),
            'approvedRows' => $this->decommission->approved(),
            'pendingCount' => $this->decommission->pendingCount(),
            'status' => (string) $request->query('status', ''),
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        try {
            $this->decommission->updateStatus(
                (int) $request->input('request_id', 0),
                (string) $request->input('new_status', ''),
                trim((string) $request->input('resolution_remark', '')),
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('inventory.decommission.index', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->route('inventory.decommission.index', ['status' => 'updated']);
    }
}
