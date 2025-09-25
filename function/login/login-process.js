$("#loginButton").click(function () {
  let email = $("#email").val().trim();
  let password = $("#password").val().trim();

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
        backgroundColor: response.status === "success" ? "#38a169" : "#e3342f"
      }).showToast();

      if (response.status === "success") {
        // Role → Redirect Map
        let roleRedirects = {
          "LBS": "admin/index",
          "BPH": "admin/index",
          "B1": "admin/index",
          "BLUINQ": "admin/index",
          "LUNTIAN": "admin/index",
          "Staff": "subadmin/index",
          "Checker": "subadmin/index"
        };

        // Check kung may redirect para sa role
        if (roleRedirects[response.role]) {
          setTimeout(() => {
            window.location.href = roleRedirects[response.role];
          }, 1000); // delay para makita muna yung toast
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
