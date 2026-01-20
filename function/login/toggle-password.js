$(document).ready(function() {
  $("#togglePassword").click(function() {
    const passwordInput = $("#password");
    const eyeOpen = $("#eyeOpen");
    const eyeClosed = $("#eyeClosed");
    
    if (passwordInput.attr("type") === "password") {
      passwordInput.attr("type", "text");
      eyeOpen.addClass("hidden");
      eyeClosed.removeClass("hidden");
    } else {
      passwordInput.attr("type", "password");
      eyeOpen.removeClass("hidden");
      eyeClosed.addClass("hidden");
    }
  });
});
