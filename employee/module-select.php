<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$role = strtolower((string)($_SESSION['role'] ?? ''));

// Only employees should use this selector.
if ($role !== 'employee') {
    if ($role === 'admin') {
        header('Location: ../admin/module-select.php');
        exit;
    }
    header('Location: ../index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module = strtolower(trim((string)($_POST['module'] ?? '')));

    if ($module === 'profile') {
        $_SESSION['employee_module'] = 'profile';
        header('Location: profile.php');
        exit;
    }

    if ($module === 'timekeeping') {
        $_SESSION['employee_module'] = 'timekeeping';
        header('Location: timekeeping/index.php');
        exit;
    }

    $error = 'Please choose a valid module.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Module</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] }
                }
            }
        };
    </script>
</head>
<body class="font-inter min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1e1e2d] via-[#1e1e2d] to-[#FA9800] p-4">
    <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-6 md:p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">
            Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Employee'); ?>
        </h1>
        <p class="text-slate-600 mb-6">Choose which module you want to open.</p>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg bg-red-50 text-red-700 text-sm px-4 py-3 border border-red-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-3">
            <button
                type="submit"
                name="module"
                value="timekeeping"
                class="w-full text-left p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition"
            >
                <span class="block text-base font-semibold text-slate-800">Time Keeping</span>
                <span class="block text-sm text-slate-500">
                    File and review your time off and attendance-related records.
                </span>
            </button>

            <button
                type="submit"
                name="module"
                value="profile"
                class="w-full text-left p-4 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition"
            >
                <span class="block text-base font-semibold text-slate-800">My Profile</span>
                <span class="block text-sm text-slate-500">
                    View and update your personal information and employment details.
                </span>
            </button>
        </form>
    </div>
</body>
</html>

