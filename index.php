<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Luntian</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { inter: ['Inter', 'sans-serif'] },
          colors: {
            luntianOrange: '#fa9b05',
            luntianDark: '#1e1e2d'
          }
        }
      }
    }
  </script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="font-inter h-screen flex">

  <!-- Left Section -->
  <div class="w-1/2 bg-luntianDark flex flex-col justify-center items-center relative">
    <img src="img/logo-light.png" alt="Logo" class="absolute top-6 left-6 w-[200px]">
    <img src="img/login-model.svg" alt="Illustration" class="max-w-sm">
  </div>

  <!-- Right Section -->
  <div class="w-1/2 bg-luntianOrange flex flex-col justify-center items-center relative">
    <!-- Language Selector -->
    <div class="absolute top-6 right-6">
      <select id="languageSelect" class="bg-gray-900 text-white px-3 py-2 rounded shadow">
        <option value="en">English</option>
        <option value="fil">Filipino</option>
        <option value="jp">日本語</option>
      </select>
    </div>

    <!-- Login Form -->
    <div class="bg-gray-900 text-white p-8 rounded-xl shadow-lg w-full max-w-md">
      <h2 class="text-2xl font-bold text-center mb-6" id="loginTitle">Login</h2>
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium" id="emailLabel">Email</label>
        <input id="email" type="email" placeholder="Enter your email"
          class="mt-1 w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-luntianOrange text-gray-900">
      </div>
      <div class="mb-4 relative">
        <label for="password" class="block text-sm font-medium" id="passwordLabel">Password</label>
        <input id="password" type="password" placeholder="Enter your password"
          class="mt-1 w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-luntianOrange text-gray-900 pr-10">
        <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center mt-7">
          <!-- Eye open -->
          <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#555" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <!-- Eye closed -->
          <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#555" class="w-6 h-6 hidden">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
          </svg>
        </button>
      </div>
      <div class="flex justify-end mb-4">
        <a href="#" class="text-sm text-luntianOrange hover:underline" id="forgotLabel">Forgot password?</a>
      </div>
      <button class="w-full bg-luntianOrange hover:bg-[#e38b03] text-white font-medium p-3 rounded-lg" id="loginButton">Login</button>
    </div>
  </div>

  <script>
    // Show/hide password
    $('#togglePassword').click(function () {
      let passwordField = $('#password');
      let type = passwordField.attr('type') === 'password' ? 'text' : 'password';
      passwordField.attr('type', type);
      $('#eyeOpen').toggleClass('hidden');
      $('#eyeClosed').toggleClass('hidden');
    });

    // Language translations
    const translations = {
      en: {
        loginTitle: 'Login',
        emailLabel: 'Email',
        passwordLabel: 'Password',
        forgotLabel: 'Forgot password?',
        loginButton: 'Login'
      },
      fil: {
        loginTitle: 'Mag-login',
        emailLabel: 'Email',
        passwordLabel: 'Password',
        forgotLabel: 'Nakalimutan ang password?',
        loginButton: 'Mag-login'
      },
      jp: {
        loginTitle: 'ログイン',
        emailLabel: 'メールアドレス',
        passwordLabel: 'パスワード',
        forgotLabel: 'パスワードをお忘れですか？',
        loginButton: 'ログイン'
      }
    };

    $('#languageSelect').on('change', function () {
      const lang = $(this).val();
      const t = translations[lang];
      $('#loginTitle').text(t.loginTitle);
      $('#emailLabel').text(t.emailLabel);
      $('#passwordLabel').text(t.passwordLabel);
      $('#forgotLabel').text(t.forgotLabel);
      $('#loginButton').text(t.loginButton);
    });
  </script>

</body>
</html>
