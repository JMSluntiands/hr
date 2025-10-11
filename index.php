<!DOCTYPE html>
<html lang="en">
  <head>
    <?php
      session_start();

        // Kung naka-login na
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
          $roleRedirects = [
              "LBS"     => "admin/index",
              "BPH"     => "admin/index",
              "B1"      => "admin/index",
              "BLUINQ"  => "admin/index",
              "LUNTIAN" => "admin/index"
          ];

          // Redirect sa tamang admin page
          if (array_key_exists($_SESSION['role'], $roleRedirects)) {
              header("Location: " . $roleRedirects[$_SESSION['role']]);
              exit;
          }
        }
      ?>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Luntian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

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
  <body class="font-inter h-screen flex flex-col md:flex-row">

    <!-- Left Section (hidden sa mobile) -->
    <div class="hidden md:flex w-1/2 bg-luntianDark flex-col justify-center items-center relative">
      <img src="img/logo-light.png" alt="Logo" class="absolute top-6 left-6 w-[200px]">
      <img src="img/login-model.svg" alt="Illustration" class="max-w-sm">
    </div>

    <!-- Right Section (full width sa mobile) -->
    <div class="w-full md:w-1/2 bg-luntianOrange flex flex-col justify-center items-center relative min-h-screen p-5">
      <div class="bg-gray-900 text-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="hidden md:block text-2xl font-bold text-center mb-6">Welcome to Luntian</h2>
        <div class="block md:hidden flex justify-center items-center mb-[25px]">
          <img src="img/logo-light.png" alt="Logo" class="w-[200px]">
        </div>
        <div class="mb-4">
          <label for="email" class="block text-sm font-medium">Email</label>
          <input id="email" type="email" placeholder="Enter your email"
            class="mt-1 w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-luntianOrange text-gray-900" autocomplete="OFF">
        </div>
        <div class="mb-4 relative">
          <label for="password" class="block text-sm font-medium">Password</label>
          <input id="password" type="password" placeholder="Enter your password"
            class="mt-1 w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-luntianOrange text-gray-900 pr-10">
          <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center mt-7">
            <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#555" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#555" class="w-6 h-6 hidden">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
            </svg>
          </button>
        </div>
        <div class="flex justify-end mb-4">
          <!-- <a href="#" class="text-sm text-luntianOrange hover:underline">Forgot password?</a> -->
        </div>
        <button class="w-full bg-luntianOrange hover:bg-[#e38b03] text-white font-medium p-3 rounded-lg" id="loginButton">Login</button>
      </div>
    </div>

  </body>


  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script src="function/login/toggle-password.js"></script>
  <script src="function/login/login-process.js"></script>
</html>
