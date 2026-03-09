<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

include '../database/db.php';

$message = $_SESSION['progressive_discipline_msg'] ?? '';
unset($_SESSION['progressive_discipline_msg']);

$records = [];
$employees = [];
$tableExists = false;

if ($conn) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'progressive_discipline_records'");
    $tableExists = $tableCheck && $tableCheck->num_rows > 0;

    $empSql = "SELECT id, employee_id, full_name, department, position FROM employees ORDER BY full_name ASC";
    $empRes = $conn->query($empSql);
    if ($empRes && $empRes->num_rows > 0) {
        while ($row = $empRes->fetch_assoc()) {
            $employees[] = $row;
        }
    }

    if ($tableExists) {
        $sql = "SELECT pdr.id, pdr.employee_id, pdr.incident_date, pdr.offense_type, pdr.discipline_level,
                       pdr.description, pdr.action_taken, pdr.status, pdr.next_review_date, pdr.created_at,
                       e.employee_id AS emp_code, e.full_name, e.department, e.position
                FROM progressive_discipline_records pdr
                LEFT JOIN employees e ON e.id = pdr.employee_id
                ORDER BY pdr.created_at DESC
                LIMIT 500";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $records[] = $row;
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
    <title>Progressive Discipline - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config = { theme: { extend: { fontFamily: { inter: ['Inter', 'sans-serif'] } } } };</script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Progressive Discipline</h1>
                <p class="text-sm text-slate-500 mt-1">Track warnings and escalation records per employee</p>
            </div>
        </div>

        <?php if (!$tableExists): ?>
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                The table is not ready yet. Run <code>database/setup_progressive_discipline_table.php</code> once, then refresh this page.
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4">Add Discipline Record</h2>
            <form method="POST" action="progressive-discipline-action.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Employee</label>
                    <select name="employee_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo (int)$emp['id']; ?>">
                                <?php echo htmlspecialchars(($emp['full_name'] ?? 'Unknown') . ' (' . ($emp['employee_id'] ?? 'N/A') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Incident Date</label>
                    <input type="date" name="incident_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Offense Type</label>
                    <input type="text" name="offense_type" maxlength="120" placeholder="Late attendance, policy violation..." required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Discipline Level</label>
                    <select name="discipline_level" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                        <option value="Verbal Warning">Verbal Warning</option>
                        <option value="Written Warning">Written Warning</option>
                        <option value="Final Warning">Final Warning</option>
                        <option value="Suspension">Suspension</option>
                        <option value="Termination">Termination</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Incident Description</label>
                    <textarea name="description" rows="3" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Action Taken / Notes</label>
                    <textarea name="action_taken" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Next Review Date (Optional)</label>
                    <input type="date" name="next_review_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-500">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700 transition-colors">
                        Save Record
                    </button>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Discipline History</h2>
                <span class="text-xs text-slate-500"><?php echo count($records); ?> record(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Employee</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Offense</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Level</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">No discipline records yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $rec): ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-600"><?php echo !empty($rec['incident_date']) ? date('M d, Y', strtotime($rec['incident_date'])) : '—'; ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-700"><?php echo htmlspecialchars($rec['full_name'] ?? 'Unknown Employee'); ?></div>
                                        <div class="text-xs text-slate-500"><?php echo htmlspecialchars($rec['emp_code'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($rec['offense_type'] ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($rec['discipline_level'] ?? '—'); ?></td>
                                    <td class="px-4 py-3">
                                        <?php
                                            $status = $rec['status'] ?? 'Active';
                                            $statusClass = 'bg-amber-100 text-amber-700';
                                            if ($status === 'Resolved') {
                                                $statusClass = 'bg-emerald-100 text-emerald-700';
                                            } elseif ($status === 'Escalated') {
                                                $statusClass = 'bg-red-100 text-red-700';
                                            }
                                        ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="progressive-discipline-action.php" class="flex gap-2 items-center">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo (int)$rec['id']; ?>">
                                            <select name="status" class="px-2 py-1 border border-slate-200 rounded text-xs">
                                                <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="Resolved" <?php echo ($status === 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                                                <option value="Escalated" <?php echo ($status === 'Escalated') ? 'selected' : ''; ?>>Escalated</option>
                                            </select>
                                            <button type="submit" class="px-2.5 py-1 rounded bg-slate-700 text-white text-xs hover:bg-slate-800">
                                                Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>
