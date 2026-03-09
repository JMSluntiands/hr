<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$adminName = $_SESSION['name'] ?? 'Admin User';
$role      = $_SESSION['role'] ?? 'admin';

// Include database connection
include '../database/db.php';

// Get counts from database
$totalEmployees = 0;
$openRequests = 0;
$pendingApprovals = 0;

if ($conn) {
    // Count total employees
    $empResult = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 'Active'");
    if ($empResult && $row = $empResult->fetch_assoc()) {
        $totalEmployees = (int)$row['total'];
    }
    
    // Count open requests: leave requests + document requests + document uploads (all pending)
    $leaveCount = 0;
    $docRequestCount = 0;
    $docUploadCount = 0;
    
    // Count pending leave requests (check if table exists)
    $checkLeaveReq = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($checkLeaveReq && $checkLeaveReq->num_rows > 0) {
        $leaveResult = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'Pending'");
        if ($leaveResult && $row = $leaveResult->fetch_assoc()) {
            $leaveCount = (int)$row['total'];
        }
    }
    
    // Count pending document requests (check if table exists)
    $checkDocReq = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($checkDocReq && $checkDocReq->num_rows > 0) {
        $docReqResult = $conn->query("SELECT COUNT(*) as total FROM document_requests WHERE status = 'Pending'");
        if ($docReqResult && $row = $docReqResult->fetch_assoc()) {
            $docRequestCount = (int)$row['total'];
        }
    }
    
    // Count pending document uploads (check if table exists)
    $checkDocUpload = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($checkDocUpload && $checkDocUpload->num_rows > 0) {
        $docUploadResult = $conn->query("SELECT COUNT(*) as total FROM employee_document_uploads WHERE status = 'Pending'");
        if ($docUploadResult && $row = $docUploadResult->fetch_assoc()) {
            $docUploadCount = (int)$row['total'];
        }
    }
    
    // Total open requests
    $openRequests = $leaveCount + $docRequestCount + $docUploadCount;
    
    // Pending approvals (same as open requests - all items needing approval)
    $pendingApprovals = $openRequests;
}

// Get recent activities
$recentActivities = [];
if ($conn) {
    // Check if activity_logs table exists
    $checkActivityTable = $conn->query("SHOW TABLES LIKE 'activity_logs'");
    if ($checkActivityTable && $checkActivityTable->num_rows > 0) {
        $activitySql = "SELECT action, description, created_at 
                       FROM activity_logs 
                       ORDER BY created_at DESC 
                       LIMIT 5";
        $activityResult = $conn->query($activitySql);
        if ($activityResult && $activityResult->num_rows > 0) {
            while ($row = $activityResult->fetch_assoc()) {
                $recentActivities[] = $row;
            }
        }
    }
}

// Function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return $diff . ' sec' . ($diff != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        if ($days == 1) {
            return 'Yesterday';
        }
        return $days . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                    colors: {
                        luntianBlue: '#E9A319',
                        luntianLight: '#f3f4ff'
                    }
                }
            }
        };
    </script>
</head>
<body class="font-inter bg-[#f1f5f9] min-h-screen">
    <?php include __DIR__ . '/include/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-64 min-h-screen overflow-y-auto p-8">
        <!-- Top Bar -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">
                Welcome back, <?php echo htmlspecialchars(explode(' ', $adminName)[0]); ?>!
            </h1>
            <div class="flex items-center gap-3">
                <a href="request-document" class="relative w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php if ($pendingApprovals > 0): ?>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-semibold rounded-full flex items-center justify-center">
                        <?php echo $pendingApprovals > 99 ? '99+' : $pendingApprovals; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <a href="change-password" class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-slate-500 hover:bg-slate-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Total Employees -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Total Employees</h2>
                <p class="text-3xl font-semibold text-slate-900 mb-1">
                    <?php echo (int)$totalEmployees; ?>
                </p>
                <p class="text-xs text-slate-500">Active employees in the system</p>
            </section>

            <!-- Open Requests -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Open Requests</h2>
                <p class="text-3xl font-semibold text-amber-500 mb-1">
                    <?php echo (int)$openRequests; ?>
                </p>
                <p class="text-xs text-slate-500">Leave and document requests awaiting review</p>
            </section>

            <!-- Pending Approvals -->
            <section class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-2">Pending Approvals</h2>
                <p class="text-3xl font-semibold text-emerald-600 mb-1">
                    <?php echo (int)$pendingApprovals; ?>
                </p>
                <p class="text-xs text-slate-500">Items that need your decision</p>
            </section>
        </div>

        <!-- Recent Activity -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Recent Activity</h2>
            </div>
            <div class="p-6 text-sm text-slate-600 space-y-3">
                <?php if (empty($recentActivities)): ?>
                    <div class="text-center text-slate-400 py-4">
                        <p>No recent activity found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="flex items-center justify-between">
                        <span><?php echo htmlspecialchars($activity['description'] ?? $activity['action'] ?? 'Activity'); ?></span>
                        <span class="text-xs text-slate-400"><?php echo timeAgo($activity['created_at'] ?? ''); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="include/sidebar-dropdown.js"></script>
</body>
</html>

