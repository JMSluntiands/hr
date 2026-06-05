<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

include '../database/db.php';
include 'include/employee_data.php';

$records = [];
$tableExists = false;
$levelOrder = [
    'Verbal Warning' => 1,
    'Written Warning' => 2,
    'Final Warning' => 3,
    'Suspension' => 4,
    'Termination' => 5
];
$highestLevel = 0;
$latestStatus = 'No Record';

if ($employeeDbId && $conn) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'progressive_discipline_records'");
    $tableExists = $checkTable && $checkTable->num_rows > 0;

    if ($tableExists) {
        $stmt = $conn->prepare("SELECT incident_date, offense_type, discipline_level, description, action_taken, status, created_at
                                FROM progressive_discipline_records
                                WHERE employee_id = ?
                                ORDER BY incident_date DESC, created_at DESC");
        if ($stmt) {
            $stmt->bind_param('i', $employeeDbId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $records[] = $row;
                $lvl = $levelOrder[$row['discipline_level'] ?? ''] ?? 0;
                if ($lvl > $highestLevel) {
                    $highestLevel = $lvl;
                }
            }
            $stmt->close();
        }
    }
}

if (!empty($records)) {
    $latestStatus = $records[0]['status'] ?? 'Active';
}

$progressPercent = ($highestLevel > 0) ? (int)round(($highestLevel / 5) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progressive Discipline</title>
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
    <?php include __DIR__ . '/include/sidebar-employee-unified.php'; ?>



    <main class="min-h-screen p-8 overflow-y-auto md:ml-64 md:pt-8 pt-16">
        <div id="main-inner">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">My Progressive Discipline</h1>
                    <p class="text-sm text-slate-500 mt-1">View your discipline level and record history</p>
                </div>
            </div>

            <?php if (!$tableExists): ?>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Progressive discipline module is not yet enabled. Please contact HR/Admin.
                </div>
            <?php endif; ?>

            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-700">Discipline Level Progress</h2>
                    <span class="text-xs text-slate-500">Current: Level <?php echo (int)$highestLevel; ?>/5</span>
                </div>
                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden mb-3">
                    <div class="h-full bg-amber-500" style="width: <?php echo (int)$progressPercent; ?>%;"></div>
                </div>
                <div class="grid grid-cols-5 gap-2 text-[11px] text-center text-slate-500">
                    <span>Verbal</span>
                    <span>Written</span>
                    <span>Final</span>
                    <span>Suspension</span>
                    <span>Termination</span>
                </div>
                <div class="mt-4 text-sm text-slate-600">
                    Latest status:
                    <?php
                        $badgeClass = 'bg-slate-100 text-slate-700';
                        if ($latestStatus === 'Active') {
                            $badgeClass = 'bg-amber-100 text-amber-700';
                        } elseif ($latestStatus === 'Resolved') {
                            $badgeClass = 'bg-emerald-100 text-emerald-700';
                        } elseif ($latestStatus === 'Escalated') {
                            $badgeClass = 'bg-red-100 text-red-700';
                        }
                    ?>
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($latestStatus); ?>
                    </span>
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">My Discipline List</h2>
                    <span class="text-xs text-slate-500"><?php echo count($records); ?> record(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Date</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Offense</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Level</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Status</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600 uppercase">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No discipline record found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                    <?php
                                        $status = $record['status'] ?? 'Active';
                                        $statusClass = 'bg-amber-100 text-amber-700';
                                        if ($status === 'Resolved') {
                                            $statusClass = 'bg-emerald-100 text-emerald-700';
                                        } elseif ($status === 'Escalated') {
                                            $statusClass = 'bg-red-100 text-red-700';
                                        }
                                    ?>
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600"><?php echo !empty($record['incident_date']) ? date('M d, Y', strtotime($record['incident_date'])) : '—'; ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($record['offense_type'] ?? '—'); ?></td>
                                        <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($record['discipline_level'] ?? '—'); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <div class="max-w-md">
                                                <p><?php echo htmlspecialchars($record['description'] ?? ''); ?></p>
                                                <?php if (!empty($record['action_taken'])): ?>
                                                    <p class="text-xs text-slate-500 mt-1">Action: <?php echo htmlspecialchars($record['action_taken']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/sidebar-dropdown.js"></script>
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

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });
      });
    </script>
</body>
</html>
