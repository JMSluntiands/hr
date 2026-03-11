<?php
// Shared admin sidebar - expects $adminName and $role to be set by the calling page
$adminName   = $adminName ?? $_SESSION['name'] ?? 'Admin User';
$role        = $role ?? $_SESSION['role'] ?? 'admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$isIndex       = ($currentPage === 'index');
$isStaffAdd    = ($currentPage === 'staff-add');
$isStaff       = ($currentPage === 'staff');
$isEmployees   = ($isStaffAdd || $isStaff);
$isLeavesAlloc = ($currentPage === 'leaves-allocation');
$isLeavesSum   = ($currentPage === 'leaves-summary');
$isLeaves      = ($isLeavesAlloc || $isLeavesSum);
$isReqLeaves   = ($currentPage === 'request-leaves');
$isReqDoc      = ($currentPage === 'request-document');
$isReqBank     = ($currentPage === 'request-bank');
$isRequest     = ($isReqLeaves || $isReqDoc || $isReqBank);
$isActivityLog = ($currentPage === 'activity-log');
$isProgressiveDiscipline = ($currentPage === 'progressive-discipline');
$isCompensation = ($currentPage === 'compensation');
$isAccounts    = ($currentPage === 'accounts');
$isDepartment  = ($currentPage === 'department');
$isEmploymentType = ($currentPage === 'employment-type');
$isIdCreation  = ($currentPage === 'id-creation');

$activeClass = 'bg-white/20';
$employeesOpen = $isEmployees ? '' : ' hidden';
$leavesOpen    = $isLeaves ? '' : ' hidden';
$requestOpen   = $isRequest ? '' : ' hidden';
$employeesArrow = $isEmployees ? ' rotate-180' : '';
$leavesArrow    = $isLeaves ? ' rotate-180' : '';
$requestArrow   = $isRequest ? ' rotate-180' : '';

$requestLeavesPending = 0;
$requestDocPending = 0;
$requestBankPending = 0;
if (isset($conn) && $conn) {
    $t = $conn->query("SHOW TABLES LIKE 'leave_requests'");
    if ($t && $t->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'Pending'");
        if ($r && $row = $r->fetch_assoc()) $requestLeavesPending = (int)$row['c'];
    }
    $t = $conn->query("SHOW TABLES LIKE 'document_requests'");
    if ($t && $t->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) AS c FROM document_requests WHERE status = 'Pending'");
        if ($r && $row = $r->fetch_assoc()) $requestDocPending += (int)$row['c'];
    }
    $t = $conn->query("SHOW TABLES LIKE 'employee_document_uploads'");
    if ($t && $t->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) AS c FROM employee_document_uploads WHERE status = 'Pending'");
        if ($r && $row = $r->fetch_assoc()) $requestDocPending += (int)$row['c'];
    }
    $t = $conn->query("SHOW TABLES LIKE 'bank_account_change_requests'");
    if ($t && $t->num_rows > 0) {
        $r = $conn->query("SELECT COUNT(*) AS c FROM bank_account_change_requests WHERE status = 'Pending'");
        if ($r && $row = $r->fetch_assoc()) $requestBankPending = (int)$row['c'];
    }
}
$requestTotalPending = $requestLeavesPending + $requestDocPending + $requestBankPending;
?>
    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                <span class="text-lg font-semibold">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div class="flex flex-col leading-tight">
                <span class="text-sm font-medium">
                    <?php echo htmlspecialchars($adminName); ?>
                </span>
                <span class="text-[11px] text-white/80">
                    Administrator
                </span>
            </div>
        </div>
        <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60" data-sidebar-toggle>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </header>

    <!-- Sidebar - desktop fixed, mobile slide-over -->
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-[#FA9800] text-white flex flex-col transform -translate-x-full transition-transform duration-200 md:translate-x-0">
        <div class="p-6 flex items-center gap-4 border-b border-white/20">
            <div class="w-14 h-14 rounded-full overflow-hidden bg-white/20 flex items-center justify-center">
                <span class="text-2xl font-semibold text-white">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div>
                <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($adminName); ?></div>
                <div class="text-xs text-white/80">Administrator</div>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="index" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isIndex ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            <!-- Employees Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="employees-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors<?php echo $isEmployees ? ' ' . $activeClass : ''; ?>">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Employees</span>
                    <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none<?php echo $employeesArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="employees-dropdown" class="space-y-1 mt-1<?php echo $employeesOpen; ?>">
                    <a href="staff-add" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isStaffAdd ? ' ' . $activeClass : ''; ?>">
                        Add New Employee
                    </a>
                    <a href="staff" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isStaff ? ' ' . $activeClass : ''; ?>">
                        List of Employee
                    </a>
                </div>
            </div>
            <a href="id-creation" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isIdCreation ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-3 3c0 1.657 1.343 3 3 3s3-1.343 3-3a3.001 3.001 0 00-3-3z" />
                </svg>
                <span>ID Creation</span>
            </a>
            <!-- Leaves Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="leaves-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors<?php echo $isLeaves ? ' ' . $activeClass : ''; ?>">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Leaves</span>
                    <svg id="leaves-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none<?php echo $leavesArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="leaves-dropdown" class="space-y-1 mt-1<?php echo $leavesOpen; ?>">
                    <a href="leaves-allocation" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isLeavesAlloc ? ' ' . $activeClass : ''; ?>">
                        Allocation of Leave
                    </a>
                    <a href="leaves-summary" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isLeavesSum ? ' ' . $activeClass : ''; ?>">
                        Leave Summary per Employee
                    </a>
                </div>
            </div>
            <!-- Request Dropdown -->
            <div class="dropdown-container">
                <button type="button" id="request-dropdown-btn" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 cursor-pointer transition-colors<?php echo $isRequest ? ' ' . $activeClass : ''; ?>">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Request</span>
                    <?php if ($requestTotalPending > 0): ?>
                        <span class="ml-auto min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-white/90 text-[#FA9800] text-xs font-semibold"><?php echo $requestTotalPending; ?></span>
                    <?php endif; ?>
                    <svg id="request-arrow" class="w-4 h-4 ml-auto transition-transform text-white pointer-events-none<?php echo $requestArrow; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="request-dropdown" class="space-y-1 mt-1<?php echo $requestOpen; ?>">
                    <a href="request-leaves" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isReqLeaves ? ' ' . $activeClass : ''; ?>">
                        <span>Request Leaves</span>
                        <?php if ($requestLeavesPending > 0): ?>
                            <span class="ml-auto min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-white/90 text-[#FA9800] text-xs font-semibold"><?php echo $requestLeavesPending; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="request-document" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isReqDoc ? ' ' . $activeClass : ''; ?>">
                        <span>Request Document</span>
                        <?php if ($requestDocPending > 0): ?>
                            <span class="ml-auto min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-white/90 text-[#FA9800] text-xs font-semibold"><?php echo $requestDocPending; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="request-bank" class="flex items-center gap-3 pl-11 pr-3 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition-colors<?php echo $isReqBank ? ' ' . $activeClass : ''; ?>">
                        <span>Request Bank</span>
                        <?php if ($requestBankPending > 0): ?>
                            <span class="ml-auto min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-white/90 text-[#FA9800] text-xs font-semibold"><?php echo $requestBankPending; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <a href="activity-log" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isActivityLog ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Activity Log</span>
            </a>
            <a href="progressive-discipline" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isProgressiveDiscipline ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <span>Progressive Discipline</span>
            </a>
            <a href="announcement" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>Announcements</span>
            </a>
            <a href="compensation" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isCompensation ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Compensation</span>
            </a>

            <!-- Setting header -->
            <div class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-white/70">
                Setting
            </div>
            <a href="department" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isDepartment ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 11a4 4 0 100-8 4 4 0 000 8zm0 0a7 7 0 00-7 7v1h7m4-8a4 4 0 110 8" />
                </svg>
                <span>Department</span>
            </a>
            <a href="employment-type" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isEmploymentType ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A4 4 0 017 11h3.172a4 4 0 013.536 2.036l1.121 1.964A4 4 0 0018.828 17H19a2 2 0 110 4H7a4 4 0 01-1.879-7.596zM10 5a3 3 0 116 0 3 3 0 01-6 0z" />
                </svg>
                <span>Employment Type</span>
            </a>
            <a href="accounts" class="flex items-center gap-3 px-3 py-2 rounded-lg font-medium text-white hover:bg-white/10 transition-colors<?php echo $isAccounts ? ' ' . $activeClass : ''; ?>">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>Accounts</span>
            </a>
        </nav>
        <div class="p-4 border-t border-white/20 mt-auto">
            <div class="flex items-center justify-between text-xs font-medium mb-2 text-white/80">
                <span>Role</span>
                <span class="px-2 py-0.5 rounded-full bg-white/10 text-white font-medium">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
            <a href="../logout.php" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
        <a href="module-select.php" class="block text-xs font-medium text-white/80 hover:text-white mt-2">Back to Main Menu</a>
        </div>
        </aside>
        <!-- Mobile sidebar backdrop -->
        <div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 bg-black/40 hidden md:hidden"></div>
