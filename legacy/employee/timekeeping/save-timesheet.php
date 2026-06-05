<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

include '../../database/db.php';
include '../include/employee_data.php';

if (!$conn || !$employeeDbId) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to resolve employee record.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$dates       = isset($_POST['dates']) && is_array($_POST['dates']) ? $_POST['dates'] : [];
$descs       = isset($_POST['task_description']) && is_array($_POST['task_description']) ? $_POST['task_description'] : [];
$starts      = isset($_POST['start']) && is_array($_POST['start']) ? $_POST['start'] : [];
$finishes    = isset($_POST['finish']) && is_array($_POST['finish']) ? $_POST['finish'] : [];

if (empty($dates)) {
    echo json_encode(['status' => 'error', 'message' => 'No days to save.']);
    exit;
}

$conn->begin_transaction();

try {
    $deleteStmt = $conn->prepare('DELETE FROM employee_timesheets WHERE employee_id = ? AND work_date = ?');
    $insertStmt = $conn->prepare('
        INSERT INTO employee_timesheets (employee_id, work_date, row_number, description, time_start, time_end, total_minutes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    if (!$deleteStmt || !$insertStmt) {
        throw new Exception('Failed to prepare database statements.');
    }

    foreach ($dates as $date) {
        $date = trim((string)$date);
        if ($date === '') {
            continue;
        }

        // Remove existing rows for this date
        $deleteStmt->bind_param('is', $employeeDbId, $date);
        $deleteStmt->execute();

        $rowsForDateDesc  = $descs[$date]   ?? [];
        $rowsForDateStart = $starts[$date]  ?? [];
        $rowsForDateEnd   = $finishes[$date] ?? [];

        $rowNumber = 0;
        foreach ($rowsForDateDesc as $idx => $desc) {
            $desc = trim((string)$desc);
            $start = isset($rowsForDateStart[$idx]) ? trim((string)$rowsForDateStart[$idx]) : '';
            $end   = isset($rowsForDateEnd[$idx])   ? trim((string)$rowsForDateEnd[$idx])   : '';

            // If all fields empty, skip
            if ($desc === '' && $start === '' && $end === '') {
                continue;
            }

            $rowNumber++;

            $totalMinutes = 0;
            if ($start !== '' && $end !== '') {
                [$sh, $sm] = array_map('intval', explode(':', $start));
                [$eh, $em] = array_map('intval', explode(':', $end));
                $startMin = $sh * 60 + $sm;
                $endMin   = $eh * 60 + $em;
                if ($endMin < $startMin) {
                    $endMin += 24 * 60; // cross midnight
                }
                $totalMinutes = max(0, $endMin - $startMin);
            }

            // Use variables so we can pass by reference in bind_param
            $startDb = ($start !== '') ? $start : null;
            $endDb   = ($end !== '') ? $end : null;

            $insertStmt->bind_param(
                'isisssi',
                $employeeDbId,
                $date,
                $rowNumber,
                $desc,
                $startDb,
                $endDb,
                $totalMinutes
            );
            $insertStmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Timesheet saved successfully.']);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Failed to save timesheet: ' . $e->getMessage()]);
}

