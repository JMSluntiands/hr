<?php
// Workforce Management System sidebar - Time Keeping, Payslip, Report only
$adminName = $adminName ?? $_SESSION['name'] ?? 'Admin User';
$role      = $role ?? $_SESSION['role'] ?? 'admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$isIndex       = ($currentPage === 'index');
$isTimeKeeping = ($currentPage === 'time-keeping');
$isPayslip     = ($currentPage === 'payslip');
$isReport      = ($currentPage === 'report');

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
                    Workforce
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
                <div class="text-xs text-white/80">Workforce</div>
            </div>
        </div>
        <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-1 text-sm">
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isIndex ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="time-keeping" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isTimeKeeping ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Time Keeping</span>
            </a>
            <a href="payslip" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isPayslip ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
                <span>Payslip</span>
            </a>
            <a href="report" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isReport ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span>Report</span>
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
            <a href="../admin/module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Select Module (Workforce)</a>
        </div>
    </aside>
    <!-- Mobile sidebar backdrop -->
    <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 bg-black/40 hidden md:hidden"></div>
