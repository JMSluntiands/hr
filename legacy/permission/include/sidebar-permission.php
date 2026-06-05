<?php
// Permission module sidebar
$adminName = $adminName ?? $_SESSION['name'] ?? 'Admin User';
$role      = $role ?? $_SESSION['role'] ?? 'admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$isIndex = ($currentPage === 'index');
$isPermissions = ($currentPage === 'index');

$activeClass = 'bg-white/20';

require_once dirname(__DIR__, 2) . '/include/sidebar-scrollbar-once.php';
?>
    <!-- Mobile Top Bar -->
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
                    Permission
                </span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <!-- Sidebar - desktop fixed, mobile slide-over -->
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($adminName); ?></div>
                <div class="text-xs text-white/80">Permission</div>
            </div>
        </div>
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-1 text-sm">
            <a href="/permission" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isPermissions ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Department Permissions</span>
            </a>
        </nav>
        <div class="shrink-0 border-t border-white/20 p-4">
            <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
                <span>Role</span>
                <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
            <a href="/logout" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="/admin/module-select" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
    </aside>
    <!-- Mobile sidebar backdrop -->
    <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 bg-black/40 hidden md:hidden"></div>
