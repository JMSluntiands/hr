<?php
/**
 * Staff performance review form fields. Expects: $formAction, $defaultStaffName, $defaultSupervisorName
 * Optional: $defaultStaffEmployeeId (employees.id when using dropdown), $staffDropdownRows.
 */
$formAction = $formAction ?? 'performance.php';
$defaultStaffName = $defaultStaffName ?? '';
$defaultStaffEmployeeId = (int)($defaultStaffEmployeeId ?? 0);
$defaultSupervisorName = $defaultSupervisorName ?? '';
$selfReviewMode = !empty($selfReviewMode);

require_once __DIR__ . '/performance_review_helpers.php';

if (!isset($staffDropdownRows) && isset($conn) && $conn instanceof mysqli) {
    $staffDropdownRows = hr_performance_review_active_staff_for_dropdown($conn);
} else {
    $staffDropdownRows = $staffDropdownRows ?? [];
}

if ($defaultStaffEmployeeId <= 0 && $defaultStaffName !== '' && !empty($staffDropdownRows)) {
    $dn = hr_performance_review_normalize_name($defaultStaffName);
    foreach ($staffDropdownRows as $_sr) {
        if (hr_performance_review_normalize_name(trim((string)($_sr['full_name'] ?? ''))) === $dn) {
            $defaultStaffEmployeeId = (int)($_sr['id'] ?? 0);
            break;
        }
    }
}

$fieldClass = 'mt-2 w-full rounded-2xl border border-slate-200/90 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition-shadow placeholder:text-slate-400 focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-500/15';
$selectClass = $fieldClass . ' cursor-pointer bg-white';
$labelClass = 'text-xs font-semibold uppercase tracking-wide text-slate-500';
$reqStar = '<span class="ml-0.5 text-rose-500" aria-hidden="true">*</span>';

$likertShort = ['Poor', 'Fair', 'OK', 'Good', 'Excellent'];

$likert = static function (string $name, $postVal) use ($likertShort) {
    $v = isset($_POST[$name]) ? (int)$_POST[$name] : (int)$postVal;
    ob_start();
    ?>
    <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-b from-slate-50/80 to-white p-4 sm:p-5">
        <p class="mb-3 text-center text-[11px] font-medium uppercase tracking-widest text-slate-400">Select one rating</p>
        <div class="grid grid-cols-5 gap-2 sm:gap-3">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <label class="relative min-w-0 cursor-pointer">
                    <input type="radio" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo $i; ?>"
                           class="peer sr-only" <?php echo ($v === $i) ? 'checked' : ''; ?> <?php echo $i === 1 ? 'required' : ''; ?>>
                    <span class="flex min-h-[4.25rem] flex-col items-center justify-center gap-0.5 rounded-2xl border-2 border-slate-200/90 bg-white py-2.5 px-1 text-center text-slate-700 shadow-sm transition-all duration-200 hover:border-amber-300 hover:shadow-md peer-checked:border-[#FA9800] peer-checked:bg-gradient-to-br peer-checked:from-amber-50 peer-checked:to-orange-50/90 peer-checked:text-amber-900 peer-checked:shadow-lg peer-checked:shadow-amber-900/10 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400 peer-focus-visible:ring-offset-2">
                        <span class="text-xl font-bold tabular-nums"><?php echo $i; ?></span>
                        <span class="hidden text-[10px] font-medium leading-tight opacity-75 sm:block"><?php echo htmlspecialchars($likertShort[$i - 1]); ?></span>
                    </span>
                </label>
            <?php endfor; ?>
        </div>
        <div class="mt-3 flex justify-between px-1 text-[11px] font-medium text-slate-400">
            <span>Needs improvement</span>
            <span>Excellent</span>
        </div>
    </div>
    <?php
    return ob_get_clean();
};

$competencyCard = static function (
    string $title,
    string $question,
    string $fieldName,
    $postVal,
    string $explanationName,
    string $explanationVal,
    array $rubricRows
) use ($likert, $fieldClass, $labelClass, $reqStar) {
    ob_start();
    ?>
    <section class="group overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-md shadow-slate-200/40 ring-1 ring-slate-100/80 transition-shadow hover:shadow-lg hover:shadow-slate-200/50">
        <div class="h-1.5 bg-gradient-to-r from-amber-400 via-[#FA9800] to-orange-500"></div>
        <div class="space-y-5 p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h2 class="max-w-prose text-lg font-bold leading-snug text-slate-800 sm:text-xl"><?php echo htmlspecialchars($title); ?><?php echo $reqStar; ?></h2>
            </div>
            <p class="text-sm leading-relaxed text-slate-600 sm:text-[15px]"><?php echo htmlspecialchars($question); ?></p>
            <?php echo $likert($fieldName, $postVal); ?>

            <details class="group overflow-hidden rounded-2xl border border-amber-100/80 bg-amber-50/40 open:shadow-inner">
                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-amber-900 transition-colors hover:bg-amber-100/50 [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-200/60 text-amber-900">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </span>
                            View rating rubric
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-amber-700/70 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </summary>
                <div class="border-t border-amber-100/80 px-3 pb-3 pt-1">
                    <div class="max-h-[min(24rem,50vh)] overflow-auto rounded-xl">
                        <table class="min-w-full border-separate border-spacing-0 text-left text-xs text-slate-700">
                            <thead>
                                <tr>
                                    <th class="sticky top-0 z-[1] whitespace-nowrap rounded-tl-xl bg-slate-100 px-3 py-2.5 font-semibold text-slate-700">#</th>
                                    <th class="sticky top-0 z-[1] whitespace-nowrap bg-slate-100 px-3 py-2.5 font-semibold text-slate-700">Label</th>
                                    <th class="sticky top-0 z-[1] bg-slate-100 px-3 py-2.5 font-semibold text-slate-700">Definition</th>
                                    <th class="sticky top-0 z-[1] rounded-tr-xl bg-slate-100 px-3 py-2.5 font-semibold text-slate-700">Criteria</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rubricRows as $idx => $row): ?>
                                    <tr class="<?php echo $idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/80'; ?>">
                                        <td class="border-b border-slate-100 px-3 py-2 align-top font-bold text-amber-800"><?php echo htmlspecialchars($row[0]); ?></td>
                                        <td class="border-b border-slate-100 px-3 py-2 align-top font-medium text-slate-800"><?php echo htmlspecialchars($row[1]); ?></td>
                                        <td class="border-b border-slate-100 px-3 py-2 align-top leading-relaxed"><?php echo htmlspecialchars($row[2]); ?></td>
                                        <td class="border-b border-slate-100 px-3 py-2 align-top leading-relaxed text-slate-600"><?php echo htmlspecialchars($row[3]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>

            <div>
                <label class="<?php echo $labelClass; ?>">Brief explanation for this rating<?php echo $reqStar; ?></label>
                <textarea name="<?php echo htmlspecialchars($explanationName); ?>" rows="3" required placeholder="Share specific examples or observations…"
                          class="<?php echo $fieldClass; ?> min-h-[5.5rem] resize-y"><?php echo htmlspecialchars($explanationVal); ?></textarea>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
};

$rubricAccuracy = [
    ['1', 'Needs Improvement', 'Frequently overlooks details, resulting in errors that affect task quality or timelines.', 'Regularly misses key steps or data. Needs frequent correction and supervision. Task outcomes often require rework or clarification.'],
    ['2', 'Fair', 'Sometimes misses minor details; shows inconsistent attention to accuracy.', 'Occasional inaccuracies or omissions. May complete tasks adequately, but not always reliably. Errors may cause minor disruption or delays.'],
    ['3', 'Satisfactory', 'Completes most tasks accurately; minor issues may occur but rarely impact overall performance.', 'Generally attentive to details. Errors are infrequent and low-impact. Demonstrates understanding of procedures but may need reminders.'],
    ['4', 'Very Good', 'Delivers accurate and thorough work with rare mistakes; shows strong attention to both major and minor details.', 'Demonstrates precision and consistency. Rarely needs corrections. Proactively double-checks work to ensure accuracy.'],
    ['5', 'Excellent', 'Consistently exceeds accuracy expectations; ensures flawless execution with attention to all critical and minor details.', 'Work is exemplary and error-free. Demonstrates mastery of procedures and standards. Anticipates potential errors and prevents them before they occur.'],
];

$rubricCross = [
    ['1', 'Needs Improvement', 'Rarely cross-checks sources or fails to identify inconsistencies, leading to frequent inaccuracies or reliance on incomplete information.', 'Reluctant or unable to verify data. Makes assumptions without support. Misses discrepancies between sources. Frequently uses outdated or incorrect information.'],
    ['2', 'Fair', 'Occasionally cross-references, but may overlook critical differences or fail to confirm accuracy thoroughly.', 'Inconsistent in verifying sources. Tends to accept the first source found. Needs reminders to validate information from multiple resources.'],
    ['3', 'Satisfactory', 'Generally verifies information using multiple sources, with minor gaps or occasional oversight.', 'Often checks for consistency. Uses reference materials when prompted. May miss subtle inconsistencies but usually catches significant errors.'],
    ['4', 'Very Good', 'Effectively cross-checks information from different resources, identifying and resolving most inconsistencies.', 'Routinely confirms data accuracy. Demonstrates analytical thinking when comparing sources. Investigates discrepancies independently.'],
    ['5', 'Excellent', 'Consistently and proactively cross-references multiple sources to ensure complete accuracy; flags even minor inconsistencies with strong judgment.', 'Demonstrates a systematic approach to validation. Uses a wide range of reliable sources. Anticipates issues and resolves discrepancies before they affect outcomes.'],
];

$rubricComp = [
    ['1', 'Needs Improvement', 'Frequently misunderstands or misinterprets instructions, requiring repeated clarification and supervision.', 'Often completes tasks incorrectly due to misunderstanding. Needs constant guidance and reminders. Delays work progress due to confusion or incorrect assumptions.'],
    ['2', 'Fair', 'Occasionally misunderstands instructions or skips steps, requiring some follow-up or correction.', 'Sometimes asks for clarification after the task has already begun. Misses minor elements of the instruction. Needs moderate supervision to stay on track.'],
    ['3', 'Satisfactory', 'Generally understands and follows instructions with minimal issues or clarification needed.', 'Asks questions when unsure before starting. Completes tasks accurately most of the time. May need occasional guidance for complex or new tasks.'],
    ['4', 'Very Good', 'Fully grasps instructions and carries them out correctly with rare need for clarification.', 'Proactively confirms unclear parts before starting. Carries out detailed or complex instructions accurately. Shows good judgment when minor interpretation is needed.'],
    ['5', 'Excellent', 'Demonstrates a strong ability to quickly understand and execute all instructions correctly and independently.', 'Immediately grasps complex directions. Requires little to no follow-up or supervision. Consistently delivers work that aligns with expectations the first time.'],
];

$rubricTeamwork = [
    ['1', 'Needs Improvement', 'Rarely collaborates; may resist helping peers or undermines team cohesion.', 'Works in isolation. Dismisses requests for support. Creates friction or confusion for teammates.'],
    ['2', 'Fair', 'Inconsistent collaboration; helps when convenient but does not reliably support others.', 'Sometimes shares information late or partially. Needs prompting to assist. Team outcomes are uneven.'],
    ['3', 'Satisfactory', 'Generally works well with others and offers reasonable support when asked.', 'Participates in team tasks. Respects others’ roles. Occasionally needs reminders to communicate or coordinate.'],
    ['4', 'Very Good', 'Actively contributes to team success; shares knowledge and helps stabilize workload.', 'Offers help without being asked at times. Communicates clearly with peers. Builds trust through reliability.'],
    ['5', 'Excellent', 'Strengthens the whole team; models cooperation, empathy, and constructive support.', 'Anticipates teammates’ needs. Mediates issues constructively. Consistently elevates group performance.'],
];

$rubricInitiativeLearn = [
    ['1', 'Needs Improvement', 'Shows little curiosity; rarely seeks clarification or learning opportunities.', 'Waits passively for instruction. Questions are vague or absent when stuck. Repeats mistakes.'],
    ['2', 'Fair', 'Sometimes asks questions but often too late or too generic to improve outcomes.', 'Occasionally reviews resources. Learning is reactive rather than intentional.'],
    ['3', 'Satisfactory', 'Asks relevant questions and uses feedback to improve most of the time.', 'Seeks guidance when blocked. Demonstrates willingness to learn procedures and context.'],
    ['4', 'Very Good', 'Proactively explores resources and asks thoughtful questions that advance the work.', 'Connects learning to tasks. Retains and applies new information with limited repetition.'],
    ['5', 'Excellent', 'Drives own growth; asks insightful questions and continuously raises the quality of discussion.', 'Shares learning with others. Anticipates knowledge gaps and closes them independently.'],
];

$rubricDailyOutput = [
    ['1', 'Needs Improvement', 'Regularly falls short of expected daily output without clear mitigation.', 'Misses volume or pace targets often. Work piles up or handoffs are late.'],
    ['2', 'Fair', 'Meets expectations inconsistently; output varies widely day to day.', 'Sometimes completes core volume but with delays or uneven quality.'],
    ['3', 'Satisfactory', 'Usually meets daily expectations with occasional slower days.', 'Generally reliable throughput. Minor slips are recovered without major impact.'],
    ['4', 'Very Good', 'Consistently meets or slightly exceeds daily output targets.', 'Steady pace. Plans time well. Rarely requires follow-up on deadlines.'],
    ['5', 'Excellent', 'Sustains high, dependable output while maintaining quality standards.', 'Sets a strong pace others can rely on. Balances speed with accuracy exceptionally well.'],
];

$rubricTaskMgmt = [
    ['1', 'Needs Improvement', 'Poor prioritization; tasks slip, duplicated, or left unassigned.', 'Struggles to break work down. Overwhelmed or idle while priorities suffer.'],
    ['2', 'Fair', 'Some planning but frequent misordering or dropped tasks.', 'Uses lists inconsistently. Delegation or sequencing needs frequent correction.'],
    ['3', 'Satisfactory', 'Manages most tasks adequately with basic prioritization.', 'Tracks work in a simple way. Handles routine allocation reasonably well.'],
    ['4', 'Very Good', 'Plans, sequences, and adjusts workload effectively with rare issues.', 'Balances urgent vs important. Communicates capacity. Recovers quickly when plans change.'],
    ['5', 'Excellent', 'Excels at organizing work for self and others; optimizes flow and outcomes.', 'Anticipates bottlenecks. Allocates effort strategically. Models strong operational discipline.'],
];

$rubricCommDelays = [
    ['1', 'Needs Improvement', 'Fails to surface delays or problems until they become crises.', 'Hides issues, goes silent, or blames others. Surprises stakeholders with late bad news.'],
    ['2', 'Fair', 'Sometimes reports problems late or with incomplete context.', 'Communication is reactive. May minimize risks until impact is obvious.'],
    ['3', 'Satisfactory', 'Generally informs others when blocked or behind, with reasonable timing.', 'Explains situation clearly enough for others to help. Accepts accountability most of the time.'],
    ['4', 'Very Good', 'Raises risks and delays early with proposed options or next steps.', 'Transparent, professional tone. Helps team replan before deadlines are missed.'],
    ['5', 'Excellent', 'Proactive, nuanced communication on challenges; builds trust through honesty and clarity.', 'Surfaces dependencies before they break. Partners on solutions. No surprises.'],
];
?>
<form method="post" class="perf-review-form space-y-8" action="<?php echo htmlspecialchars($formAction); ?>">
    <?php if (function_exists('csrf_token')): ?>
    <input type="hidden" name="_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/30 ring-1 ring-white/60">
        <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 via-white to-amber-50/30 px-5 py-5 sm:px-7 sm:py-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#FA9800] to-orange-600 text-white shadow-lg shadow-amber-500/30">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800 sm:text-lg">Review details</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Who is being evaluated and when the review applies.</p>
                </div>
            </div>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-2 sm:gap-6 sm:p-7">
            <div class="<?php echo $selfReviewMode ? 'sm:col-span-2 sm:max-w-xs' : ''; ?>">
                <label for="perf_review_date" class="<?php echo $labelClass; ?>">Review date<?php echo $reqStar; ?></label>
                <input id="perf_review_date" type="date" name="review_date" required value="<?php echo htmlspecialchars($_POST['review_date'] ?? hr_today_ymd()); ?>" class="<?php echo $fieldClass; ?>">
            </div>
            <?php if (!$selfReviewMode): ?>
            <div>
                <label for="perf_supervisor_name" class="<?php echo $labelClass; ?>">Supervisor name<?php echo $reqStar; ?></label>
                <input id="perf_supervisor_name" type="text" name="supervisor_name" required placeholder="Supervisor on record"
                       value="<?php echo htmlspecialchars($_POST['supervisor_name'] ?? $defaultSupervisorName); ?>" class="<?php echo $fieldClass; ?>" <?php echo $selfReviewMode ? 'readonly' : ''; ?>>
            </div>
            <?php else: ?>
            <input type="hidden" name="supervisor_name" value="<?php echo htmlspecialchars($_POST['supervisor_name'] ?? $defaultSupervisorName); ?>">
            <?php endif; ?>
            <?php if (!$selfReviewMode): ?>
            <div class="sm:col-span-2">
                <label for="perf_staff_name" class="<?php echo $labelClass; ?>">Name of staff<?php echo $reqStar; ?></label>
                <?php
                $selStaffId = (int)($_POST['staff_employee_id'] ?? $defaultStaffEmployeeId);
                $selStaffManual = trim((string)($_POST['staff_name'] ?? $defaultStaffName));
                $staffIdsPresent = [];
                foreach ($staffDropdownRows as $_sr) {
                    $staffIdsPresent[(int)($_sr['id'] ?? 0)] = true;
                }
                ?>
                <?php if ($selfReviewMode): ?>
                    <input id="perf_staff_name" type="text" value="<?php echo htmlspecialchars($defaultStaffName); ?>" class="<?php echo $fieldClass; ?> bg-slate-50 text-slate-600" readonly>
                    <input type="hidden" name="staff_employee_id" value="<?php echo (int)$defaultStaffEmployeeId; ?>">
                    <input type="hidden" name="staff_name" value="<?php echo htmlspecialchars($defaultStaffName); ?>">
                    <p class="mt-1.5 text-xs text-slate-500">This self review is automatically tagged to your employee profile.</p>
                <?php elseif (!empty($staffDropdownRows)): ?>
                    <select id="perf_staff_name" name="staff_employee_id" required class="<?php echo $selectClass; ?>" autocomplete="off">
                        <option value="" disabled <?php echo $selStaffId <= 0 ? 'selected' : ''; ?>>— Select employee —</option>
                        <?php foreach ($staffDropdownRows as $sr):
                            $eid = (int)($sr['id'] ?? 0);
                            if ($eid <= 0) {
                                continue;
                            }
                            $fn = trim($sr['full_name']);
                            if ($fn === '') {
                                continue;
                            }
                            $code = trim($sr['employee_id'] ?? '');
                            $label = $code !== '' ? $fn . ' (' . $code . ')' : $fn;
                            $isSel = $selStaffId === $eid;
                            ?>
                            <option value="<?php echo $eid; ?>" <?php echo $isSel ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                        <?php if ($selStaffId > 0 && empty($staffIdsPresent[$selStaffId])): ?>
                            <option value="<?php echo (int)$selStaffId; ?>" selected>Employee #<?php echo (int)$selStaffId; ?> (previous selection)</option>
                        <?php endif; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Choose an active employee. The review stores their <strong class="font-medium text-slate-600">full name</strong> from HR so it matches &ldquo;My performance review.&rdquo;</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <span class="<?php echo $labelClass; ?>">Department</span>
                            <input type="text" id="perf_staff_dept" readonly tabindex="-1" class="<?php echo $fieldClass; ?> cursor-default bg-slate-50 text-slate-600" value="">
                        </div>
                        <div>
                            <span class="<?php echo $labelClass; ?>">Position</span>
                            <input type="text" id="perf_staff_position" readonly tabindex="-1" class="<?php echo $fieldClass; ?> cursor-default bg-slate-50 text-slate-600" value="">
                        </div>
                        <div>
                            <span class="<?php echo $labelClass; ?>">Employee ID</span>
                            <input type="text" id="perf_staff_code" readonly tabindex="-1" class="<?php echo $fieldClass; ?> cursor-default bg-slate-50 text-slate-600" value="">
                        </div>
                    </div>
                    <?php
                    $staffMeta = [];
                    foreach ($staffDropdownRows as $sr) {
                        $eid = (int)($sr['id'] ?? 0);
                        if ($eid <= 0) {
                            continue;
                        }
                        $staffMeta[(string)$eid] = [
                            'department' => trim((string)($sr['department'] ?? '')),
                            'position' => trim((string)($sr['position'] ?? '')),
                            'code' => trim((string)($sr['employee_id'] ?? '')),
                        ];
                    }
                    ?>
                    <script type="application/json" id="perf-staff-meta"><?php echo json_encode($staffMeta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?></script>
                    <script>
                    (function () {
                        var sel = document.getElementById('perf_staff_name');
                        var el = document.getElementById('perf-staff-meta');
                        if (!sel || !el) return;
                        var meta = {};
                        try { meta = JSON.parse(el.textContent || '{}'); } catch (e) { return; }
                        var d = document.getElementById('perf_staff_dept');
                        var p = document.getElementById('perf_staff_position');
                        var c = document.getElementById('perf_staff_code');
                        function sync() {
                            var m = meta[String(sel.value)] || {};
                            if (d) d.value = m.department || '—';
                            if (p) p.value = m.position || '—';
                            if (c) c.value = m.code || '—';
                        }
                        sel.addEventListener('change', sync);
                        sync();
                    })();
                    </script>
                <?php else: ?>
                    <input id="perf_staff_name" type="text" name="staff_name" required placeholder="Person being evaluated"
                           value="<?php echo htmlspecialchars($selStaffManual); ?>" class="<?php echo $fieldClass; ?>">
                    <p class="mt-1.5 text-xs text-amber-800">Employee list could not be loaded. Enter the staff member&rsquo;s full name manually (must match HR records).</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <input type="hidden" name="staff_employee_id" value="<?php echo (int)($_POST['staff_employee_id'] ?? $defaultStaffEmployeeId); ?>">
            <input type="hidden" name="staff_name" value="<?php echo htmlspecialchars($_POST['staff_name'] ?? $defaultStaffName); ?>">
            <?php endif; ?>
        </div>
    </section>

    <div class="relative py-2">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-slate-200/90"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-[#f1f5f9] px-4 text-xs font-semibold uppercase tracking-widest text-slate-400">Competencies</span>
        </div>
    </div>

    <?php
    echo $competencyCard(
        'Accuracy in task execution',
        'Does the employee consistently ensure that no critical or minor details are missed when performing tasks?',
        'accuracy_rating',
        $_POST['accuracy_rating'] ?? 0,
        'accuracy_explanation',
        $_POST['accuracy_explanation'] ?? '',
        $rubricAccuracy
    );
    echo $competencyCard(
        'Cross-referencing resources',
        'Is the employee able to effectively verify and cross-check resources and information to ensure accuracy?',
        'cross_ref_rating',
        $_POST['cross_ref_rating'] ?? 0,
        'cross_ref_explanation',
        $_POST['cross_ref_explanation'] ?? '',
        $rubricCross
    );
    echo $competencyCard(
        'Comprehension of instructions',
        'Does the employee fully grasp instructions and follow through without requiring excessive guidance or corrections?',
        'comprehension_rating',
        $_POST['comprehension_rating'] ?? 0,
        'comprehension_explanation',
        $_POST['comprehension_explanation'] ?? '',
        $rubricComp
    );
    echo $competencyCard(
        'Teamwork and support',
        'Does the employee collaborate effectively, help colleagues, and contribute positively to the team?',
        'teamwork_support_rating',
        $_POST['teamwork_support_rating'] ?? 0,
        'teamwork_support_explanation',
        $_POST['teamwork_support_explanation'] ?? '',
        $rubricTeamwork
    );
    echo $competencyCard(
        'Initiative to learn and ask meaningful questions',
        'Does the employee seek to understand, ask useful questions, and apply learning to improve their work?',
        'initiative_learning_rating',
        $_POST['initiative_learning_rating'] ?? 0,
        'initiative_learning_explanation',
        $_POST['initiative_learning_explanation'] ?? '',
        $rubricInitiativeLearn
    );
    echo $competencyCard(
        'Meeting daily output expectations',
        'Does the employee reliably meet the expected volume, pace, or throughput for their role day to day?',
        'daily_output_rating',
        $_POST['daily_output_rating'] ?? 0,
        'daily_output_explanation',
        $_POST['daily_output_explanation'] ?? '',
        $rubricDailyOutput
    );
    echo $competencyCard(
        'Task management and allocation',
        'Does the employee prioritize, organize, and allocate tasks (their own or shared work) effectively?',
        'task_management_rating',
        $_POST['task_management_rating'] ?? 0,
        'task_management_explanation',
        $_POST['task_management_explanation'] ?? '',
        $rubricTaskMgmt
    );
    echo $competencyCard(
        'Communication of delays or challenges',
        'Does the employee promptly and clearly communicate blockers, risks, or delays so others can respond?',
        'communication_delays_rating',
        $_POST['communication_delays_rating'] ?? 0,
        'communication_delays_explanation',
        $_POST['communication_delays_explanation'] ?? '',
        $rubricCommDelays
    );
    ?>

    <div class="flex flex-col items-stretch gap-4 rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 p-5 shadow-md sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <p class="text-sm text-slate-600"><span class="font-semibold text-slate-800">Ready to submit?</span> Double-check ratings and explanations before sending.</p>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#FA9800] to-orange-600 px-8 py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:from-orange-500 hover:to-orange-700 hover:shadow-xl hover:shadow-amber-500/30 focus:outline-none focus:ring-4 focus:ring-amber-400/40 active:scale-[0.98]">
            <svg class="h-5 w-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Submit review
        </button>
    </div>
</form>
