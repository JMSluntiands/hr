<?php
$currentInvNav = basename($_SERVER['PHP_SELF'], '.php');
$invViewRaw = (string)($_GET['view'] ?? 'list');
$invViewNav = in_array($invViewRaw, ['list', 'request'], true) ? $invViewRaw : 'list';
$isInventoryNavPage = ($currentInvNav === 'inventory');
$inventoryNavOpen = $isInventoryNavPage ? '' : ' hidden';
$inventoryNavArrow = $isInventoryNavPage ? ' rotate-180' : '';
$inventoryNavBtnActive = $isInventoryNavPage ? ' bg-white/20' : '';
$isInvNavList = $isInventoryNavPage && $invViewNav === 'list';
$isInvNavRequest = $isInventoryNavPage && $invViewNav === 'request';
?>
<div class="dropdown-container relative z-10">
    <button type="button" id="employee-inv-dropdown-btn" class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-white transition-colors hover:bg-white/10<?php echo $inventoryNavBtnActive; ?>" aria-expanded="<?php echo $inventoryNavOpen === '' ? 'true' : 'false'; ?>" aria-controls="employee-inv-dropdown">
        <svg class="h-5 w-5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2h-3V3H9v2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4m4 0v2m8-2v2" />
        </svg>
        <span class="min-w-0 flex-1">My Inventory</span>
        <svg id="employee-inv-arrow" class="h-4 w-4 shrink-0 text-white transition-transform<?php echo $inventoryNavArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div id="employee-inv-dropdown" class="mb-2 ml-10 space-y-1<?php echo $inventoryNavOpen; ?>" role="region" aria-label="My inventory submenu">
        <a href="inventory.php?view=list" data-url="inventory.php?view=list" class="js-side-link block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isInvNavList ? ' bg-white/20' : ''; ?>">List of my item</a>
        <a href="inventory.php?view=request" data-url="inventory.php?view=request" class="js-side-link block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isInvNavRequest ? ' bg-white/20' : ''; ?>">Request item</a>
    </div>
</div>
