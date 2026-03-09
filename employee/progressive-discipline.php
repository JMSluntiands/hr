<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

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
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#FA9800] text-white flex flex-col">
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
            <a href="index.php" data-url="index.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" data-url="profile.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>My Profile</span>
            </a>
            <a href="timeoff.php" data-url="timeoff.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span>My Time Off</span>
            </a>
            <a href="request.php" data-url="request.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <span>My Request</span>
            </a>
            <a href="compensation.php" data-url="compensation.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>My Compensation</span>
            </a>
            <a href="inventory.php" data-url="inventory.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2h-3V3H9v2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0H4m4 0v2m8-2v2" /></svg>
                <span>My Inventory</span>
            </a>
            <a href="progressive-discipline.php" data-url="progressive-discipline.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg bg-white/20 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
                <span>Progressive Discipline</span>
            </a>
            <a href="settings.php" data-url="settings.php" class="js-side-link flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span>Settings</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        </div>
    </aside>

    <main class="ml-64 min-h-screen p-8 overflow-y-auto">
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
    <script>
      $(function () {
        $('.js-side-link').on('click', function (e) {
          const url = $(this).data('url');
          if (!url) return;
          e.preventDefault();

          if (url === 'profile.php' || url === 'compensation.php' || url === 'timeoff.php' || url === 'settings.php' || url === 'progressive-discipline.php' || url === 'inventory.php') {
            window.location.href = url;
            return;
          }

          $('#main-inner').addClass('opacity-60 pointer-events-none');
          $('#main-inner').load(url + ' #main-inner > *', function () {
            $('#main-inner').removeClass('opacity-60 pointer-events-none');
          });
        });
      });
    </script>
</body>
</html>
