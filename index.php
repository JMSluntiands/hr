<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            inter: ['Inter', 'sans-serif']
          }
        }
      }
    }
  </script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="h-screen bg-gray-800 flex items-center justify-center font-inter">
  <div class="bg-gray-900/90 backdrop-blur-md p-10 rounded-2xl shadow-lg w-full max-w-sm text-white">
    <div class="flex justify-center mb-[40px]">
      <img src="img/logo-light.png" alt="Logo" class="w-[200px]">
    </div>
    <div class="mb-4">
      <label for="email" class="block text-sm font-medium">Email</label>
      <input id="email" type="email" placeholder="Enter your email" class="mt-1 w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#fa9b05] text-gray-900" autocomplete="off">
    </div>
    <div class="mb-4 relative">
      <label for="password" class="block text-sm font-medium">Password</label>
      <input id="password" type="password" placeholder="Enter your password" class="mt-1 w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#fa9b05] text-gray-900 pr-10">
      <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center mt-7">
        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#393938ff" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#343a40" class="w-6 h-6 hidden">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
        </svg>
      </button>
    </div>
    <div class="flex justify-end mb-4">
      <a href="#" class="text-sm text-[#fa9b05] hover:underline">Forgot password?</a>
    </div>
    <button class="w-full bg-[#fa9b05] hover:bg-[#e38b03] text-white font-medium p-3 rounded-lg">Login</button>
  </div>
  <script>
    $(document).ready(function() {
      $('#togglePassword').click(function() {
        let passwordField = $('#password');
        let type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);
        $('#eyeOpen').toggleClass('hidden');
        $('#eyeClosed').toggleClass('hidden');
      });
    });
  </script>
</body>
</html>
