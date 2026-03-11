<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

include '../../database/db.php';
include '../include/employee_data.php';

// Timesheet subview (overview or fill-up)
$view = isset($_GET['view']) ? strtolower((string)$_GET['view']) : 'overview';
if ($view !== 'fillup') {
    $view = 'overview';
}

// Fill Up date-range state (max 16 days)
$fillFrom = '';
$fillTo = '';
$fillError = '';
$fillDates = [];
// Prefilled rows from database: [ 'Y-m-d' => [ ['description'=>..., 'time_start'=>..., ...], ... ] ]
$timesheetRowsByDate = [];

if ($view === 'fillup') {
    $fillFrom = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
    $fillTo   = isset($_GET['to'])   ? trim((string)$_GET['to'])   : '';

    if ($fillFrom !== '' && $fillTo !== '') {
        try {
            $fromDate = new DateTime($fillFrom);
            $toDate   = new DateTime($fillTo);

            if ($fromDate > $toDate) {
                $fillError = 'Date From cannot be after Date To.';
            } else {
                $intervalDays = (int)$fromDate->diff($toDate)->days + 1; // inclusive
                if ($intervalDays > 16) {
                    $fillError = 'Maximum range is 16 days. Please select a shorter period.';
                } else {
                    $cursor = clone $fromDate;
                    while ($cursor <= $toDate) {
                        $fillDates[] = $cursor->format('Y-m-d');
                        $cursor->modify('+1 day');
                    }

                    // Load existing timesheet entries for this employee and date range
                    if (!empty($fillDates) && isset($employeeDbId, $conn) && $conn instanceof mysqli) {
                        $minDate = $fillDates[0];
                        $maxDate = $fillDates[count($fillDates) - 1];
                        $stmtTs = $conn->prepare("
                            SELECT work_date, row_number, description, time_start, time_end, total_minutes
                            FROM employee_timesheets
                            WHERE employee_id = ?
                              AND work_date BETWEEN ? AND ?
                            ORDER BY work_date, row_number
                        ");
                        if ($stmtTs) {
                            $stmtTs->bind_param('iss', $employeeDbId, $minDate, $maxDate);
                            $stmtTs->execute();
                            $resTs = $stmtTs->get_result();
                            while ($row = $resTs->fetch_assoc()) {
                                $d = $row['work_date'];
                                if (!isset($timesheetRowsByDate[$d])) {
                                    $timesheetRowsByDate[$d] = [];
                                }
                                $timesheetRowsByDate[$d][] = [
                                    'description'  => (string)($row['description'] ?? ''),
                                    'time_start'   => (string)($row['time_start'] ?? ''),
                                    'time_end'     => (string)($row['time_end'] ?? ''),
                                    'total_minutes'=> (int)($row['total_minutes'] ?? 0),
                                ];
                            }
                            $stmtTs->close();
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $fillError = 'Invalid date range. Please select valid dates.';
        }
    }
}

// Overview state: selected month/year and day metadata
$overviewYear = (int)date('Y');
$overviewMonth = (int)date('n');
$overviewDays = [];
// Totals per day in minutes for overview: [ 'Y-m-d' => int ]
$overviewTotals = [];

if ($view === 'overview') {
    $reqYear = isset($_GET['year']) ? (int)$_GET['year'] : $overviewYear;
    $reqMonth = isset($_GET['month']) ? (int)$_GET['month'] : $overviewMonth;

    if ($reqYear > 1970 && $reqYear < 2100 && $reqMonth >= 1 && $reqMonth <= 12) {
        $overviewYear = $reqYear;
        $overviewMonth = $reqMonth;
    }

    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $overviewMonth, $overviewYear);
    $firstDate = sprintf('%04d-%02d-01', $overviewYear, $overviewMonth);
    $lastDate  = sprintf('%04d-%02d-%02d', $overviewYear, $overviewMonth, $daysInMonth);

    // Load summed totals from database for this month
    if (isset($employeeDbId, $conn) && $conn instanceof mysqli) {
        $stmtOv = $conn->prepare("
            SELECT work_date, SUM(total_minutes) AS minutes
            FROM employee_timesheets
            WHERE employee_id = ?
              AND work_date BETWEEN ? AND ?
            GROUP BY work_date
        ");
        if ($stmtOv) {
            $stmtOv->bind_param('iss', $employeeDbId, $firstDate, $lastDate);
            $stmtOv->execute();
            $resOv = $stmtOv->get_result();
            while ($row = $resOv->fetch_assoc()) {
                $overviewTotals[$row['work_date']] = (int)($row['minutes'] ?? 0);
            }
            $stmtOv->close();
        }
    }

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $overviewYear, $overviewMonth, $d);
        $dt = new DateTime($dateStr);
        $dow = (int)$dt->format('w'); // 0=Sun, 6=Sat
        $overviewDays[] = [
            'date' => $dateStr,
            'day' => $d,
            'dow' => $dow,
            'label' => $dt->format('D'),
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timesheet</title>
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
    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../../uploads/' . $employeePhoto)): ?>
                    <img src="../../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-lg font-semibold">
                        <?php echo strtoupper(substr($employeeName, 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0">
                <span class="text-sm font-medium truncate">
                    <?php echo htmlspecialchars($employeeName); ?>
                </span>
                <span class="text-[11px] text-white/80">
                    Employee
                </span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-employee-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <!-- Sidebar (fixed) -->
    <aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#FA9800] text-white flex flex-col transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center flex-shrink-0">
                <?php if (!empty($employeePhoto) && file_exists(__DIR__ . '/../../uploads/' . $employeePhoto)): ?>
                    <img src="../../uploads/<?php echo htmlspecialchars($employeePhoto); ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-2xl font-semibold text-white"><?php echo strtoupper(substr($employeeName, 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <div class="font-medium text-sm text-white truncate"><?php echo htmlspecialchars($employeeName); ?></div>
                <div class="text-xs text-white/80">Employee</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-3 text-sm">
            <div class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white bg-white/10">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm3-8h6" />
                </svg>
                <span>Timesheet</span>
            </div>
            <div class="ml-9 space-y-1">
                <a href="index.php?view=overview"
                   class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium text-white/90 hover:bg-white/10<?php echo $view === 'overview' ? ' bg-white/10' : ''; ?>">
                    <svg class="w-3.5 h-3.5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M5 8h14M7 12h10M9 16h6" />
                    </svg>
                    <span>Overview</span>
                </a>
                <a href="index.php?view=fillup"
                   class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium text-white/90 hover:bg-white/10<?php echo $view === 'fillup' ? ' bg-white/10' : ''; ?>">
                    <svg class="w-3.5 h-3.5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h4M5 5h14M7 9h10M5 19h14" />
                    </svg>
                    <span>Fill Up</span>
                </a>
            </div>
            <a href="payslip.php"
               class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
                <span>Payslip</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <a href="../../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
            <a href="../module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">
                Back to Main Menu
            </a>
        </div>
    </aside>

    <!-- Mobile sidebar backdrop -->
    <div id="employee-sidebar-backdrop" class="fixed inset-0 z-20 bg-black/40 hidden md:hidden"></div>

    <!-- Main Content -->
    <main class="min-h-screen p-4 pt-16 md:pt-8 md:ml-64 md:p-8 space-y-6 overflow-y-auto">
        <div id="main-inner">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-800">
                        Timesheet - <?php echo $view === 'fillup' ? 'Fill Up' : 'Overview'; ?>
                    </h1>
                    <?php if ($view === 'fillup'): ?>
                        <p class="text-sm text-slate-500 mt-1">
                            Choose a date range (maximum 16 days) and fill in your tasks per day.
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-slate-500 mt-1">
                            This area will show a summary overview of your timesheet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($view === 'fillup'): ?>
                <!-- Date range filter -->
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-6">
                    <form id="fillRangeForm" method="GET" class="grid gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))_auto] items-end">
                        <input type="hidden" name="view" value="fillup">
                        <div>
                            <label for="from" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                                Date From
                            </label>
                            <input
                                type="date"
                                id="from"
                                name="from"
                                value="<?php echo htmlspecialchars($fillFrom); ?>"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-[#FA9800] focus:ring-2 focus:ring-[#FA9800]/20 outline-none"
                                required
                            >
                        </div>
                        <div>
                            <label for="to" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                                Date To
                            </label>
                            <input
                                type="date"
                                id="to"
                                name="to"
                                value="<?php echo htmlspecialchars($fillTo); ?>"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-[#FA9800] focus:ring-2 focus:ring-[#FA9800]/20 outline-none"
                                required
                            >
                        </div>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#FA9800] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#d18a15] focus:outline-none focus:ring-2 focus:ring-[#FA9800]/40 focus:ring-offset-1"
                        >
                            Show Days
                        </button>
                    </form>

                    <?php if ($fillError): ?>
                        <div class="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            <?php echo htmlspecialchars($fillError); ?>
                        </div>
                    <?php elseif (!empty($fillDates)): ?>
                        <p class="mt-4 text-xs text-slate-500">
                            Showing <?php echo count($fillDates); ?> day(s)
                            from <span class="font-medium"><?php echo htmlspecialchars($fillFrom); ?></span>
                            to <span class="font-medium"><?php echo htmlspecialchars($fillTo); ?></span>.
                        </p>
                    <?php else: ?>
                        <p class="mt-4 text-xs text-slate-500">
                            Select a date range (up to 16 days) then click <span class="font-semibold">Show Days</span> to generate cards for filling up your timesheet.
                        </p>
                    <?php endif; ?>
                </section>

                <?php if (!$fillError && !empty($fillDates)): ?>
                    <!-- Save button + generated day cards -->
                    <form id="timesheetForm" class="grid grid-cols-1 md:grid-cols-2 gap-4" method="POST" action="save-timesheet.php">
                        <?php foreach ($fillDates as $day): ?>
                            <?php
                                $dateObj = new DateTime($day);
                                $dayLabel   = $dateObj->format('D');
                                $fullLabel  = $dateObj->format('M d');
                            ?>
                            <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden" data-day-card="<?php echo htmlspecialchars($day); ?>">
                                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-orange-50/80">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-[#FA9800]">
                                            <?php echo htmlspecialchars($dayLabel); ?>
                                        </span>
                                        <span class="text-sm font-semibold text-slate-800">
                                            <?php echo htmlspecialchars($fullLabel); ?>
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">Start / Finish / Total</span>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-xs">
                                        <thead>
                                            <tr class="bg-orange-50 text-slate-700 border-b border-slate-200">
                                                <th class="px-3 py-2 text-left font-semibold min-w-[200px]">Description</th>
                                                <th class="px-3 py-2 text-center font-semibold min-w-[80px]">Start</th>
                                                <th class="px-3 py-2 text-center font-semibold min-w-[80px]">Finish</th>
                                                <th class="px-3 py-2 text-center font-semibold min-w-[80px]">Totals</th>
                                            </tr>
                                        </thead>
                                        <tbody data-tbody-date="<?php echo htmlspecialchars($day); ?>">
                                            <?php
                                                $existingRows = $timesheetRowsByDate[$day] ?? [];
                                                $rowCount = max(count($existingRows), 2);
                                                for ($i = 1; $i <= $rowCount; $i++):
                                                    $rowData = $existingRows[$i - 1] ?? ['description' => '', 'time_start' => '', 'time_end' => '', 'total_minutes' => 0];
                                                    $descVal = $rowData['description'];
                                                    $startVal = $rowData['time_start'];
                                                    $endVal = $rowData['time_end'];
                                                    $totalMin = (int)$rowData['total_minutes'];
                                                    $hours = floor($totalMin / 60);
                                                    $mins  = $totalMin % 60;
                                                    $totalLabel = sprintf('%d:%02d', $hours, $mins);
                                            ?>
                                                <tr class="<?php echo $i % 2 === 0 ? 'bg-white' : 'bg-orange-50/40'; ?> border-b border-slate-100" data-row-index="<?php echo $i; ?>">
                                                    <td class="px-3 py-2 text-slate-700">
                                                        <input
                                                            type="text"
                                                            name="task_description[<?php echo htmlspecialchars($day); ?>][<?php echo $i; ?>]"
                                                            placeholder="Task <?php echo $i; ?> description"
                                                            value="<?php echo htmlspecialchars($descVal); ?>"
                                                            class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs focus:border-[#FA9800] focus:ring-1 focus:ring-[#FA9800]/30 outline-none"
                                                        >
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        <input
                                                            type="time"
                                                            name="start[<?php echo htmlspecialchars($day); ?>][<?php echo $i; ?>]"
                                                            value="<?php echo htmlspecialchars($startVal); ?>"
                                                            class="w-20 rounded-md border border-slate-200 px-2 py-1 text-xs text-center focus:border-[#FA9800] focus:ring-1 focus:ring-[#FA9800]/30 outline-none"
                                                        >
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        <input
                                                            type="time"
                                                            name="finish[<?php echo htmlspecialchars($day); ?>][<?php echo $i; ?>]"
                                                            value="<?php echo htmlspecialchars($endVal); ?>"
                                                            class="w-20 rounded-md border border-slate-200 px-2 py-1 text-xs text-center focus:border-[#FA9800] focus:ring-1 focus:ring-[#FA9800]/30 outline-none"
                                                        >
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span class="inline-flex items-center justify-center rounded-md bg-slate-50 border border-slate-200 px-2 py-1 text-[11px] text-slate-700 min-w-[56px]" data-total="1">
                                                            <?php echo htmlspecialchars($totalLabel); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/60 flex justify-end">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-100 hover:border-slate-300"
                                        data-add-row
                                        data-date="<?php echo htmlspecialchars($day); ?>"
                                    >
                                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#FA9800] text-white text-xs leading-none">+</span>
                                        <span>Add Row</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($fillDates as $day): ?>
                            <input type="hidden" name="dates[]" value="<?php echo htmlspecialchars($day); ?>">
                        <?php endforeach; ?>
                        <div class="md:col-span-2 flex justify-end pt-2">
                            <button
                                type="submit"
                                id="saveTimesheetBtn"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
                            >
                                <span>Save Timesheet</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <!-- Overview: month calendar with weekends shaded -->
                <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="view" value="overview">
                        <div>
                            <label for="overview_year" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                                Year
                            </label>
                            <select
                                id="overview_year"
                                name="year"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-[#FA9800] focus:ring-2 focus:ring-[#FA9800]/20 outline-none"
                            >
                                <?php
                                    $currentY = (int)date('Y');
                                    for ($y = $currentY - 5; $y <= $currentY + 5; $y++):
                                ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y === $overviewYear ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label for="overview_month" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                                Month
                            </label>
                            <select
                                id="overview_month"
                                name="month"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-[#FA9800] focus:ring-2 focus:ring-[#FA9800]/20 outline-none"
                            >
                                <?php
                                    for ($m = 1; $m <= 12; $m++):
                                        $dtLabel = DateTime::createFromFormat('!m', (string)$m)->format('F');
                                ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m === $overviewMonth ? 'selected' : ''; ?>>
                                        <?php echo $dtLabel; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#FA9800] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#d18a15] focus:outline-none focus:ring-2 focus:ring-[#FA9800]/40 focus:ring-offset-1"
                        >
                            Apply
                        </button>
                    </form>

                    <div class="mt-2 text-xs text-slate-500">
                        <span class="inline-block w-3 h-3 rounded bg-slate-200 align-middle mr-1"></span>
                        <span>Saturday / Sunday</span>
                    </div>

                    <div class="overflow-x-auto pt-2">
                        <table class="min-w-full border-collapse text-xs">
                            <thead>
                                <tr>
                                    <?php foreach ($overviewDays as $dayInfo): ?>
                                        <?php
                                            $isWeekend = ($dayInfo['dow'] === 0 || $dayInfo['dow'] === 6);
                                            $thClass = $isWeekend
                                                ? 'bg-slate-200 text-slate-600'
                                                : 'bg-slate-50 text-slate-700';
                                        ?>
                                        <th class="<?php echo $thClass; ?> px-2 py-2 border border-slate-200 min-w-[52px]">
                                            <div class="flex flex-col items-center gap-0.5">
                                                <span class="text-[10px] font-semibold uppercase tracking-wide">
                                                    <?php echo htmlspecialchars($dayInfo['label']); ?>
                                                </span>
                                                <span class="text-sm font-semibold">
                                                    <?php echo (int)$dayInfo['day']; ?>
                                                </span>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php foreach ($overviewDays as $dayInfo): ?>
                                        <?php
                                            $isWeekend = ($dayInfo['dow'] === 0 || $dayInfo['dow'] === 6);
                                            $tdClass = $isWeekend
                                                ? 'bg-slate-50 text-slate-400'
                                                : 'bg-white text-slate-500';

                                            $dateKey = $dayInfo['date'];
                                            $minutes = $overviewTotals[$dateKey] ?? 0;
                                            $label = '';
                                            if ($minutes > 0) {
                                                $h = floor($minutes / 60);
                                                $m = $minutes % 60;
                                                $label = sprintf('%d:%02d', $h, $m);
                                            }
                                        ?>
                                        <td
                                            class="<?php echo $tdClass; ?> px-2 py-4 border border-slate-200 align-top text-center cursor-pointer hover:bg-slate-100"
                                            data-overview-date="<?php echo htmlspecialchars($dateKey); ?>"
                                        >
                                            <?php if ($label !== ''): ?>
                                                <span class="inline-flex items-center justify-center rounded-md bg-emerald-50 border border-emerald-200 px-2 py-1 text-[11px] font-semibold text-emerald-700 min-w-[56px]">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-[11px] text-slate-300">0:00</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="mt-6">
                    <div id="overviewDetails" class="hidden">
                        <!-- Filled via AJAX when clicking a day -->
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../include/sidebar-employee.js"></script>
    <script src="function/timesheet.js"></script>
</body>
</html>

