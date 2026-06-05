<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryEmployeeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeRequestController extends Controller
{
    public function __construct(
        private InventoryEmployeeRequestService $requests,
    ) {}

    public function index(Request $request): View
    {
        return view('inventory.requests.index', [
            'tableReady' => $this->requests->ensureReady(),
            'requests' => $this->requests->listRequests(),
            'pendingCount' => $this->requests->pendingCount(),
            'status' => (string) $request->query('status', ''),
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $requestId = (int) $request->input('request_id', 0);
        $status = (string) $request->input('new_status', '');
        $remark = trim((string) $request->input('admin_remark', ''));

        if ($requestId > 0) {
            $this->requests->updateStatus($requestId, $status, $remark);
        }

        return redirect()->route('inventory.requests.index', ['status' => 'updated']);
    }
}
