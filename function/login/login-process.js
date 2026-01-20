$("#loginButton").click(function () {
  let email = $("#email").val().trim();
  let password = $("#password").val().trim();

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
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: response.status === "success" ? "#38a169" : "#e3342f",
      }).showToast();

      if (response.status === "success") {
        // Simple role-based redirect
        let role = response.role || "";
        let target = "admin/index";

        if (role.toLowerCase() === "employee") {
          target = "employee/index";
        }

        setTimeout(function () {
          window.location.href = target;
        }, 800);
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

