<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminId = (int)$_SESSION['user_id'];
$adminName = $_SESSION['name'] ?? 'Admin User';

include '../database/db.php';

$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$employeeId) {
    header('Location: staff.php');
    exit;
}

$employee = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT id, employee_id, full_name FROM employees WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();
}

if (!$employee) {
    header('Location: staff.php');
    exit;
}

$documentTypes = [
    'SSS',
    'Philhealth',
    'Pag-Ibig',
    'TIN',
    'NBI Clearance',
    'Police Clearance',
    'Bank Account',
    'Employee Agreement Contract',
    'Contractual Agreement Contract',
];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentType = trim($_POST['document_type'] ?? '');
    if (empty($documentType) || !in_array($documentType, $documentTypes, true)) {
        $error = 'Invalid document type.';
    } elseif (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        $file = $_FILES['document_file'];
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            $error = 'Invalid file type. Allowed: PDF, JPG, PNG.';
        } else {
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                $error = 'File too large. Maximum 5MB.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/employee_documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $docSlug = preg_replace('/[^a-z0-9]+/', '_', strtolower($documentType));
                $filename = $employeeId . '_' . $docSlug . '_' . time() . '.' . $ext;
                $fullPath = $uploadDir . $filename;
                if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                    $error = 'Failed to save file.';
                } else {
                    $relativePath = 'employee_documents/' . $filename;
                    $now = date('Y-m-d H:i:s');
                    $checkTable = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
                    if ($checkTable && $checkTable->num_rows > 0) {
                        $ins = $conn->prepare("INSERT INTO employee_document_uploads (employee_id, document_type, file_path, status, approved_by, approved_by_name, approved_at) VALUES (?, ?, ?, 'Approved', ?, ?, ?)");
                        $ins->bind_param('ississ', $employeeId, $documentType, $relativePath, $adminId, $adminName, $now);
                        if (!$ins->execute()) {
                            $error = 'Failed to save record.';
                        }
                        $ins->close();
                    }
                    $checkDf = $conn->query("SHOW TABLES LIKE 'document_files'");
                    if ($checkDf && $checkDf->num_rows > 0 && empty($error)) {
                        $insDf = $conn->prepare("INSERT INTO document_files (employee_id, document_type, file_path, approved_by, approved_by_name, approved_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $insDf->bind_param('ississs', $employeeId, $documentType, $relativePath, $adminId, $adminName, $now, $now);
                        $insDf->execute();
                        $insDf->close();
                    }
                    if (empty($error)) {
                        $_SESSION['staff_document_added'] = 1;
                        header('Location: staff-view.php?id=' . $employeeId);
                        exit;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen pt-16 md:pt-8 md:ml-64 px-4 md:px-8 pb-10">
        <div class="max-w-xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <a href="staff-view.php?id=<?php echo $employeeId; ?>" class="p-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Add Document</h1>
                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($employee['full_name'] ?? ''); ?> (<?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?>)</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <?php if ($error): ?>
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Document Type <span class="text-red-500">*</span></label>
                        <select name="document_type" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#d97706]/20 focus:border-[#d97706]">
                            <option value="">Select type...</option>
                            <?php foreach ($documentTypes as $dt): ?>
                                <option value="<?php echo htmlspecialchars($dt); ?>"><?php echo htmlspecialchars($dt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">File <span class="text-red-500">*</span></label>
                        <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required
                               class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-amber-50 file:text-amber-800 file:font-medium file:cursor-pointer hover:file:bg-amber-100">
                        <p class="text-xs text-slate-500 mt-1">PDF, JPG or PNG. Max 5MB.</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-[#d97706] text-white rounded-lg hover:bg-[#b45309] font-medium text-sm">
                            Add Document
                        </button>
                        <a href="staff-view.php?id=<?php echo $employeeId; ?>" class="px-5 py-2.5 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 font-medium text-sm">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
