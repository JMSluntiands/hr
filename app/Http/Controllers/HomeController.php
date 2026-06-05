<?php

namespace App\Http\Controllers;

use App\Services\AdminPermissionService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    public function __construct(
        private HrSession $hrSession,
        private AdminPermissionService $permissions,
    ) {}

    public function __invoke(): RedirectResponse
    {
        if (! $this->hrSession->isLoggedIn()) {
            return redirect()->route('login');
        }

        if ($this->hrSession->role() === 'admin') {
            $module = $this->hrSession->adminModule();
            $userId = (int) $this->hrSession->userId();

            return match ($module) {
                'inventory' => $this->redirectInventoryHome($userId),
                'workforce' => redirect()->route('admin.workforce.building'),
                'permission' => redirect('/permission/index.php'),
                'hr' => redirect()->route('admin.dashboard'),
                default => redirect()->route('admin.module-select'),
            };
        }

        $module = $this->hrSession->employeeModule();

        return match ($module) {
            'timekeeping' => redirect()->route('employee.timekeeping.building'),
            'profile' => redirect()->route('employee.profile.show'),
            default => redirect()->route('employee.module-select'),
        };
    }

    private function redirectInventoryHome(int $userId): RedirectResponse
    {
        $candidates = [
            ['inventory_dashboard', fn () => route('inventory.dashboard')],
            ['inventory_items_list', fn () => route('inventory.items.index', ['tab' => 'list'])],
            ['inventory_items_add', fn () => route('inventory.items.index', ['tab' => 'add'])],
            ['inventory_items_history', fn () => route('inventory.items.index', ['tab' => 'history'])],
            ['inventory_allocation', fn () => route('inventory.allocation.index')],
            ['inventory_nav_requests', fn () => route('inventory.requests.index')],
            ['inventory_nav_decommission', fn () => route('inventory.decommission.index')],
            ['inventory_report', fn () => route('inventory.report.index')],
            ['inventory_messages', fn () => route('inventory.messages.index')],
            ['inventory_activity_log', fn () => route('inventory.activity-log.index')],
        ];

        foreach ($candidates as [$key, $url]) {
            if ($this->permissions->canAccessSidebar($userId, $key)) {
                return redirect($url());
            }
        }

        return redirect()->route('admin.module-select')
            ->with('error', 'Walang inventory page na naka-assign sa account mo.');
    }
}
