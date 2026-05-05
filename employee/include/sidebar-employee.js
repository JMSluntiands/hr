// Employee mobile sidebar toggle (shared across all employee pages)
(function () {
  function closeSidebarDropdowns(sidebar) {
    sidebar.querySelectorAll('[id$="-dropdown"]').forEach(function (dd) {
      dd.classList.add('hidden');
    });
    sidebar.querySelectorAll('[id$="-arrow"]').forEach(function (arr) {
      arr.style.transform = 'rotate(0deg)';
    });
  }

  function initEmployeeSidebar() {
    var sidebar = document.getElementById('employee-sidebar');
    if (!sidebar) return;

    var backdrop = document.getElementById('employee-sidebar-backdrop');
    var toggleButtons = document.querySelectorAll('[data-employee-sidebar-toggle]');

    function openSidebar() {
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
      if (backdrop) backdrop.classList.remove('hidden');
    }

    function closeSidebar() {
      sidebar.classList.add('-translate-x-full');
      sidebar.classList.remove('translate-x-0');
      if (backdrop) backdrop.classList.add('hidden');
    }

    toggleButtons.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var isHidden = sidebar.classList.contains('-translate-x-full');
        if (isHidden) {
          openSidebar();
        } else {
          closeSidebar();
        }
      });
    });

    // Close sidebar when clicking any sidebar link on mobile
    var sideLinks = document.querySelectorAll('.js-side-link');
    sideLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 768) {
          closeSidebar();
        }
      });
    });

    if (backdrop) {
      backdrop.addEventListener('click', function () {
        closeSidebar();
      });
    }

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768) {
        sidebar.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
      } else {
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
      }
    });

    // Toggle dropdown on capture so we win over other document/jQuery handlers and avoid double-handling
    sidebar.addEventListener(
      'click',
      function (e) {
        var dropdownBtn = e.target.closest('button[id$="-dropdown-btn"]');
        if (!dropdownBtn || !sidebar.contains(dropdownBtn)) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        var btnId = dropdownBtn.id;
        var dropdownType = btnId.replace('-dropdown-btn', '');
        var dropdown = document.getElementById(dropdownType + '-dropdown');
        var arrow = document.getElementById(dropdownType + '-arrow');
        if (!dropdown) return;
        sidebar.querySelectorAll('[id$="-dropdown"]').forEach(function (dd) {
          if (dd !== dropdown) dd.classList.add('hidden');
        });
        sidebar.querySelectorAll('[id$="-arrow"]').forEach(function (arr) {
          if (arr !== arrow) arr.style.transform = 'rotate(0deg)';
        });
        var isHidden = dropdown.classList.contains('hidden');
        dropdown.classList.toggle('hidden');
        if (arrow) {
          arrow.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        }
      },
      true
    );
  }

  // Close incident (and any) sidebar dropdowns when clicking outside sidebar or on other sidebar rows
  document.addEventListener('click', function (e) {
    var sidebar = document.getElementById('employee-sidebar');
    if (!sidebar) return;
    if (!sidebar.contains(e.target)) {
      closeSidebarDropdowns(sidebar);
      return;
    }
    if (e.target.closest('.dropdown-container')) return;
    closeSidebarDropdowns(sidebar);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmployeeSidebar);
  } else {
    initEmployeeSidebar();
  }
})();

