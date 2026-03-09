<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

include '../database/db.php';
include 'include/activity-logger.php';

$redirect = 'progressive-discipline';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'progressive_discipline_records'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    $_SESSION['progressive_discipline_msg'] = 'Progressive discipline table is missing. Run setup first.';
    header('Location: ' . $redirect);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $incidentDate = trim($_POST['incident_date'] ?? '');
    $offenseType = trim($_POST['offense_type'] ?? '');
    $disciplineLevel = trim($_POST['discipline_level'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $actionTaken = trim($_POST['action_taken'] ?? '');
    $nextReviewDate = trim($_POST['next_review_date'] ?? '');
    $issuedBy = (int)($_SESSION['user_id'] ?? 0);

    $allowedLevels = ['Verbal Warning', 'Written Warning', 'Final Warning', 'Suspension', 'Termination'];
    if ($employeeId <= 0 || empty($incidentDate) || empty($offenseType) || empty($description) || !in_array($disciplineLevel, $allowedLevels, true)) {
        $_SESSION['progressive_discipline_msg'] = 'Please complete all required fields.';
        header('Location: ' . $redirect);
        exit;
    }

    $nextReviewDateValue = !empty($nextReviewDate) ? $nextReviewDate : null;
    $stmt = $conn->prepare(
        "INSERT INTO progressive_discipline_records
         (employee_id, incident_date, offense_type, discipline_level, description, action_taken, issued_by, next_review_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        $_SESSION['progressive_discipline_msg'] = 'Database error while preparing record.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt->bind_param(
        'isssssis',
        $employeeId,
        $incidentDate,
        $offenseType,
        $disciplineLevel,
        $description,
        $actionTaken,
        $issuedBy,
        $nextReviewDateValue
    );

    if ($stmt->execute()) {
        $recordId = (int)$stmt->insert_id;
        logActivity(
            $conn,
            'Create Discipline Record',
            'progressive_discipline',
            $recordId,
            "Added {$disciplineLevel} for employee ID {$employeeId}"
        );
        $_SESSION['progressive_discipline_msg'] = 'Discipline record has been added.';
    } else {
        $_SESSION['progressive_discipline_msg'] = 'Failed to add discipline record.';
    }
    $stmt->close();

    header('Location: ' . $redirect);
    exit;
}

if ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $allowedStatus = ['Active', 'Resolved', 'Escalated'];

    if ($id <= 0 || !in_array($status, $allowedStatus, true)) {
        $_SESSION['progressive_discipline_msg'] = 'Invalid update request.';
        header('Location: ' . $redirect);
        exit;
    }

    $stmt = $conn->prepare("UPDATE progressive_discipline_records SET status = ? WHERE id = ?");
    if (!$stmt) {
        $_SESSION['progressive_discipline_msg'] = 'Database error while updating status.';
        header('Location: ' . $redirect);
        exit;
    }
    $stmt->bind_param('si', $status, $id);

    if ($stmt->execute()) {
        logActivity(
            $conn,
            'Update Discipline Status',
            'progressive_discipline',
            $id,
            "Updated discipline record #{$id} status to {$status}"
        );
        $_SESSION['progressive_discipline_msg'] = 'Discipline status updated.';
    } else {
        $_SESSION['progressive_discipline_msg'] = 'Failed to update discipline status.';
    }
    $stmt->close();

    header('Location: ' . $redirect);
    exit;
}

$_SESSION['progressive_discipline_msg'] = 'Unknown request.';
header('Location: ' . $redirect);
exit;
