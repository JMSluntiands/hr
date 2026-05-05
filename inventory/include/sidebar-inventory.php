<?php
require_once __DIR__ . '/../database/setup_inventory_item_requests_table.php';

$adminName = $adminName ?? $_SESSION['name'] ?? 'Admin User';
$role = $role ?? $_SESSION['role'] ?? 'admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$itemTab = (string)($_GET['tab'] ?? 'add');

$isItem = ($currentPage === 'item');
$isItemAdd = $isItem && $itemTab === 'add';
$isItemList = $isItem && $itemTab === 'list';
$isItemHistory = $isItem && $itemTab === 'history';
$isInventory = ($currentPage === 'inventory' || $currentPage === 'index');
$isAllocation = ($currentPage === 'allocation');
$isReport = ($currentPage === 'report');
$isMessages = ($currentPage === 'messages');
$isRequest = ($currentPage === 'request');
$isActivityLog = ($currentPage === 'activity-log');
$unreadMessageCount = 0;
$pendingRequestCount = 0;

if (isset($conn) && $conn instanceof mysqli) {
    ensureInventoryItemRequestsTable($conn);

    $pendingReqResult = $conn->query("SELECT COUNT(*) AS c FROM inventory_item_requests WHERE status = 'pending'");
    if ($pendingReqResult && $pr = $pendingReqResult->fetch_assoc()) {
        $pendingRequestCount = (int)($pr['c'] ?? 0);
    }

    $unreadResult = $conn->query("
        SELECT COUNT(*) AS total_unread
        FROM inventory_item_allocations
        WHERE employee_appeal IS NOT NULL
          AND TRIM(employee_appeal) <> ''
          AND admin_viewed_at IS NULL
    ");
    if ($unreadResult && $row = $unreadResult->fetch_assoc()) {
        $unreadMessageCount = (int)($row['total_unread'] ?? 0);
    }
}
$activeClass = 'bg-white/20';
$itemNavOpen = $isItem ? '' : ' hidden';
$itemNavArrow = $isItem ? ' rotate-180' : '';
$itemNavBtnActive = $isItem ? ' ' . $activeClass : '';

require_once dirname(__DIR__, 2) . '/include/sidebar-scrollbar-once.php';
?>
<!-- Mobile Top Bar for Inventory -->
<header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
    <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
            <span class="text-lg font-semibold">
                <?php echo strtoupper(substr($adminName, 0, 1)); ?>
            </span>
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-medium">
                <?php echo htmlspecialchars($adminName); ?>
            </span>
            <span class="text-[11px] text-white/80">
                Inventory Admin
            </span>
        </div>
    </div>
    <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-inventory-sidebar-toggle>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</header>

<aside id="inventory-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
    <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
        <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
            <span class="text-2xl font-semibold text-white">
                <?php echo strtoupper(substr($adminName, 0, 1)); ?>
            </span>
        </div>
        <div>
            <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($adminName); ?></div>
            <div class="text-xs text-white/80">Inventory Admin</div>
        </div>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-1 text-sm">
        <div class="dropdown-container">
            <button type="button" id="inventory-item-dropdown-btn" class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-white transition-colors hover:bg-white/10<?php echo $itemNavBtnActive; ?>" aria-expanded="<?php echo $itemNavOpen === '' ? 'true' : 'false'; ?>" aria-controls="inventory-item-dropdown">
                <svg class="h-5 w-5 shrink-0 text-white pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                </svg>
                <span class="min-w-0 flex-1 pointer-events-none">Item</span>
                <svg id="inventory-item-arrow" class="h-4 w-4 shrink-0 text-white transition-transform pointer-events-none<?php echo $itemNavArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="inventory-item-dropdown" class="mb-2 ml-10 space-y-1<?php echo $itemNavOpen; ?>" role="region" aria-label="Item submenu">
                <a href="item.php?tab=add" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isItemAdd ? ' ' . $activeClass : ''; ?>">Add Item</a>
                <a href="item.php?tab=list" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isItemList ? ' ' . $activeClass : ''; ?>">List Item</a>
                <a href="item.php?tab=history" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isItemHistory ? ' ' . $activeClass : ''; ?>">Item History</a>
            </div>
        </div>

        <a href="inventory.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isInventory ? ' ' . $activeClass : ''; ?>">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7V5a1 1 0 011-1h10a1 1 0 011 1v2m-1 4H7m10 4H7m13-8v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7" />
            </svg>
            <span>Inventory</span>
        </a>

        <a href="allocation.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isAllocation ? ' ' . $activeClass : ''; ?>">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-9 4h10m-9 4h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
            </svg>
            <span>Allocation</span>
        </a>

        <a href="request.php" class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isRequest ? ' ' . $activeClass : ''; ?>">
            <span class="flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Request</span>
            </span>
            <?php if ($pendingRequestCount > 0): ?>
                <span class="inline-flex min-w-[22px] h-[22px] items-center justify-center rounded-full bg-red-600 text-white text-xs font-semibold px-1">
                    <?php echo $pendingRequestCount > 99 ? '99+' : $pendingRequestCount; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="report.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isReport ? ' ' . $activeClass : ''; ?>">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>Report</span>
        </a>

        <a href="messages.php" class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isMessages ? ' ' . $activeClass : ''; ?>">
            <span class="flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 7h12a2 2 0 002-2V7a2 2 0 00-2-2h-2l-2-2H10L8 5H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Messages</span>
            </span>
            <?php if ($unreadMessageCount > 0): ?>
                <span class="inline-flex min-w-[22px] h-[22px] items-center justify-center rounded-full bg-red-600 text-white text-xs font-semibold px-1">
                    <?php echo $unreadMessageCount > 99 ? '99+' : $unreadMessageCount; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="activity-log.php" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isActivityLog ? ' ' . $activeClass : ''; ?>">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6" />
            </svg>
            <span>Activity Log</span>
        </a>
    </nav>

    <div class="shrink-0 border-t border-white/20 p-4">
        <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
            <span>Role</span>
            <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium">
                <?php echo htmlspecialchars($role); ?>
            </span>
        </div>
        <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        <a href="../admin/module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
    </div>
</aside>
