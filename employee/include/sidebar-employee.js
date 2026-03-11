// Employee mobile sidebar toggle (shared across all employee pages)
(function () {
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
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmployeeSidebar);
  } else {
    initEmployeeSidebar();
  }
})();

