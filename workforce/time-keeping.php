<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
require_once __DIR__ . '/../controller/session_timeout.php';

if (strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../employee/index.php');
    exit;
}

$_SESSION['admin_module'] = 'workforce';
$adminName = $_SESSION['name'] ?? 'Admin User';
$role = $_SESSION['role'] ?? 'admin';
include __DIR__ . '/../database/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Keeping - Workforce</title>
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
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-workforce.php'; ?>

    <main class="min-h-screen overflow-y-auto p-4 pt-16 md:pt-8 md:ml-64 md:p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Time Keeping</h1>
            <p class="text-sm text-slate-500 mt-1">Workforce Management System – time and attendance</p>
        </div>

        <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="flex flex-col gap-6">
                <!-- Date range filter -->
                <form id="dateFilterForm" class="grid gap-4 md:grid-cols-[repeat(2,minmax(0,1fr))_auto] items-end">
                    <div>
                        <label for="date_from" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                            Date From
                        </label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"
                        >
                    </div>
                    <div>
                        <label for="date_to" class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                            Date To
                        </label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"
                        >
                    </div>
                    <button
                        type="button"
                        id="showFormBtn"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1 disabled:bg-slate-300 disabled:cursor-not-allowed"
                    >
                        Show Time Sheet
                    </button>
                </form>

                <!-- Time keeping form -->
                <div id="timeKeepingWrapper" class="hidden border border-slate-200 rounded-xl overflow-hidden">
                    <div class="flex justify-between items-center px-4 py-3 bg-slate-50 border-b border-slate-200">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-800">Daily Time Sheet</h2>
                            <p id="selectedDateRange" class="text-xs text-slate-500"></p>
                        </div>
                        <div class="text-xs text-slate-500">
                            All times are in 24-hour format.
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs md:text-sm">
                            <thead>
                                <tr class="bg-orange-50 text-slate-700 border-b border-slate-200">
                                    <th class="px-3 py-2 text-left font-semibold min-w-[140px]">Client Name</th>
                                    <th class="px-3 py-2 text-left font-semibold min-w-[140px]">Sub-Client Name</th>
                                    <th class="px-3 py-2 text-left font-semibold min-w-[140px]">Task</th>
                                    <th class="px-3 py-2 text-left font-semibold min-w-[160px]">Description</th>
                                    <th class="px-3 py-2 text-center font-semibold min-w-[240px]" colspan="3">
                                        <div class="flex flex-col items-center">
                                            <span id="timeSheetDateLabel" class="font-semibold">Selected Date</span>
                                            <span class="text-[10px] text-slate-500">Start / Finish / Total Hours</span>
                                        </div>
                                    </th>
                                </tr>
                                <tr class="bg-orange-50 text-slate-600 border-b border-slate-200">
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="px-3 py-1 text-center font-medium border-l border-slate-200">Start</th>
                                    <th class="px-3 py-1 text-center font-medium">Finish</th>
                                    <th class="px-3 py-1 text-center font-medium border-r border-slate-200">Total Hrs</th>
                                </tr>
                            </thead>
                            <tbody id="timeSheetBody">
                                <?php for ($i = 1; $i <= 20; $i++): ?>
                                    <tr class="<?= $i % 2 === 0 ? 'bg-white' : 'bg-orange-50/40' ?> border-b border-slate-100">
                                        <td class="px-3 py-2 text-slate-700">
                                            <input
                                                type="text"
                                                name="client_name[<?= $i ?>]"
                                                placeholder="Task <?= $i ?> client"
                                                class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs md:text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none"
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-slate-700">
                                            <input
                                                type="text"
                                                name="sub_client_name[<?= $i ?>]"
                                                class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs md:text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none"
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-slate-700">
                                            <input
                                                type="text"
                                                name="task_name[<?= $i ?>]"
                                                class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs md:text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none"
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-slate-700">
                                            <input
                                                type="text"
                                                name="description[<?= $i ?>]"
                                                class="w-full rounded-md border border-slate-200 px-2 py-1 text-xs md:text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none"
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-center border-l border-slate-100">
                                            <input
                                                type="time"
                                                name="time_in[<?= $i ?>]"
                                                class="w-24 md:w-28 rounded-md border border-slate-200 px-2 py-1 text-xs md:text-sm text-center focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none time-input"
                                                data-row="<?= $i ?>"
                                                data-type="in"
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input
                                                type="time"
                                                name="time_out[<?= $i ?>]"
                                                class="w-24 md:w-28 rounded-md border border-slate-200 px-2 py-1 text-xs md:text-sm text-center focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 outline-none time-input"
                                                data-row="<?= $i ?>"
                                                data-type="out"
                                            >
                                        </td>
                                        <td class="px-3 py-2 text-center border-r border-slate-100">
                                            <span
                                                id="total_hrs_<?= $i ?>"
                                                class="inline-flex items-center justify-center rounded-md bg-slate-50 border border-slate-200 px-2 py-1 text-[11px] md:text-xs text-slate-700 min-w-[64px]"
                                            >
                                                0:00
                                            </span>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 border-t border-slate-200">
                                    <td colspan="4" class="px-3 py-3 text-right text-xs md:text-sm font-semibold text-slate-700">
                                        Total hrs
                                    </td>
                                    <td colspan="3" class="px-3 py-3 text-center">
                                        <span
                                            id="grandTotalHrs"
                                            class="inline-flex items-center justify-center rounded-md bg-indigo-50 border border-indigo-200 px-3 py-1.5 text-xs md:text-sm font-semibold text-indigo-700 min-w-[80px]"
                                        >
                                            0:00
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="../admin/include/sidebar-dropdown.js"></script>
    <script>
        (function () {
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const showFormBtn = document.getElementById('showFormBtn');
            const wrapper = document.getElementById('timeKeepingWrapper');
            const dateRangeLabel = document.getElementById('selectedDateRange');
            const dateHeaderLabel = document.getElementById('timeSheetDateLabel');
            const timeInputs = document.querySelectorAll('.time-input');
            const grandTotalEl = document.getElementById('grandTotalHrs');

            function formatMinutesToHHMM(totalMinutes) {
                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;
                return hours + ':' + (minutes < 10 ? '0' + minutes : minutes);
            }

            function computeRowTotal(row) {
                const inInput = document.querySelector('input[data-row="' + row + '"][data-type="in"]');
                const outInput = document.querySelector('input[data-row="' + row + '"][data-type="out"]');
                const totalEl = document.getElementById('total_hrs_' + row);

                if (!inInput || !outInput || !totalEl) return 0;

                const timeIn = inInput.value;
                const timeOut = outInput.value;

                if (!timeIn || !timeOut) {
                    totalEl.textContent = '0:00';
                    return 0;
                }

                const [inH, inM] = timeIn.split(':').map(Number);
                const [outH, outM] = timeOut.split(':').map(Number);

                let startMinutes = inH * 60 + inM;
                let endMinutes = outH * 60 + outM;

                // If time-out is past midnight compared to time-in, roll to next day
                if (endMinutes < startMinutes) {
                    endMinutes += 24 * 60;
                }

                const diff = Math.max(endMinutes - startMinutes, 0);
                totalEl.textContent = formatMinutesToHHMM(diff);
                return diff;
            }

            function recomputeGrandTotal() {
                let total = 0;
                for (let i = 1; i <= 20; i++) {
                    total += computeRowTotal(i);
                }
                grandTotalEl.textContent = formatMinutesToHHMM(total);
            }

            if (showFormBtn) {
                showFormBtn.addEventListener('click', function () {
                    const fromVal = dateFrom.value;
                    const toVal = dateTo.value;

                    if (!fromVal || !toVal) {
                        alert('Please select both "Date From" and "Date To".');
                        return;
                    }

                    if (fromVal > toVal) {
                        alert('"Date From" cannot be after "Date To".');
                        return;
                    }

                    wrapper.classList.remove('hidden');

                    if (fromVal === toVal) {
                        const date = new Date(fromVal);
                        const options = { weekday: 'short', month: 'short', day: 'numeric' };
                        const label = date.toLocaleDateString(undefined, options);
                        dateRangeLabel.textContent = 'For ' + label;
                        dateHeaderLabel.textContent = label;
                    } else {
                        const fromDate = new Date(fromVal);
                        const toDate = new Date(toVal);
                        const options = { month: 'short', day: 'numeric' };
                        const fromLabel = fromDate.toLocaleDateString(undefined, options);
                        const toLabel = toDate.toLocaleDateString(undefined, options);
                        dateRangeLabel.textContent = 'For ' + fromLabel + ' – ' + toLabel;
                        dateHeaderLabel.textContent = 'Selected Range';
                    }
                });
            }

            timeInputs.forEach(function (input) {
                input.addEventListener('change', recomputeGrandTotal);
                input.addEventListener('keyup', recomputeGrandTotal);
            });
        })();
    </script>
</body>
</html>
