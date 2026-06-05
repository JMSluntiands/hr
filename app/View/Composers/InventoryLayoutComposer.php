<?php

namespace App\View\Composers;

use App\Services\AdminPermissionService;
use App\Services\HrSession;
use App\Services\Inventory\InventoryDashboardService;
use Illuminate\View\View;

class InventoryLayoutComposer
{
    public function __construct(
        private HrSession $hrSession,
        private InventoryDashboardService $dashboard,
        private AdminPermissionService $permissions,
    ) {}

    public function compose(View $view): void
    {
        $userId = (int) session(HrSession::USER_ID, 0);

        $view->with([
            'adminName' => session(HrSession::NAME, 'Admin'),
            'role' => session(HrSession::ROLE, 'admin'),
            'unreadMessageCount' => $this->dashboard->unreadAppealCount(),
            'pendingRequestCount' => $this->dashboard->pendingRequestCount(),
            'pendingDecommissionCount' => $this->dashboard->pendingDecommissionCount(),
            'sidebarRestricted' => $this->permissions->isSidebarRestricted($userId),
            'sidebarCan' => fn (string $key): bool => $this->permissions->canAccessSidebar($userId, $key),
            'permCan' => fn (string $key): bool => $this->permissions->can($userId, $key),
        ]);
    }
}
