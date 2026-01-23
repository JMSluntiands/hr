<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/employee_data.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    <!-- Sidebar (fixed) -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#d97706] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../uploads/' . $employeePhoto)): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <!-- Dashboard -->
            <a href="index.php"
               data-url="index.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- My Profile -->
            <a href="profile.php"
               data-url="profile.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>My Profile</span>
            </a>
            <!-- My Time Off -->
            <a href="timeoff.php"
               data-url="timeoff.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>My Time Off</span>
            </a>
            <!-- My Request -->
            <a href="request.php"
               data-url="request.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>My Request</span>
            </a>
            <!-- Settings -->
            <a href="settings.php"
               data-url="settings.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Request</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Request and preview employment and government certificates.
                </p>
            </div>
            <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                <span><?php echo htmlspecialchars($department); ?></span>
                <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                <span><?php echo htmlspecialchars($position); ?></span>
            </div>
        </div>

        <!-- Certificate Buttons -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Certificates</h2>
            <div class="flex flex-wrap gap-3 text-sm">
                <button
                    class="js-cert-btn inline-flex items-center px-4 py-2 rounded-full bg-[#FA9800] text-white font-medium shadow-sm"
                    data-doc="coe">
                    Certificate of Employment (COE)
                </button>
                <button
                    class="js-cert-btn inline-flex items-center px-4 py-2 rounded-full bg-slate-100 text-slate-700"
                    data-doc="sss">
                    SSS Certificate
                </button>
                <button
                    class="js-cert-btn inline-flex items-center px-4 py-2 rounded-full bg-slate-100 text-slate-700"
                    data-doc="pagibig">
                    Pag-IBIG Certificate
                </button>
                <button
                    class="js-cert-btn inline-flex items-center px-4 py-2 rounded-full bg-slate-100 text-slate-700"
                    data-doc="philhealth">
                    PhilHealth Certificate
                </button>
            </div>
        </section>

        <!-- PDF View Box -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 mt-6 h-[600px] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700" id="pdfTitle">
                    Certificate of Employment (COE)
                </h2>
                <p class="text-xs text-slate-500 mt-1" id="pdfSubtitle">
                    PDF preview area. Once integrated, requested certificates will appear here.
                </p>
            </div>
            <div id="pdfViewBox" class="flex-1 bg-slate-50 flex flex-col items-center justify-center text-slate-400 text-sm">
                <div class="w-16 h-20 mb-3 rounded-md border border-dashed border-slate-300 bg-white flex items-center justify-center">
                    <span class="text-xs font-medium text-slate-400">PDF</span>
                </div>
                <p id="pdfMessage">No PDF loaded. Select a certificate above to preview.</p>
            </div>
        </section>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        // Sidebar partial-load behavior
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          if (url === 'profile.php') {
            window.location.href = url;
            return;
          }

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Certificate button behavior (UI only for now)
        const titles = {
          coe: 'Certificate of Employment (COE)',
          sss: 'SSS Certificate',
          pagibig: 'Pag-IBIG Certificate',
          philhealth: 'PhilHealth Certificate',
        };

        const subtitles = {
          coe: 'Preview area for your Certificate of Employment PDF.',
          sss: 'Preview area for your SSS certification PDF.',
          pagibig: 'Preview area for your Pag-IBIG certification PDF.',
          philhealth: 'Preview area for your PhilHealth certification PDF.',
        };

        $('.js-cert-btn').on('click', function () {
          const doc = $(this).data('doc');

          $('.js-cert-btn')
            .removeClass('bg-[#FA9800] text-white')
            .addClass('bg-slate-100 text-slate-700');

          $(this)
            .removeClass('bg-slate-100 text-slate-700')
            .addClass('bg-[#FA9800] text-white');

          $('#pdfTitle').text(titles[doc] || 'Certificate Preview');
          $('#pdfSubtitle').text(subtitles[doc] || 'PDF preview area for the selected certificate.');
          $('#pdfMessage').text('PDF preview for ' + (titles[doc] || 'selected certificate') + ' will appear here once integrated.');
        });

        $(document).on('click', '#profilePhotoBtn', function(e) { e.preventDefault(); $('#profilePhotoInput').click(); });
        $(document).on('change', '#profilePhotoInput', function() {
          var $input = $(this); var files = $input[0].files;
          if (!files || !files.length) return;
          var fd = new FormData(); fd.append('profile_picture', files[0]);
          $('#profilePhotoMessage').addClass('hidden').html('');
          $('#profilePhotoBtn').prop('disabled', true).text('Uploading...');
          $.ajax({ url: 'profile-picture-upload.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo'); $input.val('');
              if (res.status === 'success') {
                $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-emerald-600').html(res.message);
                if (res.path) { $('#profilePhotoImg').attr('src', '../uploads/' + res.path).removeClass('hidden'); $('#profilePhotoInitial').addClass('hidden'); }
                setTimeout(function() { location.reload(); }, 800);
              } else { $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(res.message || 'Upload failed'); }
            },
            error: function(xhr) {
              $('#profilePhotoBtn').prop('disabled', false).text('Choose Photo'); $input.val('');
              var m = 'Upload failed.'; try { var r = JSON.parse(xhr.responseText); if (r.message) m = r.message; } catch(e) {}
              $('#profilePhotoMessage').removeClass('hidden').addClass('text-sm text-red-600').html(m);
            }
          });
        });
      });
    </script>
</body>
</html>

