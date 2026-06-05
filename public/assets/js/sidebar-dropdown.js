// Universal sidebar dropdown + mobile toggle (from legacy admin sidebar)
(function() {
    'use strict';
    if (window.__hrSidebarDropdownInitialized) return;
    window.__hrSidebarDropdownInitialized = true;

    function closeAllDropdowns() {
        document.querySelectorAll('[id$="-dropdown"]').forEach(function(dd) {
            dd.classList.add('hidden');
        });
        document.querySelectorAll('[id$="-arrow"]').forEach(function(arr) {
            arr.style.transform = 'rotate(0deg)';
        });
    }

    document.addEventListener('click', function(e) {
        const dropdownBtn = e.target.closest('[id$="-dropdown-btn"]');
        if (dropdownBtn) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const dropdownType = dropdownBtn.id.replace('-dropdown-btn', '');
            const dropdown = document.getElementById(dropdownType + '-dropdown');
            const arrow = document.getElementById(dropdownType + '-arrow');
            if (!dropdown) return;
            document.querySelectorAll('[id$="-dropdown"]').forEach(function(dd) {
                if (dd !== dropdown) dd.classList.add('hidden');
            });
            document.querySelectorAll('[id$="-arrow"]').forEach(function(arr) {
                if (arr !== arrow) arr.style.transform = 'rotate(0deg)';
            });
            const isHidden = dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden');
            if (arrow) arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            return;
        }
        if (!e.target.closest('.dropdown-container')) closeAllDropdowns();
    });
})();

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
                backdrop.classList.add('is-open');
                backdrop.style.display = 'block';
                backdrop.setAttribute('aria-hidden', 'false');
            }
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            if (backdrop) {
                backdrop.classList.remove('is-open');
                backdrop.style.display = 'none';
                backdrop.setAttribute('aria-hidden', 'true');
            }
        }
        toggleButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
            });
        });
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('-translate-x-full');
                closeSidebar();
            } else {
                sidebar.classList.add('-translate-x-full');
                closeSidebar();
            }
        });
    }
    function initAll() {
        initSidebarToggle({ sidebarId: 'admin-sidebar', backdropId: 'admin-sidebar-backdrop', toggleSelector: '[data-sidebar-toggle]' });
        initSidebarToggle({ sidebarId: 'employee-sidebar', backdropId: 'employee-sidebar-backdrop', toggleSelector: '[data-employee-sidebar-toggle]' });
        initSidebarToggle({ sidebarId: 'inventory-sidebar', backdropId: 'inventory-sidebar-backdrop', toggleSelector: '[data-inventory-sidebar-toggle]' });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
})();
