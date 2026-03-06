(function () {
  var LOGIN_BTN_HTML = "Login";
  var LOGIN_BTN_LOADING_HTML = '<span class="login-spinner"></span> Logging in...';

  function setLoading($btn, loading) {
    if (loading) {
      $btn.prop("disabled", true).addClass("is-loading").html(LOGIN_BTN_LOADING_HTML);
    } else {
      $btn.prop("disabled", false).removeClass("is-loading").html(LOGIN_BTN_HTML);
    }
  }

  $(document).on("click", ".js-login-btn", function (e) {
    e.preventDefault();
    var $btn = $(this);
    if ($btn.prop("disabled") || $btn.hasClass("is-loading")) return;

    // Kunin ang values mula sa form na naglalaman ng button na na-click
    var $form = $btn.closest("form.js-login-form");
    var email = ($form.find("input[name=email]").val() || "").toString().trim();
    var password = ($form.find("input[name=password]").val() || "").toString().trim();

    if (!email || !password) {
      Toastify({
        text: "Please fill in all fields",
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "#e3342f",
      }).showToast();
      return;
    }

    setLoading($btn, true);
    var startTime = Date.now();
    var restore = function () {
      setLoading($btn, false);
    };
    var minDelay = 400;
    var runRestore = function () {
      var elapsed = Date.now() - startTime;
      var remaining = Math.max(minDelay - elapsed, 0);
      setTimeout(restore, remaining);
    };

    // Fallback: kung hindi na-restore after 15s (e.g. nawala connection), i-restore pa rin
    var fallbackTimer = setTimeout(restore, 15000);

    $.ajax({
      url: "controller/login/login-process.php",
      method: "POST",
      data: { email: email, password: password },
      dataType: "json",
      complete: function () {
        clearTimeout(fallbackTimer);
        runRestore();
      },
      success: function (response) {
        Toastify({
          text: response.message,
          duration: response.status === "locked" ? 5000 : 3000,
          gravity: "top",
          position: "right",
          backgroundColor: response.status === "success" ? "#38a169" : "#e3342f",
        }).showToast();

        if (response.status === "success") {
          setTimeout(function () { window.location.href = "index.php"; }, 800);
          return;
        }
        if (response.status === "locked") {
          $("#loginLockedNotice, #loginLockedNoticeMobile").removeClass("hidden");
          $("#loginLockedEmail, #loginLockedEmailMobile").text(email);
          $("#requestUnlockEmail, #requestUnlockEmailMobile").val(email);
        }
      },
      error: function (xhr, status, error) {
        var errorMsg = xhr.responseText ? xhr.responseText : error;
        Toastify({
          text: "Backend Error: " + errorMsg,
          duration: 4000,
          gravity: "top",
          position: "right",
          backgroundColor: "#e3342f",
        }).showToast();
      },
    });
  });
})();

function doRequestUnlock() {
  let email = $("#requestUnlockEmail").val().trim() || $("#requestUnlockEmailMobile").val().trim();
  if (!email) {
    Toastify({ text: "Email is required", duration: 3000, gravity: "top", position: "right", backgroundColor: "#e3342f" }).showToast();
    return;
  }
  $.ajax({
    url: "controller/login/request-unlock.php",
    method: "POST",
    data: { email: email },
    dataType: "json",
    success: function (r) {
      Toastify({
        text: r.message,
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: r.status === "success" ? "#38a169" : "#e3342f",
      }).showToast();
      if (r.status === "success") $("#loginLockedNotice, #loginLockedNoticeMobile").addClass("hidden");
    },
    error: function (xhr) {
      Toastify({
        text: "Request failed. Please try again.",
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: "#e3342f",
      }).showToast();
    },
  });
}
$("#requestUnlockBtn, #requestUnlockBtnMobile").on("click", doRequestUnlock);

