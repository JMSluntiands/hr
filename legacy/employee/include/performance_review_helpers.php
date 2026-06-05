<?php
/**
 * Department master row must have additional_performance_review = 1
 * and match employees.department by name.
 */
function hr_department_performance_review_enabled($conn, string $departmentName): bool
{
    if (!$conn || $departmentName === '') {
        return false;
    }
    $chkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'additional_performance_review'");
    if (!$chkCol || $chkCol->num_rows === 0) {
        return false;
    }
    $stmt = $conn->prepare('SELECT additional_performance_review FROM departments WHERE name = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $departmentName);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row && !empty($row['additional_performance_review']);
}

function hr_ensure_staff_performance_reviews_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS `staff_performance_reviews` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `employee_id` INT(11) NOT NULL,
      `review_date` DATE NOT NULL,
      `staff_name` VARCHAR(255) NOT NULL,
      `supervisor_name` VARCHAR(255) NOT NULL,
      `accuracy_rating` TINYINT NOT NULL,
      `accuracy_explanation` TEXT NOT NULL,
      `cross_ref_rating` TINYINT NOT NULL,
      `cross_ref_explanation` TEXT NOT NULL,
      `comprehension_rating` TINYINT NOT NULL,
      `comprehension_explanation` TEXT NOT NULL,
      `teamwork_support_rating` TINYINT NOT NULL,
      `teamwork_support_explanation` TEXT NOT NULL,
      `initiative_learning_rating` TINYINT NOT NULL,
      `initiative_learning_explanation` TEXT NOT NULL,
      `daily_output_rating` TINYINT NOT NULL,
      `daily_output_explanation` TEXT NOT NULL,
      `task_management_rating` TINYINT NOT NULL,
      `task_management_explanation` TEXT NOT NULL,
      `communication_delays_rating` TINYINT NOT NULL,
      `communication_delays_explanation` TEXT NOT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_employee` (`employee_id`),
      KEY `idx_review_date` (`review_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    hr_staff_performance_reviews_migrate_extra_competencies($conn);
}

/** Adds five extra competency columns for existing databases (nullable until new reviews fill them). */
function hr_staff_performance_reviews_migrate_extra_competencies(mysqli $conn): void
{
    if (!$conn) {
        return;
    }
    $t = 'staff_performance_reviews';
    $chk = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($t) . "'");
    if (!$chk || $chk->num_rows === 0) {
        return;
    }
    $pairs = [
        ['teamwork_support_rating', 'TINYINT NULL'],
        ['teamwork_support_explanation', 'TEXT NULL'],
        ['initiative_learning_rating', 'TINYINT NULL'],
        ['initiative_learning_explanation', 'TEXT NULL'],
        ['daily_output_rating', 'TINYINT NULL'],
        ['daily_output_explanation', 'TEXT NULL'],
        ['task_management_rating', 'TINYINT NULL'],
        ['task_management_explanation', 'TEXT NULL'],
        ['communication_delays_rating', 'TINYINT NULL'],
        ['communication_delays_explanation', 'TEXT NULL'],
    ];
    foreach ($pairs as [$col, $def]) {
        $c = preg_replace('/[^a-z0-9_]/i', '', $col);
        if ($c === '' || $c !== $col) {
            continue;
        }
        $show = $conn->query("SHOW COLUMNS FROM `$t` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($show && $show->num_rows === 0) {
            $conn->query("ALTER TABLE `$t` ADD COLUMN `$c` $def");
        }
    }
}

/**
 * Process performance review POST. Redirects on success; returns error message string on validation/DB failure; returns null if not POST.
 */
function hr_performance_review_handle_post(mysqli $conn, int $employeeDbId, string $department, string $redirectOnSuccess): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }
    if (!hr_department_performance_review_enabled($conn, $department)) {
        header('Location: index.php');
        exit;
    }

    $reviewDate = trim($_POST['review_date'] ?? '');
    $staffEmployeeId = (int)($_POST['staff_employee_id'] ?? 0);
    $staffName = trim((string)($_POST['staff_name'] ?? ''));
    if ($staffEmployeeId > 0) {
        $st = $conn->prepare(
            'SELECT full_name FROM employees WHERE id = ? AND TRIM(COALESCE(full_name, \'\')) <> \'\' AND (status = \'Active\' OR status IS NULL) LIMIT 1'
        );
        if (!$st) {
            return 'Could not save your submission. Please try again.';
        }
        $st->bind_param('i', $staffEmployeeId);
        $st->execute();
        $resStaff = $st->get_result();
        $rowStaff = $resStaff ? $resStaff->fetch_assoc() : null;
        $st->close();
        if (!$rowStaff) {
            return 'Please select a valid employee from the list.';
        }
        $staffName = trim((string)($rowStaff['full_name'] ?? ''));
    }
    $supervisorName = trim($_POST['supervisor_name'] ?? '');
    $accuracyRating = (int)($_POST['accuracy_rating'] ?? 0);
    $accuracyExplanation = trim($_POST['accuracy_explanation'] ?? '');
    $crossRating = (int)($_POST['cross_ref_rating'] ?? 0);
    $crossExplanation = trim($_POST['cross_ref_explanation'] ?? '');
    $compRating = (int)($_POST['comprehension_rating'] ?? 0);
    $compExplanation = trim($_POST['comprehension_explanation'] ?? '');
    $teamworkRating = (int)($_POST['teamwork_support_rating'] ?? 0);
    $teamworkExplanation = trim($_POST['teamwork_support_explanation'] ?? '');
    $initLearnRating = (int)($_POST['initiative_learning_rating'] ?? 0);
    $initLearnExplanation = trim($_POST['initiative_learning_explanation'] ?? '');
    $dailyOutputRating = (int)($_POST['daily_output_rating'] ?? 0);
    $dailyOutputExplanation = trim($_POST['daily_output_explanation'] ?? '');
    $taskMgmtRating = (int)($_POST['task_management_rating'] ?? 0);
    $taskMgmtExplanation = trim($_POST['task_management_explanation'] ?? '');
    $commDelaysRating = (int)($_POST['communication_delays_rating'] ?? 0);
    $commDelaysExplanation = trim($_POST['communication_delays_explanation'] ?? '');

    $validRating = static function (int $r): bool {
        return $r >= 1 && $r <= 5;
    };

    if ($reviewDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reviewDate)) {
        return 'Please enter a valid review date.';
    }
    if ($staffName === '' || $supervisorName === '') {
        return 'Staff name and supervisor name are required.';
    }
    if (
        !$validRating($accuracyRating) || !$validRating($crossRating) || !$validRating($compRating)
        || !$validRating($teamworkRating) || !$validRating($initLearnRating) || !$validRating($dailyOutputRating)
        || !$validRating($taskMgmtRating) || !$validRating($commDelaysRating)
    ) {
        return 'Please select a rating from 1 to 5 for each competency.';
    }
    if (
        $accuracyExplanation === '' || $crossExplanation === '' || $compExplanation === ''
        || $teamworkExplanation === '' || $initLearnExplanation === '' || $dailyOutputExplanation === ''
        || $taskMgmtExplanation === '' || $commDelaysExplanation === ''
    ) {
        return 'Please provide a brief explanation for each competency.';
    }

    $stmt = $conn->prepare('INSERT INTO staff_performance_reviews (
        employee_id, review_date, staff_name, supervisor_name,
        accuracy_rating, accuracy_explanation,
        cross_ref_rating, cross_ref_explanation,
        comprehension_rating, comprehension_explanation,
        teamwork_support_rating, teamwork_support_explanation,
        initiative_learning_rating, initiative_learning_explanation,
        daily_output_rating, daily_output_explanation,
        task_management_rating, task_management_explanation,
        communication_delays_rating, communication_delays_explanation
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return 'Could not save your submission. Please try again.';
    }
    $stmt->bind_param(
        'isssisisisisisisisis',
        $employeeDbId,
        $reviewDate,
        $staffName,
        $supervisorName,
        $accuracyRating,
        $accuracyExplanation,
        $crossRating,
        $crossExplanation,
        $compRating,
        $compExplanation,
        $teamworkRating,
        $teamworkExplanation,
        $initLearnRating,
        $initLearnExplanation,
        $dailyOutputRating,
        $dailyOutputExplanation,
        $taskMgmtRating,
        $taskMgmtExplanation,
        $commDelaysRating,
        $commDelaysExplanation
    );
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: ' . $redirectOnSuccess);
        exit;
    }
    $stmt->close();
    return 'Could not save your submission. Please try again.';
}

/** Admin-flagged supervisors may use Form Review (evaluate staff + see submissions naming them). */
function hr_employee_is_performance_review_supervisor(mysqli $conn, int $employeeDbId): bool
{
    if (!$conn || $employeeDbId <= 0) {
        return false;
    }
    require_once __DIR__ . '/../../include/ensure_employees_staff_columns.php';
    ensure_employees_staff_columns($conn);
    $stmt = $conn->prepare('SELECT COALESCE(performance_review_supervisor, 0) AS v FROM employees WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $employeeDbId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row && (int)($row['v'] ?? 0) === 1;
}

/** Integer 1–5 for display, or null when the review predates that competency column. */
function hr_performance_review_rating_or_null($value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    $n = (int)$value;
    return ($n >= 1 && $n <= 5) ? $n : null;
}

/** Lowercase trimmed name for matching staff_name / supervisor_name on forms. */
function hr_performance_review_normalize_name(string $fullName): string
{
    $fullName = trim($fullName);
    return function_exists('mb_strtolower') ? mb_strtolower($fullName, 'UTF-8') : strtolower($fullName);
}

/**
 * Active employees for performance review "Name of staff" dropdown (option value = employees.id; full_name stored on submit).
 *
 * @return list<array{id: int, full_name: string, employee_id: string, department: string, position: string}>
 */
function hr_performance_review_active_staff_for_dropdown(mysqli $conn): array
{
    $out = [];
    if (!$conn) {
        return $out;
    }
    $sql = "SELECT id, full_name, employee_id, department, position FROM employees
            WHERE TRIM(COALESCE(full_name, '')) <> ''
              AND (status = 'Active' OR status IS NULL)
            ORDER BY full_name ASC";
    $res = $conn->query($sql);
    if (!$res) {
        return $out;
    }
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id' => (int)($row['id'] ?? 0),
            'full_name' => (string)($row['full_name'] ?? ''),
            'employee_id' => (string)($row['employee_id'] ?? ''),
            'department' => (string)($row['department'] ?? ''),
            'position' => (string)($row['position'] ?? ''),
        ];
    }
    return $out;
}
