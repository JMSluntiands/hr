$(".js-login-btn").on("click", function () {
  let card = $(this).closest(".rounded-2xl");
  let email = card.find("input[type=email]").val().trim();
  let password = card.find("input[type=password]").val().trim();

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

  $.ajax({
    url: "controller/login/login-process.php",
    method: "POST",
    data: { email: email, password: password },
    dataType: "json",
    success: function (response) {
      Toastify({
        text: response.message,
        duration: response.status === "locked" ? 5000 : 3000,
        gravity: "top",
        position: "right",
        backgroundColor: response.status === "success" ? "#38a169" : "#e3342f",
      }).showToast();

      if (response.status === "success") {
        let role = response.role || "";
        let target = "admin/index";
        if (role.toLowerCase() === "employee") target = "employee/index";
        setTimeout(function () { window.location.href = target; }, 800);
      } else if (response.status === "locked") {
        $("#loginLockedNotice, #loginLockedNoticeMobile").removeClass("hidden");
        $("#loginLockedEmail, #loginLockedEmailMobile").text(email);
        $("#requestUnlockEmail, #requestUnlockEmailMobile").val(email);
      }
    },
    error: function (xhr, status, error) {
      let errorMsg = xhr.responseText ? xhr.responseText : error;
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

