<!DOCTYPE html>
<html lang="en">
  <head>
    <?php
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
      session_start();

      // Kung naka-login na
      if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // Simplified roles: admin at employee lang
        $role = strtolower((string)$_SESSION['role']);

        if ($role === 'admin') {
          $selectedModule = $_SESSION['admin_module'] ?? '';
          $embedded = defined('HR_LEGACY_EMBEDDED') && HR_LEGACY_EMBEDDED;

          if ($selectedModule === 'inventory') {
            header('Location: ' . ($embedded ? '/inventory' : 'inventory/index.php'));
          } elseif ($selectedModule === 'workforce') {
            header('Location: ' . ($embedded ? '/admin/workforce' : 'workforce/index.php'));
          } elseif ($selectedModule === 'permission') {
            header('Location: ' . ($embedded ? '/permission' : 'permission/index.php'));
          } elseif ($selectedModule === 'hr') {
            header('Location: ' . ($embedded ? '/admin/dashboard' : 'admin/index.php'));
          } else {
            header('Location: ' . ($embedded ? '/admin/module-select' : 'admin/module-select.php'));
          }
          exit;
        }

        if ($role === 'employee') {
          $selectedEmployeeModule = $_SESSION['employee_module'] ?? '';

          if ($selectedEmployeeModule === 'timekeeping') {
            header('Location: employee/timekeeping/index.php');
          } elseif ($selectedEmployeeModule === 'profile') {
            header('Location: employee/profile.php');
          } else {
            header('Location: employee/module-select.php');
          }
          exit;
        }

        // Fallback kung ibang role man, diretso sa employee dashboard
        header('Location: employee/index.php');
        exit;
      }

      $loginQuery = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
        ? '?' . $_SERVER['QUERY_STRING']
        : '';
      header('Location: /login' . $loginQuery);
      exit;
    ?>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Luntian</title>
    <link rel="icon" type="image/png" href="assets/img/luntian-favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
  <body class="font-inter min-h-screen flex items-center justify-center p-4 md:p-6 relative overflow-hidden">

    <!-- Full-page gradient background (old colors: luntianDark → FA9800) -->
    <div class="absolute inset-0 bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800] -z-10"></div>

    <!-- Desktop Login Card: white, 2 columns (graphic left, form right) -->
    <div class="hidden md:flex bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden flex-row">
      <!-- Left: users icon -->
      <div class="hidden md:flex md:w-2/5 bg-white items-center justify-center p-8 md:p-12">
        <div class="w-48 h-48 md:w-56 md:h-56 rounded-full border-2 border-slate-200 bg-white flex items-center justify-center shadow-inner">
          <i class="fa fa-users text-7xl md:text-8xl text-[#E9A319]" aria-hidden="true"></i>
        </div>
      </div>

      <!-- Right: form -->
      <div class="flex-1 p-6 md:p-10 flex flex-col justify-center">
        <form class="js-login-form" id="loginFormDesktop" novalidate>
          <h1 class="text-2xl font-bold text-slate-800 mb-6">User Login</h1>

          <div class="mb-4">
            <label for="email" class="sr-only">Email</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </span>
              <input name="email" id="email" type="email" placeholder="Email Id"
                class="w-full pl-10 pr-4 py-3 bg-slate-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E9A319]/50 focus:bg-white text-slate-900" autocomplete="OFF">
            </div>
          </div>

          <div class="mb-5 relative">
            <label for="password" class="sr-only">Password</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </span>
              <input name="password" id="password" type="password" placeholder="Password"
                class="w-full pl-10 pr-12 py-3 bg-slate-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E9A319]/50 focus:bg-white text-slate-900">
              <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18"/></svg>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              class="js-auth-btn w-full bg-[#E9A319] hover:bg-[#d18a15] text-white font-semibold py-3 rounded-xl transition-colors"
              id="loginButton"
              data-action="login"
            >
              Login
            </button>
            <button
              type="button"
              class="js-auth-btn w-full bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 rounded-xl transition-colors"
              id="timeInButton"
              data-action="timein"
            >
              Time In
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- Mobile Version: same card layout, graphic on top, same gradient bg -->
    <div class="md:hidden absolute inset-0 flex flex-col items-center justify-center p-4 bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800]">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="flex justify-center pt-8 pb-4">
          <div class="w-32 h-32 rounded-full border-2 border-slate-200 bg-white flex items-center justify-center shadow-inner">
            <i class="fa fa-users text-6xl text-[#E9A319]" aria-hidden="true"></i>
          </div>
        </div>
        <div class="px-6 pb-8">
          <form class="js-login-form" id="loginFormMobile" novalidate>
            <h1 class="text-2xl font-bold text-slate-800 mb-6 text-center">User Login</h1>

            <div class="mb-4">
              <label for="emailMobile" class="sr-only">Email</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </span>
                <input name="email" id="emailMobile" type="email" placeholder="Email Id"
                  class="w-full pl-10 pr-4 py-3 bg-slate-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E9A319]/50 focus:bg-white text-slate-900" autocomplete="OFF">
              </div>
            </div>

            <div class="mb-5 relative">
              <label for="passwordMobile" class="sr-only">Password</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
                <input name="password" id="passwordMobile" type="password" placeholder="Password"
                  class="w-full pl-10 pr-12 py-3 bg-slate-100 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#E9A319]/50 focus:bg-white text-slate-900">
                <button type="button" id="togglePasswordMobile" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                  <svg id="eyeOpenMobile" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <svg id="eyeClosedMobile" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18"/></svg>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                class="js-auth-btn w-full bg-[#E9A319] hover:bg-[#d18a15] text-white font-semibold py-3 rounded-xl transition-colors"
                id="loginButtonMobile"
                data-action="login"
              >
                Login
              </button>
              <button
                type="button"
                class="js-auth-btn w-full bg-slate-700 hover:bg-slate-800 text-white font-semibold py-3 rounded-xl transition-colors"
                id="timeInButtonMobile"
                data-action="timein"
              >
                Time In
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>

  </body>


  <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
  <script src="assets/js/manila-time.js"></script>
  <script src="function/login/toggle-password.js"></script>
  <script src="function/login/login-process.js"></script>
  <script>
    (function() {
      var q = new URLSearchParams(window.location.search);
      var err = q.get('error');
      var timeout = q.get('timeout');
      var hasCacheBuster = q.has('cb');
      if (!err && !timeout && !hasCacheBuster) return;

      if (timeout) {
        Toastify({ text: 'You have been logged out due to inactivity.', duration: 5000, gravity: 'top', position: 'right', backgroundColor: '#f59e0b' }).showToast();
        history.replaceState({}, '', window.location.pathname);
        return;
      }
      if (!err && hasCacheBuster) {
        history.replaceState({}, '', window.location.pathname);
        return;
      }

      var msg = {
        google_not_configured: 'Google Sign-In is not configured.',
        google_denied: 'Google sign-in was cancelled or denied.',
        google_no_code: 'Google did not return an authorization.',
        google_token_failed: 'Could not verify Google sign-in. Try again.',
        google_no_email: 'Google did not provide your email.',
        domain_restricted: 'Access is restricted to @luntiands.com email addresses only.',
        no_hr_account: 'No HR account found for this email. Ask admin to add you.',
        account_inactive: 'Your account is inactive. Please contact HR.'
      }[err] || 'Something went wrong. Please try again.';
      Toastify({ text: msg, duration: 5000, gravity: 'top', position: 'right', backgroundColor: '#e3342f' }).showToast();
      history.replaceState({}, '', window.location.pathname);
    })();
  </script>
</html>
