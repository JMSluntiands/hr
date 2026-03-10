// Universal sidebar dropdown functionality using event delegation
// This works across all pages and doesn't require DOMContentLoaded

(function() {
    'use strict';
    
    // Use event delegation on document level so it works even after navigation
    document.addEventListener('click', function(e) {
        // Only treat as "dropdown button click" when the click is ON the button itself
        // (not when clicking a link inside the dropdown - those should navigate)
        const dropdownBtn = e.target.closest('[id$="-dropdown-btn"]');
        
        if (dropdownBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const btnId = dropdownBtn.id;
            const dropdownType = btnId.replace('-dropdown-btn', '');
            const dropdown = document.getElementById(dropdownType + '-dropdown');
            const arrow = document.getElementById(dropdownType + '-arrow');
            
            if (!dropdown) return;
            
            // Close all other dropdowns
            const allDropdowns = document.querySelectorAll('[id$="-dropdown"]');
            const allArrows = document.querySelectorAll('[id$="-arrow"]');
            
            allDropdowns.forEach(function(dd) {
                if (dd !== dropdown) {
                    dd.classList.add('hidden');
                }
            });
            
            allArrows.forEach(function(arr) {
                if (arr !== arrow) {
                    arr.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current dropdown
            const isHidden = dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden');
            
            if (arrow) {
                arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const dropdownBtn = e.target.closest('[id$="-dropdown-btn"]');
        const dropdown = e.target.closest('[id$="-dropdown"]');
        const dropdownContainer = e.target.closest('.dropdown-container');
        
        if (!dropdownBtn && !dropdown && !dropdownContainer) {
            // Clicked outside, close all dropdowns
            const allDropdowns = document.querySelectorAll('[id$="-dropdown"]');
            const allArrows = document.querySelectorAll('[id$="-arrow"]');
            
            allDropdowns.forEach(function(dd) {
                dd.classList.add('hidden');
            });
            
            allArrows.forEach(function(arr) {
                arr.style.transform = 'rotate(0deg)';
            });
        }
    });
})();

// Mobile sidebar toggles (admin + inventory)
(function() {
    'use strict';

    function initSidebarToggle(options) {
        const sidebar = document.getElementById(options.sidebarId);
        if (!sidebar) return;

        const backdrop = options.backdropId ? document.getElementById(options.backdropId) : null;
        const toggleButtons = document.querySelectorAll(options.toggleSelector);

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            if (backdrop) {
                backdrop.classList.remove('hidden');
            }
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            if (backdrop) {
                backdrop.classList.add('hidden');
            }
        }

        toggleButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const isHidden = sidebar.classList.contains('-translate-x-full');
                if (isHidden) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', function() {
                closeSidebar();
            });
        }

        // Ensure proper state on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) { // md breakpoint
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) {
                    backdrop.classList.add('hidden');
                }
            } else {
                sidebar.classList.add('-translate-x-full');
                if (backdrop) {
                    backdrop.classList.add('hidden');
                }
            }
        });
    }

    function initAllSidebars() {
        initSidebarToggle({
            sidebarId: 'admin-sidebar',
            backdropId: 'admin-sidebar-backdrop',
            toggleSelector: '[data-sidebar-toggle]'
        });

        initSidebarToggle({
            sidebarId: 'inventory-sidebar',
            backdropId: 'inventory-sidebar-backdrop',
            toggleSelector: '[data-inventory-sidebar-toggle]'
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllSidebars);
    } else {
        initAllSidebars();
    }
})();
