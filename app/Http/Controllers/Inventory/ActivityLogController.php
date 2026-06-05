<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryActivityLogService;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(
        private InventoryActivityLogService $logs,
    ) {}

    public function index(): View
    {
        return view('inventory.activity-log.index', [
            'tableMissing' => ! $this->logs->tableExists(),
            'logs' => $this->logs->recentLogs(),
        ]);
    }
}
