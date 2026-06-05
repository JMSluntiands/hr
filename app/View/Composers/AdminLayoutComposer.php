<?php

namespace App\View\Composers;

use App\Services\AdminPermissionService;
use App\Services\HrSession;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function __construct(
        private HrSession $hrSession,
        private AdminPermissionService $permissions,
    ) {}

    public function compose(View $view): void
    {
        $userId = (int) session(HrSession::USER_ID, 0);
        $pendingCounts = $this->permissions->pendingCounts();

        $view->with([
            'adminName' => session(HrSession::NAME, 'Admin'),
            'pendingCounts' => array_merge([
                'leaves' => 0, 'documents' => 0, 'uploads' => 0, 'bank' => 0,
                'reimbursements' => 0, 'archive' => 0, 'incidents' => 0,
            ], $pendingCounts),
            'sidebarRestricted' => $this->permissions->isSidebarRestricted($userId),
            'sidebarCan' => fn (string $key): bool => $this->permissions->canAccessSidebar($userId, $key),
            'permCan' => fn (string $key): bool => $this->permissions->can($userId, $key),
        ]);
    }
}
