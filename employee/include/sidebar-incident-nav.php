<?php
$currentNavIr = basename($_SERVER['PHP_SELF'], '.php');
$incidentPages = ['incident-report', 'incident-report-add', 'incident-report-list'];
$incidentNavOpen = in_array($currentNavIr, $incidentPages, true) ? '' : ' hidden';
$incidentNavArrow = in_array($currentNavIr, $incidentPages, true) ? ' rotate-180' : '';
$incidentNavBtnActive = in_array($currentNavIr, $incidentPages, true) ? ' bg-white/20' : '';
$isIrAdd = ($currentNavIr === 'incident-report-add');
$isIrList = ($currentNavIr === 'incident-report-list');
?>
<div class="dropdown-container relative z-10">
    <button type="button" id="incident-report-dropdown-btn" class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-white transition-colors hover:bg-white/10<?php echo $incidentNavBtnActive; ?>" aria-expanded="<?php echo $incidentNavOpen === '' ? 'true' : 'false'; ?>" aria-controls="incident-report-dropdown">
        <svg class="h-5 w-5 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="min-w-0 flex-1">Incident Report</span>
        <svg id="incident-report-arrow" class="h-4 w-4 shrink-0 text-white transition-transform<?php echo $incidentNavArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div id="incident-report-dropdown" class="mb-2 ml-10 space-y-1<?php echo $incidentNavOpen; ?>" role="region" aria-label="Incident report submenu">
        <a href="incident-report-add.php" class="js-side-link block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isIrAdd ? ' bg-white/20' : ''; ?>">Add incident</a>
        <a href="incident-report-list.php" class="js-side-link block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 transition-colors hover:bg-white/10<?php echo $isIrList ? ' bg-white/20' : ''; ?>">List of incident</a>
    </div>
</div>
