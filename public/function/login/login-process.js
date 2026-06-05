(function () {
  var AUTH_BUTTON_LABELS = {
    login: "Login",
    timein: "Time In",
  };
  var AUTH_BTN_LOADING_HTML = '<span class="login-spinner"></span> Signing in...';
  var TIME_IN_LOCK_KEY = "hr_time_in_lock";

  function resolveAppBase() {
    var origin = window.location.origin || "";
    var pathBase = "";
    if (/\/login\/?$/i.test(window.location.pathname)) {
      pathBase = window.location.pathname.replace(/\/login\/?$/i, "");
    }
    var fromPage = (origin + pathBase).replace(/\/$/, "");
    var meta = ($('meta[name="app-url"]').attr("content") || "").replace(/\/$/, "");
    if (!meta) return fromPage;
    try {
      if (new URL(meta).origin === origin) return meta;
    } catch (e) {}
    return fromPage;
  }

  function resolveCsrfToken($form) {
    return (
      $('meta[name="csrf-token"]').attr("content") ||
      ($form && $form.find('input[name="_token"]').val()) ||
      $(".js-login-csrf").first().val() ||
      ""
    );
  }

  function clearClientCache() {
    try {
      localStorage.clear();
    } catch (e) {}
    try {
      sessionStorage.clear();
    } catch (e) {}

    if (!window.caches || typeof window.caches.keys !== "function") {
      return Promise.resolve();
    }

    return window.caches
      .keys()
      .then(function (names) {
        return Promise.all(
          names.map(function (name) {
            return window.caches.delete(name);
          })
        );
      })
      .catch(function () {
        // Ignore cache API errors; login redirect should still continue.
      });
  }

  function setLoading($btn, loading) {
    var action = ($btn.data("action") || "login").toString().toLowerCase();
    var defaultLabel = AUTH_BUTTON_LABELS[action] || AUTH_BUTTON_LABELS.login;
    if (loading) {
      $btn.prop("disabled", true).addClass("is-loading").html(AUTH_BTN_LOADING_HTML);
    } else {
      $btn.prop("disabled", false).removeClass("is-loading").html(defaultLabel);
    }
  }

  function getLockData() {
    try {
      var raw = localStorage.getItem(TIME_IN_LOCK_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function setLockData(data) {
    try {
      localStorage.setItem(TIME_IN_LOCK_KEY, JSON.stringify(data));
    } catch (e) {}
  }

  function removeLockData() {
    try {
      localStorage.removeItem(TIME_IN_LOCK_KEY);
    } catch (e) {}
  }

  function getTodayInManila() {
    return new Intl.DateTimeFormat("en-CA", {
      timeZone: "Asia/Manila",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    }).format(new Date());
  }

  function isTimeInLockedForEmail(email) {
    var lock = getLockData();
    if (!lock) return false;
    var today = getTodayInManila();
    if (lock.date !== today || !lock.email || lock.email !== email.toLowerCase()) {
      removeLockData();
      return false;
    }
    return true;
  }

  function applyTimeInButtonState(isLocked) {
    $(".js-auth-btn[data-action='timein']").each(function () {
      var $btn = $(this);
      if (isLocked) {
        $btn.prop("disabled", true).addClass("opacity-60 cursor-not-allowed").text("Time In Done");
      } else {
        $btn
          .prop("disabled", false)
          .removeClass("opacity-60 cursor-not-allowed is-loading")
          .text("Time In");
      }
    });
  }

  function refreshTimeInButtonStateByEmail(email) {
    var normalizedEmail = (email || "").toString().trim().toLowerCase();
    if (!normalizedEmail) {
      applyTimeInButtonState(false);
      return;
    }
    applyTimeInButtonState(isTimeInLockedForEmail(normalizedEmail));
  }

  $(document).on("input blur", "input[name=email]", function () {
    refreshTimeInButtonStateByEmail($(this).val());
  });

  $(document).on("click", ".js-auth-btn", function (e) {
    e.preventDefault();
    var $btn = $(this);
    if ($btn.prop("disabled") || $btn.hasClass("is-loading")) return;
    var action = ($btn.data("action") || "login").toString().toLowerCase();

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

    if (action === "timein" && isTimeInLockedForEmail(email)) {
      applyTimeInButtonState(true);
      Toastify({
        text: "Time In is already submitted for today. Try again after 12:00 AM.",
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#e3342f",
      }).showToast();
      return;
    }

    if (!email.toLowerCase().endsWith("@luntiands.com")) {
      Toastify({
        text: "Access is restricted to @luntiands.com email addresses only.",
        duration: 4000,
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

    var csrf = resolveCsrfToken($form);
    var base = resolveAppBase();
    if (!csrf) {
      clearTimeout(fallbackTimer);
      restore();
      Toastify({
        text: "Session expired. Please refresh the page and try again.",
        duration: 5000,
        gravity: "top",
        position: "right",
        backgroundColor: "#e3342f",
      }).showToast();
      return;
    }
    $.ajax({
      url: base + "/login/process",
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": csrf,
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
      xhrFields: { withCredentials: true },
      data: { email: email, password: password, action: action, _token: csrf },
      dataType: "json",
      cache: false,
      complete: function () {
        clearTimeout(fallbackTimer);
        runRestore();
      },
      success: function (response) {
        Toastify({
          text: response.message,
          duration: 3000,
          gravity: "top",
          position: "right",
          backgroundColor: response.status === "success" ? "#38a169" : "#e3342f",
        }).showToast();

        if (response.status === "success" && action === "timein") {
          setLockData({
            email: email.toLowerCase(),
            date: getTodayInManila(),
          });
          applyTimeInButtonState(true);
          return;
        }

        if (response.status === "success") {
          clearClientCache().finally(function () {
            var cacheBuster = response.cache_buster || Date.now().toString();
            setTimeout(function () {
              window.location.href = base + "/?cb=" + encodeURIComponent(cacheBuster);
            }, 800);
          });
        }
      },
      error: function (xhr) {
        var errorMsg = "Login failed. Please try again.";
        if (xhr.status === 419) {
          errorMsg = "Session expired. Refresh the page (F5) and log in again.";
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if (xhr.responseText) {
          try {
            var parsed = JSON.parse(xhr.responseText);
            if (parsed.message) errorMsg = parsed.message;
          } catch (e) {
            errorMsg = xhr.responseText.substring(0, 200);
          }
        }
        Toastify({
          text: errorMsg,
          duration: 5000,
          gravity: "top",
          position: "right",
          backgroundColor: "#e3342f",
        }).showToast();
      },
    });
  });

  refreshTimeInButtonStateByEmail($("input[name=email]").first().val());
})();
