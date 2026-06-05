@php
    $path = request()->path();
    $route = request()->route()?->getName() ?? '';
    $hit = fn (...$needles) => collect($needles)->contains(fn ($n) => str_contains($path, $n) || ($route && str_contains((string) $route, $n)));

    $activeClass = 'bg-white/20';
    $itemTab = request()->query('tab', 'add');
    $isItemPage = $hit('inventory.items', '/inventory/items');
    $isItemAdd = $isItemPage && $itemTab === 'add';
    $isItemList = $isItemPage && $itemTab === 'list';
    $isItemHistory = $isItemPage && $itemTab === 'history';
    $isItemCollapsibleActive = $isItemAdd || $isItemHistory;
    $isInventory = $hit('inventory.dashboard') || ($path === 'inventory' || str_ends_with($path, 'inventory/index'));
    $isAllocation = $hit('inventory.allocation', '/inventory/allocation');
    $isReport = $hit('inventory.report', '/inventory/report');
    $isMessages = $hit('inventory.messages', '/inventory/messages');
    $isRequest = $hit('inventory.requests', '/inventory/requests');
    $isDecommissionRequest = $hit('inventory.decommission', '/inventory/decommission');
    $isActivityLog = $hit('inventory.activity-log', '/inventory/activity-log');

    $itemNavOpen = $isItemCollapsibleActive ? '' : ' hidden';
    $itemNavArrow = $isItemCollapsibleActive ? ' rotate-180' : '';
    $itemNavBtnActive = $isItemCollapsibleActive ? ' '.$activeClass : '';

    $badge = function ($n) {
        if ($n <= 0) return '';
        $t = $n > 99 ? '99+' : (string) $n;
        return '<span class="inline-flex min-w-[22px] h-[22px] items-center justify-center rounded-full bg-red-600 text-white text-xs font-semibold px-1">'.$t.'</span>';
    };

    $nav = $sidebarCan ?? fn () => true;
    $showItemDropdown = $nav('inventory_items_add') || $nav('inventory_items_history');
@endphp

<header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
    <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
            <span class="text-lg font-semibold">{{ strtoupper(substr($adminName ?? 'A', 0, 1)) }}</span>
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-medium">{{ $adminName ?? 'Admin' }}</span>
            <span class="text-[11px] text-white/80">Inventory Admin</span>
        </div>
    </div>
    <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20" data-inventory-sidebar-toggle aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</header>

<aside id="inventory-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
    <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
        <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
            <span class="text-2xl font-semibold text-white">{{ strtoupper(substr($adminName ?? 'A', 0, 1)) }}</span>
        </div>
        <div>
            <div class="font-medium text-sm text-white">{{ $adminName ?? 'Admin' }}</div>
            <div class="text-xs text-white/80">Inventory Admin</div>
        </div>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-1 text-sm">
        @if($nav('inventory_dashboard'))
        <a href="{{ route('inventory.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isInventory ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7V5a1 1 0 011-1h10a1 1 0 011 1v2m-1 4H7m10 4H7m13-8v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7"/></svg>
            <span>Inventory</span>
        </a>
        @endif

        @if($nav('inventory_items_list'))
        <a href="{{ route('inventory.items.index', ['tab' => 'list']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isItemList ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
            <span>List Item</span>
        </a>
        @endif

        @if($showItemDropdown)
        <div class="dropdown-container">
            <button type="button" id="inventory-item-dropdown-btn" class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-white transition-colors hover:bg-white/10{{ $itemNavBtnActive }}" aria-expanded="{{ $isItemCollapsibleActive ? 'true' : 'false' }}" aria-controls="inventory-item-dropdown">
                <svg class="h-5 w-5 shrink-0 text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                <span class="min-w-0 flex-1 pointer-events-none">Item</span>
                <svg id="inventory-item-arrow" class="h-4 w-4 shrink-0 text-white transition-transform pointer-events-none{{ $itemNavArrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="inventory-item-dropdown" class="mb-2 ml-10 space-y-1{{ $itemNavOpen }}">
                @if($nav('inventory_items_add'))
                <a href="{{ route('inventory.items.index', ['tab' => 'add']) }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10{{ $isItemAdd ? ' '.$activeClass : '' }}">Add Item</a>
                @endif
                @if($nav('inventory_items_history'))
                <a href="{{ route('inventory.items.index', ['tab' => 'history']) }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10{{ $isItemHistory ? ' '.$activeClass : '' }}">Item History</a>
                @endif
            </div>
        </div>
        @endif

        @if($nav('inventory_allocation'))
        <a href="{{ route('inventory.allocation.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isAllocation ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-9 4h10m-9 4h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
            <span>Allocation</span>
        </a>
        @endif

        @if($nav('inventory_nav_requests'))
        <a href="{{ route('inventory.requests.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isRequest ? ' '.$activeClass : '' }}">
            <span class="flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Request</span>
            </span>
            {!! $badge($pendingRequestCount ?? 0) !!}
        </a>
        @endif

        @if($nav('inventory_nav_decommission'))
        <a href="{{ route('inventory.decommission.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isDecommissionRequest ? ' '.$activeClass : '' }}">
            <span class="flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span>Decommission</span>
            </span>
            {!! $badge($pendingDecommissionCount ?? 0) !!}
        </a>
        @endif

        @if($nav('inventory_report'))
        <a href="{{ route('inventory.report.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isReport ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span>Report</span>
        </a>
        @endif

        @if($nav('inventory_messages'))
        <a href="{{ route('inventory.messages.index') }}" class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isMessages ? ' '.$activeClass : '' }}">
            <span class="flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 7h12a2 2 0 002-2V7a2 2 0 00-2-2h-2l-2-2H10L8 5H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Messages</span>
            </span>
            {!! $badge($unreadMessageCount ?? 0) !!}
        </a>

        <a href="{{ route('inventory.activity-log.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors{{ $isActivityLog ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6"/></svg>
            <span>Activity Log</span>
        </a>
        @endif
    </nav>

    <div class="shrink-0 border-t border-white/20 p-4">
        <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
            <span>Role</span>
            <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium">{{ $role ?? 'admin' }}</span>
        </div>
        <form method="post" action="{{ route('logout') }}" class="inline">@csrf
            <button type="submit" class="text-xs font-medium text-white/80 hover:text-white">Logout</button>
        </form>
        <a href="{{ route('admin.module-select') }}" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
    </div>
</aside>
