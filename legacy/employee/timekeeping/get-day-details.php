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

$date = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
if ($date === '') {
    echo json_encode(['status' => 'error', 'message' => 'Date is required.']);
    exit;
}

try {
    $dt = new DateTime($date);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date.']);
    exit;
}

$displayDate = $dt->format('D, M d, Y');

$stmt = $conn->prepare("
    SELECT row_number, description, time_start, time_end, total_minutes
    FROM employee_timesheets
    WHERE employee_id = ? AND work_date = ?
    ORDER BY row_number
");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement.']);
    exit;
}

$stmt->bind_param('is', $employeeDbId, $date);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$totalMinutesAll = 0;

while ($row = $res->fetch_assoc()) {
    $minutes = (int)($row['total_minutes'] ?? 0);
    $totalMinutesAll += $minutes;
    $h = floor($minutes / 60);
    $m = $minutes % 60;
    $totalLabel = sprintf('%d:%02d', $h, $m);

    $rows[] = [
        'row_number'   => (int)$row['row_number'],
        'description'  => (string)($row['description'] ?? ''),
        'time_start'   => (string)($row['time_start'] ?? ''),
        'time_end'     => (string)($row['time_end'] ?? ''),
        'total_label'  => $totalLabel,
    ];
}

$stmt->close();

$hAll = floor($totalMinutesAll / 60);
$mAll = $totalMinutesAll % 60;

echo json_encode([
    'status'       => 'success',
    'date'         => $date,
    'display_date' => $displayDate,
    'rows'         => $rows,
    'total_label'  => sprintf('%d:%02d', $hAll, $mAll),
]);

