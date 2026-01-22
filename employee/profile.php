<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';

// Get employee data from database
$userId = (int)$_SESSION['user_id'];
$employeeData = null;
$employeeDbId = null;

if ($conn) {
    // Get user email
    $userStmt = $conn->prepare("SELECT email FROM user_login WHERE id = ?");
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    $userStmt->close();
    
    if ($user) {
        // Get employee by email
        $empStmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
        $empStmt->bind_param('s', $user['email']);
        $empStmt->execute();
        $empResult = $empStmt->get_result();
        $employeeData = $empResult->fetch_assoc();
        $empStmt->close();
        
        if ($employeeData) {
            $employeeDbId = (int)$employeeData['id'];
        }
    }
}

$employeeName = $employeeData['full_name'] ?? $_SESSION['name'] ?? 'Juan Dela Cruz';
$position     = $employeeData['position'] ?? $_SESSION['position'] ?? 'Software Engineer';
$department   = $employeeData['department'] ?? $_SESSION['department'] ?? 'IT Department';
$employeeId   = $employeeData['employee_id'] ?? $_SESSION['employee_id'] ?? 'EMP-0001';
$dateHired    = $employeeData['date_hired'] ?? $_SESSION['hire_date'] ?? 'Jan 15, 2020';

// Get document uploads
$documents = [];
$documentTypes = [
    'Birth Certificate (PSA)',
    'Government IDs (Valid ID Set)',
    'Employment Contract',
    'Company ID Form'
];

if ($employeeDbId && $conn) {
    $docStmt = $conn->prepare("SELECT * FROM employee_document_uploads WHERE employee_id = ? ORDER BY document_type, created_at DESC");
    $docStmt->bind_param('i', $employeeDbId);
    $docStmt->execute();
    $docResult = $docStmt->get_result();
    while ($row = $docResult->fetch_assoc()) {
        $documents[$row['document_type']] = $row;
    }
    $docStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white">
                    <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($employeeName); ?></div>
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

    <!-- Main Content (only this area scrolls) -->
    <main class="ml-64 min-h-screen p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">My Profile</h1>
                <p class="text-sm text-slate-500 mt-1">
                    View and verify your HRIS information. Philippine format and IDs applied.
                </p>
            </div>
            <div class="hidden md:flex items-center gap-3 text-sm text-slate-500">
                <span><?php echo htmlspecialchars($department); ?></span>
                <span class="w-1 h-1 rounded-full bg-slate-400"></span>
                <span><?php echo htmlspecialchars($position); ?></span>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Personal Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Full Name</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($employeeName); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Date of Birth</p>
                        <p class="font-medium text-slate-900">January 10, 1995</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Sex</p>
                        <p class="font-medium text-slate-900">Male</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Civil Status</p>
                        <p class="font-medium text-slate-900">Single</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Citizenship</p>
                        <p class="font-medium text-slate-900">Filipino</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Place of Birth</p>
                        <p class="font-medium text-slate-900">Quezon City, Philippines</p>
                    </div>
                </div>
            </section>

            <!-- Government Information -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Government Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">TIN</p>
                        <p class="font-medium text-slate-900">123-456-789-000</p>
                    </div>
                    <div>
                        <p class="text-slate-500">SSS Number</p>
                        <p class="font-medium text-slate-900">12-3456789-0</p>
                    </div>
                    <div>
                        <p class="text-slate-500">PhilHealth Number</p>
                        <p class="font-medium text-slate-900">12-345678901-2</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Pag-IBIG (HDMF) Number</p>
                        <p class="font-medium text-slate-900">1234-5678-9012</p>
                    </div>
                    <div>
                        <p class="text-slate-500">PhilSys ID</p>
                        <p class="font-medium text-slate-900">0000-1111-2222-3333</p>
                    </div>
                    <div>
                        <p class="text-slate-500">BIR Tax Status</p>
                        <p class="font-medium text-slate-900">Single / ME</p>
                    </div>
                </div>
            </section>

            <!-- Employee Information -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Employee Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Employee ID</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($employeeId); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Position</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($position); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Department</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($department); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Employment Status</p>
                        <p class="font-medium text-slate-900">Regular</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Date Hired</p>
                        <p class="font-medium text-slate-900">
                            <?php echo htmlspecialchars($dateHired); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Work Schedule</p>
                        <p class="font-medium text-slate-900">Mon–Fri, 9:00 AM – 6:00 PM</p>
                    </div>
                </div>
            </section>

            <!-- Contact Details -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Contact Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <p class="text-slate-500">Mobile Number</p>
                        <p class="font-medium text-slate-900">+63 917 123 4567</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Email Address</p>
                        <p class="font-medium text-slate-900">juan.delacruz@example.com</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-slate-500">Present Address</p>
                        <p class="font-medium text-slate-900">
                            Unit 5B, Sample Condominium, EDSA, Mandaluyong City, Philippines
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-slate-500">Permanent Address</p>
                        <p class="font-medium text-slate-900">
                            Brgy. San Isidro, Quezon City, Philippines
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-500">Emergency Contact Person</p>
                        <p class="font-medium text-slate-900">Maria Dela Cruz (Mother)</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Emergency Contact Number</p>
                        <p class="font-medium text-slate-900">+63 918 765 4321</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Documents -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 mt-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Documents</h2>
                <span class="text-xs text-slate-400">Uploaded documents for HRIS 201 file</span>
            </div>
            <div class="p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Document Type</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Updated</th>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($documentTypes as $docType): 
                            $doc = $documents[$docType] ?? null;
                            $hasFile = $doc && !empty($doc['file_path']);
                            
                            // Determine status: No File if no document exists, otherwise use document status
                            if (!$hasFile) {
                                $status = 'No File';
                                $statusClass = 'bg-slate-100 text-slate-600';
                                $statusText = 'No File';
                            } else {
                                $status = $doc['status'] ?? 'Pending';
                                $statusClass = $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                $statusText = $status === 'Approved' ? 'Verified' : ($status === 'Rejected' ? 'Rejected' : 'Pending');
                            }
                            
                            $lastUpdated = $doc && $doc['updated_at'] ? date('M d, Y', strtotime($doc['updated_at'])) : ($doc && $doc['created_at'] ? date('M d, Y', strtotime($doc['created_at'])) : '—');
                        ?>
                        <tr>
                            <td class="px-4 py-2 text-slate-700"><?php echo htmlspecialchars($docType); ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($statusText); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-slate-500 text-xs"><?php echo $lastUpdated; ?></td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="upload-doc-btn p-2 rounded-lg bg-[#d97706] hover:bg-[#b45309] text-white transition-colors" data-doc-type="<?php echo htmlspecialchars($docType); ?>" title="Upload">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                        </svg>
                                    </button>
                                    <?php if ($hasFile): ?>
                                    <a href="document-view.php?id=<?php echo (int)$doc['id']; ?>" target="_blank" class="p-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <?php else: ?>
                                    <button type="button" disabled class="p-2 rounded-lg bg-slate-300 text-slate-500 cursor-not-allowed" title="No file to view">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.008 9.963 7.181.07.207.07.43 0 .637C20.573 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.008-9.964-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>

    <!-- Upload Document Modal -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-slate-800">Upload Document</h2>
                <button type="button" id="closeUploadModal" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="document_type" id="uploadDocType" value="">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Document Type</label>
                    <input type="text" id="uploadDocTypeDisplay" readonly class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Select File <span class="text-red-500">*</span></label>
                    <input type="file" name="document_file" id="documentFile" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                    <p class="text-xs text-slate-500 mt-1">Allowed: PDF, JPG, PNG (Max 5MB)</p>
                </div>
                <div id="uploadMessage" class="hidden"></div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelUpload" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309]">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          // Remove any active state from all links
          $('.js-side-link').removeClass('bg-[#f1f5f9] text-[#FA9800] font-medium rounded-l-none rounded-r-full');
          $('.js-side-link').addClass('rounded-lg');

          // Load only the right content
          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });

        // Delegated filters for Time Off page
        $(document).on('change', '#usageTypeFilter, #usageYearFilter', function () {
          const type = $('#usageTypeFilter').val();
          const year = $('#usageYearFilter').val();

          $('#usageTable tbody tr').each(function () {
            const rowType = $(this).data('type');
            const rowYear = String($(this).data('year'));
            const typeOk = type === 'all' || type === rowType;
            const yearOk = year === 'all' || year === rowYear;
            $(this).toggle(typeOk && yearOk);
          });
        });

        $(document).on('change', '#requestStatusFilter, #requestTypeFilter', function () {
          const status = $('#requestStatusFilter').val();
          const type = $('#requestTypeFilter').val();

          $('#requestTable tbody tr').each(function () {
            const rowStatus = $(this).data('status');
            const rowType = $(this).data('type');
            const statusOk = status === 'all' || status === rowStatus;
            const typeOk = type === 'all' || type === rowType;
            $(this).toggle(statusOk && typeOk);
          });
        });

        // Document Upload Modal
        $(document).on('click', '.upload-doc-btn', function() {
          const docType = $(this).data('doc-type');
          $('#uploadDocType').val(docType);
          $('#uploadDocTypeDisplay').val(docType);
          $('#documentFile').val('');
          $('#uploadMessage').addClass('hidden').html('');
          $('#uploadModal').removeClass('hidden');
        });

        $('#closeUploadModal, #cancelUpload').on('click', function() {
          $('#uploadModal').addClass('hidden');
        });

        $('#uploadModal').on('click', function(e) {
          if (e.target === this) {
            $('#uploadModal').addClass('hidden');
          }
        });

        $('#uploadForm').on('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(this);
          const $btn = $(this).find('button[type="submit"]');
          const originalText = $btn.text();
          
          $btn.prop('disabled', true).text('Uploading...');
          $('#uploadMessage').addClass('hidden').html('');

          $.ajax({
            url: 'document-upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
              if (response.status === 'success') {
                $('#uploadMessage').removeClass('hidden').addClass('bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-lg text-sm').html(response.message);
                setTimeout(function() {
                  location.reload();
                }, 1500);
              } else {
                $('#uploadMessage').removeClass('hidden').addClass('bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-lg text-sm').html(response.message);
                $btn.prop('disabled', false).text(originalText);
              }
            },
            error: function() {
              $('#uploadMessage').removeClass('hidden').addClass('bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-lg text-sm').html('Upload failed. Please try again.');
              $btn.prop('disabled', false).text(originalText);
            }
          });
        });
      });
    </script>
</body>
</html>

