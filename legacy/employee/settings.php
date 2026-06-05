<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include '../database/db.php';
include 'include/employee_data.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#FA9800',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



    <!-- Main Content -->
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div id="main-inner">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-semibold text-slate-800">Settings</h1>
                <p class="text-sm text-slate-500 mt-1">Manage your account settings and preferences</p>
            </div>

            <!-- Change Password Section -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Change Password</h2>
                </div>
                <div class="p-6">
                    <form id="changePasswordForm" class="space-y-4 max-w-md">
                        <div>
                            <label for="currentPassword" class="block text-sm font-medium text-slate-700 mb-2">Current Password</label>
                            <div class="relative" style="position: relative;">
                                <input type="password" id="currentPassword" name="current_password" required
                                    class="w-full px-4 py-2 pr-12 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                    style="padding-right: 3rem; position: relative; z-index: 1;">
                                <button type="button" 
                                        id="toggleCurrentPassword"
                                        class="toggle-password absolute right-0 top-0 bottom-0 flex items-center justify-center w-10 h-full text-slate-500 hover:text-slate-700 cursor-pointer" 
                                        data-target="currentPassword" 
                                        style="pointer-events: auto !important; z-index: 50 !important; position: absolute !important; background: transparent; border: none; outline: none; cursor: pointer;">
                                    <svg id="eyeOpenCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg id="eyeClosedCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="newPassword" class="block text-sm font-medium text-slate-700 mb-2">New Password</label>
                            <div class="relative" style="position: relative;">
                                <input type="password" id="newPassword" name="new_password" required minlength="6"
                                    class="w-full px-4 py-2 pr-12 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                    style="padding-right: 3rem; position: relative; z-index: 1;">
                                <button type="button" 
                                        id="toggleNewPassword"
                                        class="toggle-password absolute right-0 top-0 bottom-0 flex items-center justify-center w-10 h-full text-slate-500 hover:text-slate-700 cursor-pointer" 
                                        data-target="newPassword" 
                                        style="pointer-events: auto !important; z-index: 50 !important; position: absolute !important; background: transparent; border: none; outline: none; cursor: pointer;">
                                    <svg id="eyeOpenNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg id="eyeClosedNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Password must be at least 6 characters long</p>
                        </div>
                        <div>
                            <label for="confirmPassword" class="block text-sm font-medium text-slate-700 mb-2">Confirm New Password</label>
                            <div class="relative" style="position: relative;">
                                <input type="password" id="confirmPassword" name="confirm_password" required
                                    class="w-full px-4 py-2 pr-12 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]"
                                    style="padding-right: 3rem; position: relative; z-index: 1;">
                                <button type="button" 
                                        id="toggleConfirmPassword"
                                        class="toggle-password absolute right-0 top-0 bottom-0 flex items-center justify-center w-10 h-full text-slate-500 hover:text-slate-700 cursor-pointer" 
                                        data-target="confirmPassword" 
                                        style="pointer-events: auto !important; z-index: 50 !important; position: absolute !important; background: transparent; border: none; outline: none; cursor: pointer;">
                                    <svg id="eyeOpenConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg id="eyeClosedConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.75 12c1.4 4.173 5.336 7.181 9.964 7.181 1.625 0 3.17-.363 4.57-1.014m3.545-2.707A10.45 10.45 0 0022.25 12c-1.4-4.173-5.336-7.181-9.964-7.181-1.318 0-2.58.24-3.75.678m-3.83 1.917L3 3m0 0l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div id="passwordMessage" class="hidden"></div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] transition-colors font-medium">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/sidebar-dropdown.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        $(function () {
            $('.js-side-link').on('click', function (e) {
                const url = $(this).data('url');
                if (!url) return;
                e.preventDefault();

                const pathOnly = (url || '').split('#')[0].split('?')[0];
                if (url === 'index.php' || url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'reimbursement.php' || url === 'request.php' || pathOnly === 'inventory.php' || ['performance.php', 'performance-my-reviews.php', 'performance-form-review.php', 'performance-review-received.php', 'performance-review-submissions.php'].indexOf(pathOnly) !== -1 || ['incident-report.php', 'incident-report-add.php', 'incident-report-list.php'].indexOf(pathOnly) !== -1) {
                    window.location.href = url;
                    return;
                }

                $('.js-side-link').removeClass('bg-white/20');
                $(this).addClass('bg-white/20');

                $('#main-inner').addClass('opacity-60 pointer-events-none');
                $('#main-inner').load(url + ' #main-inner', function () {
                    $('#main-inner').removeClass('opacity-60 pointer-events-none');
                });
            });

            // Password Toggle Functionality - Multiple approaches for reliability
            function togglePasswordVisibility(button) {
                const targetId = $(button).data('target');
                const $input = $('#' + targetId);
                const $button = $(button);
                
                if (!$input.length) {
                    return;
                }
                
                const currentType = $input.attr('type');
                
                if (currentType === 'password') {
                    $input.attr('type', 'text');
                    $button.find('svg').first().addClass('hidden');
                    $button.find('svg').last().removeClass('hidden');
                } else {
                    $input.attr('type', 'password');
                    $button.find('svg').first().removeClass('hidden');
                    $button.find('svg').last().addClass('hidden');
                }
            }
            
            // Direct click handler
            $(document).on('click', '.toggle-password', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                togglePasswordVisibility(this);
                return false;
            });
            
            // Also handle by ID for extra reliability
            $(document).on('click', '#toggleCurrentPassword, #toggleNewPassword, #toggleConfirmPassword', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                togglePasswordVisibility(this);
                return false;
            });
            
            // Mousedown as backup
            $(document).on('mousedown', '.toggle-password', function(e) {
                e.preventDefault();
                e.stopPropagation();
                togglePasswordVisibility(this);
                return false;
            });

            // Real-time validation for confirm password
            $('#confirmPassword').on('input', function() {
                const newPassword = $('#newPassword').val();
                const confirmPassword = $(this).val();
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    $(this).addClass('border-red-500').removeClass('border-slate-200');
                    if ($('#confirmPasswordError').length === 0) {
                        $(this).after('<p id="confirmPasswordError" class="text-xs text-red-600 mt-1">Passwords do not match</p>');
                    }
                } else {
                    $(this).removeClass('border-red-500').addClass('border-slate-200');
                    $('#confirmPasswordError').remove();
                }
            });
            
            $('#newPassword').on('input', function() {
                const newPassword = $(this).val();
                const confirmPassword = $('#confirmPassword').val();
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    $('#confirmPassword').addClass('border-red-500').removeClass('border-slate-200');
                    if ($('#confirmPasswordError').length === 0) {
                        $('#confirmPassword').after('<p id="confirmPasswordError" class="text-xs text-red-600 mt-1">Passwords do not match</p>');
                    }
                } else {
                    $('#confirmPassword').removeClass('border-red-500').addClass('border-slate-200');
                    $('#confirmPasswordError').remove();
                }
            });

            // Change Password Form
            $('#changePasswordForm').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous error messages
                $('#confirmPasswordError').remove();
                $('#currentPassword, #newPassword, #confirmPassword').removeClass('border-red-500').addClass('border-slate-200');
                
                const currentPassword = $('#currentPassword').val().trim();
                const newPassword = $('#newPassword').val().trim();
                const confirmPassword = $('#confirmPassword').val().trim();
                
                let hasError = false;
                
                // Validation - check all fields
                if (!currentPassword) {
                    $('#currentPassword').addClass('border-red-500').removeClass('border-slate-200');
                    hasError = true;
                }
                
                if (!newPassword) {
                    $('#newPassword').addClass('border-red-500').removeClass('border-slate-200');
                    hasError = true;
                }
                
                if (!confirmPassword) {
                    $('#confirmPassword').addClass('border-red-500').removeClass('border-slate-200');
                    hasError = true;
                }
                
                if (hasError) {
                    showMessage('Please fill in all fields', 'error');
                    return false;
                }
                
                // Validate password length
                if (newPassword.length < 6) {
                    $('#newPassword').addClass('border-red-500').removeClass('border-slate-200');
                    showMessage('Password must be at least 6 characters long', 'error');
                    return false;
                }
                
                // Validate passwords match
                if (newPassword !== confirmPassword) {
                    $('#newPassword, #confirmPassword').addClass('border-red-500').removeClass('border-slate-200');
                    $('#confirmPassword').after('<p id="confirmPasswordError" class="text-xs text-red-600 mt-1">Passwords do not match</p>');
                    showMessage('New password and confirm password do not match', 'error');
                    return false;
                }
                
                // Validate new password is different
                if (currentPassword === newPassword) {
                    $('#newPassword').addClass('border-red-500').removeClass('border-slate-200');
                    showMessage('New password must be different from current password', 'error');
                    return false;
                }
                
                const formData = {
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                };
                
                const $btn = $(this).find('button[type="submit"]');
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('Changing...');
                $('#passwordMessage').addClass('hidden').html('');
                
                $.ajax({
                    url: 'change-password.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.status === 'success') {
                            showMessage(response.message, 'success');
                            $('#changePasswordForm')[0].reset();
                            // Reset password fields to password type
                            $('#currentPassword, #newPassword, #confirmPassword').attr('type', 'password');
                            $('.toggle-password').each(function() {
                                $(this).find('svg').first().removeClass('hidden');
                                $(this).find('svg').last().addClass('hidden');
                            });
                            // Clear default password flag from session
                            <?php if (isset($_SESSION['is_default_password'])): ?>
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 1500);
                            <?php endif; ?>
                        } else {
                            const errorMsg = response && response.message ? response.message : 'Failed to change password';
                            showMessage(errorMsg, 'error');
                            $btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'An error occurred. Please try again.';
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.message) {
                                    errorMsg = response.message;
                                }
                            } catch(e) {
                                errorMsg = xhr.responseText.substring(0, 100);
                            }
                        }
                        showMessage(errorMsg, 'error');
                        $btn.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            function showMessage(message, type) {
                const bgColor = type === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200';
                $('#passwordMessage')
                    .removeClass('hidden')
                    .addClass(bgColor + ' border px-4 py-2 rounded-lg text-sm')
                    .html(message);
                
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: type === 'success' ? "#38a169" : "#e3342f",
                }).showToast();
            }
        });
    </script>
</body>
</html>
