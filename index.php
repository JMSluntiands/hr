<!DOCTYPE html>
<html lang="en">
  <head>
    <?php
      session_start();

        // Kung naka-login na
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
          // Simplified roles: admin at employee lang
          $roleRedirects = [
              "admin"    => "admin/index.php",
              "Admin"    => "admin/index.php",
              "employee" => "employee/index.php",
              "Employee" => "employee/index.php"
          ];

          $role = $_SESSION['role'];
          $target = $roleRedirects[$role] ?? "admin/index.php"; // default admin kung may hindi kilalang role
          header("Location: " . $target);
          exit;
        }
      ?>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Luntian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
      .login-spinner {
        display: inline-block;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: login-spin 0.7s linear infinite;
        vertical-align: middle;
        margin-right: 0.5rem;
      }
      @keyframes login-spin {
        to { transform: rotate(360deg); }
      }
    </style>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { inter: ['Inter', 'sans-serif'] },
            colors: {
              luntianOrange: '#fa9b05',
              luntianDark: '#1e1e2d',
              tealBlue: '#FA9800'
            }
          }
        }
      }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  </head>
  <body class="font-inter h-screen flex flex-col md:flex-row relative overflow-hidden">

    <!-- Left Section (White Background) -->
    <div class="hidden md:flex w-1/2 bg-white flex-col justify-center items-center relative">
      <!-- Left Illustration: Standing Man -->
      <div class="absolute left-0 bottom-0 w-64 h-96 flex flex-col items-center justify-end">
        <div class="relative">
          <!-- Standing Man Figure -->
          <div class="w-32 h-48 flex flex-col items-center justify-end">
            <!-- Head -->
            <div class="w-12 h-12 rounded-full bg-[#E9A319] mb-2"></div>
            <!-- Body -->
            <div class="w-16 h-20 bg-[#E9A319] rounded-t-lg"></div>
            <!-- Arms -->
            <div class="absolute left-2 top-8 w-4 h-16 bg-[#E9A319] rounded-full transform rotate-[-25deg]"></div>
            <!-- Tablet/Document -->
            <div class="absolute right-2 top-10 w-8 h-12 bg-gray-300 rounded"></div>
          </div>
          <!-- Plant -->
          <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2">
            <div class="w-8 h-10 bg-gray-700 rounded-sm mb-2"></div>
            <div class="flex gap-1 justify-center">
              <div class="w-6 h-8 bg-yellow-400 rounded-full transform rotate-[-20deg]"></div>
              <div class="w-6 h-8 bg-yellow-400 rounded-full"></div>
              <div class="w-6 h-8 bg-yellow-400 rounded-full transform rotate-[20deg]"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Section (Teal Blue Background) -->
    <div class="hidden md:flex w-1/2 bg-[#FA9800] flex-col justify-center items-center relative">
      <!-- Right Illustration: Sitting Man -->
      <div class="absolute right-0 bottom-0 w-64 h-96 flex flex-col items-center justify-end pr-8">
        <div class="relative">
          <!-- Beanbag/Cushion -->
          <div class="w-32 h-20 bg-gray-400 rounded-full mb-2"></div>
          <!-- Sitting Man Figure -->
          <div class="absolute bottom-20 left-1/2 transform -translate-x-1/2 w-32 h-32 flex flex-col items-center">
            <!-- Head -->
            <div class="w-10 h-10 rounded-full bg-[#8B4513] mb-1"></div>
            <!-- Body -->
            <div class="w-14 h-16 bg-[#8B4513] rounded-t-lg"></div>
            <!-- Tablet -->
            <div class="absolute top-12 left-1/2 transform -translate-x-1/2 w-10 h-14 bg-gray-300 rounded"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Central Login Card -->
    <div class="absolute inset-0 flex items-center justify-center z-10 p-5">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 relative">
        <form class="js-login-form" id="loginFormDesktop" novalidate>
          <h1 class="text-2xl font-bold text-slate-800 mb-6 text-center">Login</h1>
          
          <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
            <input name="email" id="email" type="email" placeholder="company@example.com"
              class="w-full p-3 border-2 border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 text-slate-900" autocomplete="OFF">
          </div>
          
          <div class="mb-6 relative">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password</label>
            <input name="password" id="password" type="password" placeholder="123456789"
              class="w-full p-3 border-2 border-[#E9A319]/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#E9A319]/40 focus:border-[#E9A319] text-slate-900 pr-10">
            <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center mt-9">
              <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#64748b" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#64748b" class="w-5 h-5 hidden">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
              </svg>
            </button>
          </div>
          
          <button type="button" class="js-login-btn w-full bg-[#E9A319] hover:bg-[#d18a15] text-white font-semibold py-3 rounded-lg transition-colors" id="loginButton">Login</button>

          <div class="mt-4 flex items-center gap-3">
            <span class="flex-1 border-t border-slate-200"></span>
            <span class="text-xs text-slate-500">or</span>
            <span class="flex-1 border-t border-slate-200"></span>
          </div>
          <a href="controller/login/google-auth.php" class="mt-4 flex items-center justify-center gap-2 w-full py-3 px-4 border-2 border-slate-300 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Sign in with Google
          </a>
        </form>

        <div id="loginLockedNotice" class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
          <p class="text-sm text-amber-800 mb-2">Account locked. Request an unlock from admin?</p>
          <p class="text-xs text-amber-700 mb-2">Email: <span id="loginLockedEmail"></span></p>
          <input type="hidden" id="requestUnlockEmail" value="">
          <button type="button" id="requestUnlockBtn" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">Request unlock</button>
        </div>
      </div>
    </div>

    <!-- Mobile Version -->
    <div class="md:hidden w-full bg-white flex flex-col justify-center items-center min-h-screen p-5">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <form class="js-login-form" id="loginFormMobile" novalidate>
          <h1 class="text-2xl font-bold text-slate-800 mb-6 text-center">Login</h1>
          
          <div class="mb-5">
            <label for="emailMobile" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
            <input name="email" id="emailMobile" type="email" placeholder="company@example.com"
              class="w-full p-3 border-2 border-[#E9A319]/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#E9A319]/40 focus:border-[#E9A319] text-slate-900" autocomplete="OFF">
          </div>
          
          <div class="mb-6 relative">
            <label for="passwordMobile" class="block text-sm font-medium text-slate-700 mb-2">Password</label>
            <input name="password" id="passwordMobile" type="password" placeholder="123456789"
              class="w-full p-3 border-2 border-[#E9A319]/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#E9A319]/40 focus:border-[#E9A319] text-slate-900 pr-10">
            <button type="button" id="togglePassword" class="absolute inset-y-0 right-3 flex items-center mt-9">
              <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#64748b" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#64748b" class="w-5 h-5 hidden">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
              </svg>
            </button>
          </div>
          
          <button type="button" class="js-login-btn w-full bg-[#E9A319] hover:bg-[#d18a15] text-white font-semibold py-3 rounded-lg transition-colors" id="loginButtonMobile">Login</button>

          <div class="mt-4 flex items-center gap-3">
            <span class="flex-1 border-t border-slate-200"></span>
            <span class="text-xs text-slate-500">or</span>
            <span class="flex-1 border-t border-slate-200"></span>
          </div>
          <a href="controller/login/google-auth.php" class="mt-4 flex items-center justify-center gap-2 w-full py-3 px-4 border-2 border-slate-300 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Sign in with Google
          </a>
        </form>

        <div id="loginLockedNoticeMobile" class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
          <p class="text-sm text-amber-800 mb-2">Account locked. Request an unlock from admin?</p>
          <p class="text-xs text-amber-700 mb-2">Email: <span id="loginLockedEmailMobile"></span></p>
          <input type="hidden" id="requestUnlockEmailMobile" value="">
          <button type="button" id="requestUnlockBtnMobile" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">Request unlock</button>
        </div>
      </div>
    </div>

  </body>


  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script src="function/login/toggle-password.js"></script>
  <script src="function/login/login-process.js"></script>
  <script>
    (function() {
      var q = new URLSearchParams(window.location.search);
      var err = q.get('error');
      if (!err) return;
      var msg = {
        google_not_configured: 'Google Sign-In is not configured.',
        google_denied: 'Google sign-in was cancelled or denied.',
        google_no_code: 'Google did not return an authorization.',
        google_token_failed: 'Could not verify Google sign-in. Try again.',
        google_no_email: 'Google did not provide your email.',
        domain_restricted: 'Access is restricted to @luntiands.com email addresses only.',
        no_hr_account: 'No HR account found for this email. Ask admin to add you.'
      }[err] || 'Something went wrong. Please try again.';
      Toastify({ text: msg, duration: 5000, gravity: 'top', position: 'right', backgroundColor: '#e3342f' }).showToast();
      history.replaceState({}, '', window.location.pathname);
    })();
  </script>
</html>
