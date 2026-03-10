<?php
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
$isActivityLog = ($currentPage === 'activity-log');
$unreadMessageCount = 0;

if (isset($conn) && $conn instanceof mysqli) {
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

<aside id="inventory-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#FA9800] text-white flex flex-col transform -translate-x-full transition-transform duration-200 md:translate-x-0">
    <div class="p-6 flex items-center gap-4 border-b border-white/20">
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

    <nav class="flex-1 p-4 space-y-1 text-sm">
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white<?php echo $isItem ? ' ' . $activeClass : ''; ?>">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
            </svg>
            <span>Item</span>
        </div>
        <div class="ml-10 space-y-1 mb-2">
            <a href="item.php?tab=add" class="block px-3 py-1.5 rounded-lg text-xs font-medium text-white/90 hover:bg-white/10 transition-colors<?php echo $isItemAdd ? ' ' . $activeClass : ''; ?>">Add Item</a>
            <a href="item.php?tab=list" class="block px-3 py-1.5 rounded-lg text-xs font-medium text-white/90 hover:bg-white/10 transition-colors<?php echo $isItemList ? ' ' . $activeClass : ''; ?>">List Item</a>
            <a href="item.php?tab=history" class="block px-3 py-1.5 rounded-lg text-xs font-medium text-white/90 hover:bg-white/10 transition-colors<?php echo $isItemHistory ? ' ' . $activeClass : ''; ?>">Item History</a>
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

    <div class="p-4 border-t border-white/20">
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
