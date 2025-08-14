$("#loginButton").click(function () {
  let email = $("#email").val().trim();
  let password = $("#password").val().trim();

  $.ajax({
    url: "controller/login/login-process",
    method: "POST",
    data: { email: email, password: password },
    dataType: "json",
    success: function (response) {
      Toastify({
        text: response.message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: response.status === "success" ? "#38a169" : "#e3342f"
      }).showToast();

      if (response.status === "success") {
        if (response.role === "admin") {
          window.location.href = "admin_dashboard.php";
        } else if (response.role === "user") {
          window.location.href = "user_dashboard.php";
        } else if (response.role === "super admin") {
          window.location.href = "super_admin_dashboard.php";
        }
      }
    },
    error: function (xhr, status, error) {
      let errorMsg = xhr.responseText ? xhr.responseText : error;
      Toastify({
        text: "Backend Error: " + errorMsg,
        duration: 4000,
        gravity: "top",
        position: "right",
        backgroundColor: "#e3342f"
      }).showToast();
    }
  });

});