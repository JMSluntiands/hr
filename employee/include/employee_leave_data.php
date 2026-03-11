<?php
/**
 * Leave credits and usage for current employee.
 * Expects: $conn, $employeeDbId.
 * Sets: $remainingLeave, $usedLeave, $pendingCount, $slTotal, $vlTotal, $blTotal, $elTotal,
 *       $slUsed, $vlUsed, $slRemaining, $vlRemaining, $recentLeaveRequests (optional).
 */
$remainingLeave = 0;
$usedLeave = 0;
$pendingCount = 0;
$slTotal = 0;
$vlTotal = 0;
$blTotal = 0;
$elTotal = 0;
$slUsed = 0;
$vlUsed = 0;
$slRemaining = 0;
$vlRemaining = 0;
$recentLeaveRequests = [];
$allocationHistory = [];

$currentYear = (int)date('Y');
if (empty($employeeDbId) || !$conn) {
    return;
}

$checkAlloc = $conn->query("SHOW TABLES LIKE 'leave_allocations'");
if ($checkAlloc && $checkAlloc->num_rows > 0) {
    $typeAllocStmt = $conn->prepare("SELECT leave_type, total_days FROM leave_allocations WHERE employee_id = ? AND year = ?");
    if ($typeAllocStmt) {
        $typeAllocStmt->bind_param('ii', $employeeDbId, $currentYear);
        $typeAllocStmt->execute();
        $typeAllocResult = $typeAllocStmt->get_result();
        while ($typeRow = $typeAllocResult->fetch_assoc()) {
            if ($typeRow['leave_type'] === 'Sick Leave') {
                $slTotal = (int)($typeRow['total_days'] ?? 0);
            } elseif ($typeRow['leave_type'] === 'Vacation Leave') {
                $vlTotal = (int)($typeRow['total_days'] ?? 0);
            } elseif ($typeRow['leave_type'] === 'Bereavement Leave') {
                $blTotal = (int)($typeRow['total_days'] ?? 0);
            } elseif ($typeRow['leave_type'] === 'Emergency Leave') {
                $elTotal = (int)($typeRow['total_days'] ?? 0);
            }
        }
        $typeAllocStmt->close();
    }
    if ($slTotal == 0 && $vlTotal == 0) {
        $yearStr = (string)$currentYear;
        $typeAllocStmt2 = $conn->prepare("SELECT leave_type, total_days FROM leave_allocations WHERE employee_id = ? AND year = ?");
        if ($typeAllocStmt2) {
            $typeAllocStmt2->bind_param('is', $employeeDbId, $yearStr);
            $typeAllocStmt2->execute();
            $typeAllocResult2 = $typeAllocStmt2->get_result();
            while ($typeRow2 = $typeAllocResult2->fetch_assoc()) {
                if ($typeRow2['leave_type'] === 'Sick Leave') {
                    $slTotal = (int)($typeRow2['total_days'] ?? 0);
                } elseif ($typeRow2['leave_type'] === 'Vacation Leave') {
                    $vlTotal = (int)($typeRow2['total_days'] ?? 0);
                } elseif ($typeRow2['leave_type'] === 'Bereavement Leave') {
                    $blTotal = (int)($typeRow2['total_days'] ?? 0);
                } elseif ($typeRow2['leave_type'] === 'Emergency Leave') {
                    $elTotal = (int)($typeRow2['total_days'] ?? 0);
                }
            }
            $typeAllocStmt2->close();
        }
    }

    // Full allocation history for My Leave Credits page
    $historyStmt = $conn->prepare("SELECT leave_type, total_days, year, created_at FROM leave_allocations WHERE employee_id = ? ORDER BY created_at DESC LIMIT 50");
    if ($historyStmt) {
        $historyStmt->bind_param('i', $employeeDbId);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        while ($row = $historyResult->fetch_assoc()) {
            $allocationHistory[] = [
                'leave_type' => $row['leave_type'],
                'total_days' => (int)($row['total_days'] ?? 0),
                'year'       => (string)($row['year'] ?? ''),
                'given_at'   => !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : ''
            ];
        }
        $historyStmt->close();
    }
}

$checkLeaveReq = $conn->query("SHOW TABLES LIKE 'leave_requests'");
if ($checkLeaveReq && $checkLeaveReq->num_rows > 0) {
    $usedQuery = "SELECT leave_type,
                 SUM(CASE 
                     WHEN start_date = end_date THEN 1
                     ELSE COALESCE(days, DATEDIFF(end_date, start_date) + 1)
                 END) as used_days
                 FROM leave_requests 
                 WHERE employee_id = ? 
                 AND status = 'Approved'
                 AND YEAR(start_date) = ?
                 GROUP BY leave_type";
    $usedStmt = $conn->prepare($usedQuery);
    if ($usedStmt) {
        $usedStmt->bind_param('ii', $employeeDbId, $currentYear);
        $usedStmt->execute();
        $usedResult = $usedStmt->get_result();
        while ($usedRow = $usedResult->fetch_assoc()) {
            $leaveType = $usedRow['leave_type'];
            $days = (int)($usedRow['used_days'] ?? 0);
            if ($leaveType === 'Sick Leave') {
                $slUsed += $days;
            } elseif ($leaveType === 'Vacation Leave') {
                $vlUsed += $days;
            } elseif ($leaveType === 'Bereavement Leave' || $leaveType === 'Emergency Leave') {
                $vlUsed += $days;
            }
        }
        $usedStmt->close();
    }
    $pendingStmt = $conn->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ? AND status = 'Pending'");
    if ($pendingStmt) {
        $pendingStmt->bind_param('i', $employeeDbId);
        $pendingStmt->execute();
        $pendingResult = $pendingStmt->get_result();
        if ($pendingRow = $pendingResult->fetch_assoc()) {
            $pendingCount = (int)($pendingRow['count'] ?? 0);
        }
        $pendingStmt->close();
    }
    // Recent leave requests (for My Leave Credits page)
    $recentStmt = $conn->prepare("SELECT id, leave_type as type, status, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 10");
    if ($recentStmt) {
        $recentStmt->bind_param('i', $employeeDbId);
        $recentStmt->execute();
        $recentResult = $recentStmt->get_result();
        while ($row = $recentResult->fetch_assoc()) {
            $recentLeaveRequests[] = [
                'id' => $row['id'],
                'date' => date('M d, Y', strtotime($row['created_at'])),
                'type' => $row['type'],
                'status' => $row['status'],
            ];
        }
        $recentStmt->close();
    }
}

$slRemaining = max(0, $slTotal - $slUsed);
$vlRemaining = max(0, $vlTotal - $vlUsed);
$remainingLeave = $slRemaining + $vlRemaining;
$usedLeave = $slUsed + $vlUsed;
