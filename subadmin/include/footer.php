<!-- Footer -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/feather.min.js"></script>
<script src="../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../assets/plugins/apexchart/apexcharts.min.js"></script>
<script src="../assets/plugins/apexchart/chart-data.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables/datatables.min.js"></script>
<script src="../assets/plugins/toastr/toastr.min.js"></script>
<script src="../assets/js/script.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
  $(document).ready(function () {
    var path = window.location.pathname.split("/").pop();
    $(".sidebar-menu li").each(function () {
      var link = $(this).find("a");
      var submenu = $(this).find("ul.removeActive");
      var submenuLinks = submenu.find("a");
      if (link.attr("href") === path || submenuLinks.filter("[href='" + path + "']").length) {
        link.addClass("active subdrop");
        $(this).addClass("active");
        submenu.show();
        submenuLinks.filter("[href='" + path + "']").addClass("active");
      } else {
        link.removeClass("active subdrop");
        $(this).removeClass("active");
        submenu.hide();
        submenuLinks.removeClass("active");
      }
    });
    $(".removeActive li").removeClass("active");

    // Dark Mode Preference Load
    if (localStorage.getItem('darkMode') === 'enabled') {
      $('body').addClass('dark-mode');
      $('#darkModeToggle i').removeClass('fa-moon').addClass('fa-sun');
    }
    $('#darkModeToggle').click(function () {
      $('body').toggleClass('dark-mode');
      if ($('body').hasClass('dark-mode')) {
        localStorage.setItem('darkMode', 'enabled');
        $('#darkModeToggle i').removeClass('fa-moon').addClass('fa-sun');
      } else {
        localStorage.setItem('darkMode', 'disabled');
        $('#darkModeToggle i').removeClass('fa-sun').addClass('fa-moon');
      }
    });
  });

  function loadJobCounts() {
    $.ajax({
      url: "../controller/sidebar/loadCount.php",
      method: "GET",
      dataType: "json",
      success: function(res) {
        // List Count
        if (res.listCount > 0) {
          $("#listCountBadge").text(res.listCount).show();
        } else {
          $("#listCountBadge").hide();
        }

        // Review Count
        if (res.reviewCount > 0) {
          $("#reviewCountBadge").text(res.reviewCount).show();
        } else {
          $("#reviewCountBadge").hide();
        }

        // Mailbox Count
        if (res.mailCount > 0) {
          $("#mailCountBadge").text(res.mailCount).show();
        } else {
          $("#mailCountBadge").hide();
        }
      }
    });
  }

  // Load agad
  loadJobCounts();
  // Auto refresh every 30s
  setInterval(loadJobCounts, 30000);

    let logoutTimer;
  const logoutAfter = 30 * 60 * 1000; // 30 mins = 1800s

  function startTimer() {
    logoutTimer = setTimeout(autoLogout, logoutAfter);
  }

  function resetTimer() {
    clearTimeout(logoutTimer);
    startTimer();
  }

  function autoLogout() {
    $.ajax({
      url: "../controller/autologout.php",
      type: "POST",
      dataType: "json",
      success: function (res) {
        if (res.status === "success") {
          toastr.warning(res.message, "Session Expired");
          setTimeout(() => {
            window.location.href = "../index";
          }, 1500);
        }
      },
      error: function () {
        toastr.error("Logout request failed.", "Error");
      }
    });
  }

  // ✅ Start timer on page load
  $(document).ready(function() {
    startTimer();

    // ✅ Reset timer whenever user is active
    $(document).on("mousemove keypress click scroll", resetTimer);
  });

</script>