$(document).ready(function() {
  $(document).on("click", "[id^='togglePassword']", function() {
    const $btn = $(this);
    const $form = $btn.closest("form");
    const passwordInput = $form.find("input[name='password']");
    const $eyeOpen = $btn.children("svg").first();
    const $eyeClosed = $btn.children("svg").last();

    if (passwordInput.attr("type") === "password") {
      passwordInput.attr("type", "text");
      $eyeOpen.addClass("hidden");
      $eyeClosed.removeClass("hidden");
    } else {
      passwordInput.attr("type", "password");
      $eyeOpen.removeClass("hidden");
      $eyeClosed.addClass("hidden");
    }
  });
});
