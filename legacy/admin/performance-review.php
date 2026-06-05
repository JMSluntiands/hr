<?php
session_start();

if (!isset($_SESSION['user_id']) || (strtolower($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';

include __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../employee/include/performance_review_helpers.php';

$rows = [];
if ($conn) {
    hr_ensure_staff_performance_reviews_table($conn);
    $chk = $conn->query("SHOW TABLES LIKE 'staff_performance_reviews'");
    if ($chk && $chk->num_rows > 0) {
        $sql = 'SELECT r.*, e.full_name AS employee_full_name, e.department AS employee_department, e.employee_id AS employee_code
                FROM staff_performance_reviews r
                INNER JOIN employees e ON e.id = r.employee_id
                ORDER BY r.created_at DESC';
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
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
    <title>Staff Performance Reviews - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: { luntianBlue: '#FA9800', luntianLight: '#f3f4ff' }
                }
            }
        }
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
<?php include __DIR__ . '/include/sidebar-admin.php'; ?>

<main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-800">Staff Performance Reviews</h1>
        <p class="text-sm text-slate-500 mt-1">Submissions from employees whose department has &quot;Additional performance review&quot; enabled under Department settings.</p>
    </div>

    <section class="bg-white rounded-xl shadow-sm border border-slate-100">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-700">Submitted reviews</h2>
        </div>
        <div class="p-5 overflow-x-auto">
            <table id="perfReviewTable" class="min-w-full text-sm">
                <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Submitted</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Employee</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Department</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Review date</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Staff (form)</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Supervisor</th>
                    <th class="text-left px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-wide">Ratings (8 areas)</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No performance reviews submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 align-top">
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($r['created_at']))); ?></td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800"><?php echo htmlspecialchars($r['employee_full_name'] ?? ''); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($r['employee_code'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['employee_department'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap"><?php echo htmlspecialchars($r['review_date'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['staff_name'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['supervisor_name'] ?? ''); ?></td>
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap tabular-nums text-xs sm:text-sm">
                                <?php
                                $rk = [
                                    'accuracy_rating', 'cross_ref_rating', 'comprehension_rating',
                                    'teamwork_support_rating', 'initiative_learning_rating', 'daily_output_rating',
                                    'task_management_rating', 'communication_delays_rating',
                                ];
                                $parts = [];
                                foreach ($rk as $col) {
                                    $v = hr_performance_review_rating_or_null($r[$col] ?? null);
                                    $parts[] = $v !== null ? (string)$v : '—';
                                }
                                echo htmlspecialchars(implode(' / ', $parts));
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="mt-4 text-xs text-slate-500">Order: Accuracy · Cross-ref · Comprehension · Teamwork · Initiative to learn · Daily output · Task management · Communication of delays. Dashes indicate older rows before that competency existed. Full text is stored per submission in the database.</p>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && $('#perfReviewTable').length && $('#perfReviewTable tbody tr').length && !$('#perfReviewTable td[colspan]').length) {
        $('#perfReviewTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: { search: '', searchPlaceholder: 'Search…', emptyTable: 'No performance reviews submitted yet.' },
            dom: '<"flex flex-wrap justify-between items-center gap-2 mb-4"<"flex gap-2"l><"flex gap-2"f>>rt<"flex justify-between items-center mt-4"<"text-sm text-slate-600"i><"flex gap-2"p>>'
        });
    }
});
</script>
<script src="include/sidebar-dropdown.js"></script>
</body>
</html>
