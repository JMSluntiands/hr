<?php
/**
 * Injects custom scrollbar styles for orange sidebars (admin / employee / inventory).
 * Safe to require_once from any layout; outputs at most once per request.
 */
if (!empty($GLOBALS['_hr_sidebar_scroll_injected'])) {
    return;
}
$GLOBALS['_hr_sidebar_scroll_injected'] = true;

echo <<<'CSS'
<style id="hr-sidebar-scrollbar">
/* Employee main can paint after aside in DOM; keep sidebar above in-flow content for hit-testing */
#employee-sidebar {
  z-index: 50;
}
#admin-sidebar > nav,
#employee-sidebar > nav,
#inventory-sidebar > nav {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.38) rgba(255, 255, 255, 0.06);
  -webkit-overflow-scrolling: touch;
}
#admin-sidebar > nav::-webkit-scrollbar,
#employee-sidebar > nav::-webkit-scrollbar,
#inventory-sidebar > nav::-webkit-scrollbar {
  width: 6px;
}
#admin-sidebar > nav::-webkit-scrollbar-track,
#employee-sidebar > nav::-webkit-scrollbar-track,
#inventory-sidebar > nav::-webkit-scrollbar-track {
  margin: 4px 0;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 999px;
}
#admin-sidebar > nav::-webkit-scrollbar-thumb,
#employee-sidebar > nav::-webkit-scrollbar-thumb,
#inventory-sidebar > nav::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.42), rgba(255, 255, 255, 0.22));
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.15);
}
#admin-sidebar > nav::-webkit-scrollbar-thumb:hover,
#employee-sidebar > nav::-webkit-scrollbar-thumb:hover,
#inventory-sidebar > nav::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.58), rgba(255, 255, 255, 0.35));
}
</style>
CSS;
