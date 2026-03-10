<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$employees = [];
$nextId = '';
$format = 'EMP-' . date('Ymd') . '-XXX';

if ($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'employees'");
    if ($check && $check->num_rows > 0) {
        $cols = [];
        $r = $conn->query("SHOW COLUMNS FROM employees");
        if ($r) {
            while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
        }
        $hasEmployeeId = in_array('employee_id', $cols);
        if ($hasEmployeeId) {
            $res = $conn->query("SELECT id, employee_id, full_name, email, department, position, date_hired, status, profile_picture FROM employees ORDER BY id DESC");
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $employees[] = $row;
                }
            }
            // Next ID: same logic as staff-add (EMP-YYYYMMDD-XXX)
            $nextId = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
            $stmt->bind_param('s', $nextId);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            if ($exists) {
                $nextId = 'EMP-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
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
    <title>ID Creation - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">ID Creation</h1>
                <p class="text-sm text-slate-500 mt-1">Employee ID format and list of assigned IDs</p>
            </div>
        </div>

        <!-- ID Format & Next ID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Current ID Format</h2>
                <p class="text-slate-600 font-mono text-lg"><?php echo htmlspecialchars($format); ?></p>
                <p class="text-xs text-slate-500 mt-2">Format: EMP-YYYYMMDD-XXX (date + 3-digit sequence)</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Next ID (for new employee)</h2>
                <div class="flex items-center gap-3">
                    <span class="font-mono text-lg font-medium text-[#FA9800]"><?php echo htmlspecialchars($nextId ?: '—'); ?></span>
                    <?php if ($nextId): ?>
                    <button type="button" id="copyNextId" class="px-3 py-1.5 text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors">Copy</button>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-2">This ID will be auto-assigned when you add a new employee from Add New Employee.</p>
            </div>
        </div>

        <!-- Employees with IDs -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Employees & Assigned IDs</h2>
            </div>
            <div class="p-6 overflow-x-auto">
                <table id="idTable" class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Full Name</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Department</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Position</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No employees found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono font-medium text-slate-800"><?php echo htmlspecialchars($emp['employee_id'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($emp['full_name'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($emp['department'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($emp['position'] ?? '—'); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo (($emp['status'] ?? '') === 'Active') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo htmlspecialchars($emp['status'] ?? '—'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="id-card-print.php?id=<?php echo (int)($emp['id'] ?? 0); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#FA9800] hover:bg-[#d97706] text-white text-xs font-medium rounded-lg transition-colors">View ID</a>
                                <a href="staff-view.php?id=<?php echo (int)($emp['id'] ?? 0); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors ml-1">Profile</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            var table = $('#idTable');
            if (table.find('tbody tr').length > 0 && !table.find('tbody td').first().text().includes('No employees')) {
                table.DataTable({
                    pageLength: 15,
                    order: [[0, 'desc']],
                    language: { search: "", searchPlaceholder: "Search ID or name...", emptyTable: "No employees found." }
                });
            }
        });
        document.getElementById('copyNextId') && document.getElementById('copyNextId').addEventListener('click', function() {
            var id = '<?php echo addslashes($nextId); ?>';
            navigator.clipboard.writeText(id).then(function() {
                var btn = document.getElementById('copyNextId');
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
            });
        });
    </script>
    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
