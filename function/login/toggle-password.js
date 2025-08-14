$('#togglePassword').click(function () {
  let passwordField = $('#password');
  let type = passwordField.attr('type') === 'password' ? 'text' : 'password';
  passwordField.attr('type', type);
  $('#eyeOpen').toggleClass('hidden');
  $('#eyeClosed').toggleClass('hidden');
});