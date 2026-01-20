<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$employeeName = $_SESSION['name'] ?? 'Juan Dela Cruz';
$position     = $_SESSION['position'] ?? 'Software Engineer';
$department   = $_SESSION['department'] ?? 'IT Department';
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
                        luntianBlue: '#2563eb',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <!-- Sidebar (fixed) -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#1d4ed8] text-white flex flex-col">
        <div class="p-6 flex items-center gap-4 border-b border-blue-500/40">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/10 flex items-center justify-center">
                <span class="text-2xl font-semibold">
                    <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-semibold text-sm"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-blue-100">Employee</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <!-- Dashboard -->
            <a href="index.php"
               data-url="index.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>Dashboard</span>
            </a>
            <!-- My Profile -->
            <a href="profile.php"
               data-url="profile.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>My Profile</span>
            </a>
            <!-- My Time Off -->
            <a href="timeoff.php"
               data-url="timeoff.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>My Time Off</span>
            </a>
            <!-- My Request -->
            <a href="request.php"
               data-url="request.php"
               class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm">
                <span>My Request</span>
            </a>
        </nav>
        <div class="p-4 border-t border-blue-500/40">
            <a href="../logout.php" class="block text-xs text-blue-100 hover:text-white">Logout</a>
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
                    class="js-cert-btn inline-flex items-center px-4 py-2 rounded-full bg-[#1d4ed8] text-white font-medium shadow-sm"
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
            .removeClass('bg-[#1d4ed8] text-white')
            .addClass('bg-slate-100 text-slate-700');

          $(this)
            .removeClass('bg-slate-100 text-slate-700')
            .addClass('bg-[#1d4ed8] text-white');

          $('#pdfTitle').text(titles[doc] || 'Certificate Preview');
          $('#pdfSubtitle').text(subtitles[doc] || 'PDF preview area for the selected certificate.');
          $('#pdfMessage').text('PDF preview for ' + (titles[doc] || 'selected certificate') + ' will appear here once integrated.');
        });
      });
    </script>
</body>
</html>

