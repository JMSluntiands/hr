<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private InventoryDashboardService $dashboard,
    ) {}

    public function index(): View
    {
        return view('inventory.dashboard.index', [
            'tableReady' => $this->dashboard->isReady(),
            'cards' => $this->dashboard->overviewCards(),
            'appealUnreadCount' => $this->dashboard->unreadAppealCount(),
            'appeals' => $this->dashboard->recentAppeals(),
            'messagesUrl' => route('inventory.messages.index'),
        ]);
    }
}
