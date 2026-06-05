@php
    $path = request()->path();
    $route = request()->route()?->getName() ?? '';
    $hit = fn (...$needles) => collect($needles)->contains(fn ($n) => str_contains($path, $n) || ($route && str_contains((string) $route, $n)));

    $activeClass = 'is-active hr-sidebar-link';
    $btnActive = 'is-open is-active';
    $subActive = 'is-active hr-sidebar-sublink';
    $isDashboard = $route === 'admin.dashboard' || str_ends_with($path, 'admin/dashboard');
    $isStaffAdd = $hit('staff/create', 'staff-add', 'staff.create');
    $isStaff = $hit('admin/staff') && ! $isStaffAdd;
    $isEmployees = $isStaffAdd || $isStaff;
    $isIdCreation = $hit('id-creation');
    $isLeaveRequests = $hit('leave-requests', 'leave-requests.index');
    $isLeaveHistory = $hit('leaves/history', 'leaves.history');
    $isLeaves = $isLeaveRequests || $isLeaveHistory;
    $isReimbursementReview = $hit('admin/reimbursements', 'reimbursement-review');
    $isReimbursementList = $hit('reimbursements/list', 'reimbursements.list');
    $isReimbursementReport = $hit('reimbursements/report', 'reimbursements.report');
    $isReimbursement = $isReimbursementReview || $isReimbursementList || $isReimbursementReport;
    $isReqDoc = $hit('admin/documents', 'request-document');
    $isReqUpload = $hit('document-uploads', 'document-uploads.index');
    $isReqBank = $hit('bank-requests', 'bank-requests.index');
    $isDocArchive = $hit('document-archive', 'document-archive.index');
    $isRequest = $isReqDoc || $isReqUpload || $isReqBank || $isDocArchive;
    $isActivityLog = $hit('activity-log');
    $isProgressiveDiscipline = $hit('progressive-discipline', 'progressive-discipline.index');
    $isIncidentReport = $hit('incident-report');
    $isIncidentReportAdd = $hit('incident-reports/create', 'incident-reports.create');
    $isIncidentReportSubmitted = $hit('incident-reports/submitted', 'incident-reports.submitted');
    $isIncidentReportList = $hit('incident-reports', 'incident-reports.index') && ! $isIncidentReportAdd && ! $isIncidentReportSubmitted;
    $isIncidentReport = $isIncidentReport || $isIncidentReportAdd || $isIncidentReportSubmitted || $isIncidentReportList;
    $isCompensation = $hit('compensation', 'compensation.index');
    $isPerformanceReview = $hit('performance-reviews', 'performance-reviews.index');
    $isDepartment = $hit('departments', 'departments.index');
    $isEmploymentType = $hit('employment-types', 'employment-types.index');
    $isAccounts = $hit('admin/accounts', 'accounts.index');
    $isAnnouncement = $hit('announcements', 'announcements.index');

    $open = fn ($cond) => $cond ? '' : 'hidden';
    $arrow = fn ($cond) => $cond ? 'rotate-180' : '';
    $pendingCounts = $pendingCounts ?? [];
    $requestTotalPending = ($pendingCounts['documents'] ?? 0) + ($pendingCounts['uploads'] ?? 0) + ($pendingCounts['bank'] ?? 0) + ($pendingCounts['archive'] ?? 0);
    $badge = function ($n) {
        if ($n <= 0) return '';
        $t = $n > 99 ? '99+' : (string) $n;
        return '<span class="ml-auto min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-white/90 text-[#FA9800] text-xs font-semibold">'.$t.'</span>';
    };

    $nav = $sidebarCan ?? fn () => true;
    $restricted = $sidebarRestricted ?? false;
    $showRequestMenu = $nav('hr_nav_documents') || $nav('hr_nav_document_uploads') || $nav('hr_nav_bank_requests') || $nav('hr_nav_document_archive');
@endphp

<header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
    <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
            <span class="text-lg font-semibold">{{ strtoupper(substr($adminName ?? 'A', 0, 1)) }}</span>
        </div>
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-medium truncate max-w-[10rem]">{{ $adminName ?? 'Admin' }}</span>
            <span class="text-[11px] text-white/80">Administrator</span>
        </div>
    </div>
    <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20" data-sidebar-toggle aria-label="Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</header>

<aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 flex-col overflow-hidden text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
    <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
        <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center">
            <span class="text-2xl font-semibold">{{ strtoupper(substr($adminName ?? 'A', 0, 1)) }}</span>
        </div>
        <div class="min-w-0">
            <div class="font-medium text-sm truncate">{{ $adminName ?? 'Admin' }}</div>
            <div class="text-xs text-white/80">Administrator</div>
        </div>
    </div>

    <nav class="hr-sidebar-nav flex-1 min-h-0 overflow-y-auto overflow-x-hidden p-4 space-y-1 text-sm">
        @if(! $restricted)
        <a href="{{ route('admin.dashboard') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isDashboard ? $activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Dashboard</span>
        </a>

        <div class="dropdown-container">
            <button type="button" id="employees-dropdown-btn" class="hr-sidebar-dropdown-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isEmployees ? $btnActive : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span class="pointer-events-none">Employees</span>
                <svg id="employees-arrow" class="w-4 h-4 ml-auto transition-transform pointer-events-none {{ $arrow($isEmployees) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="employees-dropdown" class="space-y-1 mt-1 {{ $open($isEmployees) }}">
                <a href="{{ route('admin.staff.create') }}" class="hr-sidebar-sublink flex pl-11 pr-3 py-2 text-sm {{ $isStaffAdd ? $subActive : '' }}">Add New Employee</a>
                <a href="{{ route('admin.staff.index') }}" class="hr-sidebar-sublink flex pl-11 pr-3 py-2 text-sm {{ $isStaff ? $subActive : '' }}">List of Employee</a>
            </div>
        </div>

        <a href="{{ url('/admin/id-creation.php') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isIdCreation ? $activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
            <span>ID Creation</span>
        </a>
        @endif

        @if($nav('hr_nav_leave_requests'))
        <div class="dropdown-container">
            <button type="button" id="leaves-dropdown-btn" class="hr-sidebar-dropdown-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isLeaves ? $btnActive : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="pointer-events-none">Leaves</span>
                {!! $badge($pendingCounts['leaves'] ?? 0) !!}
                <svg id="leaves-arrow" class="w-4 h-4 ml-auto shrink-0 transition-transform pointer-events-none {{ $arrow($isLeaves) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="leaves-dropdown" class="space-y-1 mt-1 {{ $open($isLeaves) }}">
                <a href="{{ route('admin.leave-requests.index') }}" class="hr-sidebar-sublink flex items-center pl-11 pr-3 py-2 text-sm {{ $isLeaveRequests ? $subActive : '' }}">
                    <span>Leave Request</span>{!! $badge($pendingCounts['leaves'] ?? 0) !!}
                </a>
                @if(! $restricted)
                <a href="{{ route('admin.leaves.history.index') }}" class="hr-sidebar-sublink flex pl-11 pr-3 py-2 text-sm {{ $isLeaveHistory ? $subActive : '' }}">Leave History</a>
                @endif
            </div>
        </div>
        @endif

        @if($nav('hr_nav_reimbursements'))
        <div class="dropdown-container">
            <button type="button" id="reimbursement-dropdown-btn" class="hr-sidebar-dropdown-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isReimbursement ? $btnActive : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2m6 4H9m6-8H9m10 14H5a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z"/></svg>
                <span class="pointer-events-none">Reimbursement</span>
                {!! $badge($pendingCounts['reimbursements'] ?? 0) !!}
                <svg id="reimbursement-arrow" class="w-4 h-4 ml-auto shrink-0 transition-transform pointer-events-none {{ $arrow($isReimbursement) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="reimbursement-dropdown" class="space-y-1 mt-1 {{ $open($isReimbursement) }}">
                <a href="{{ route('admin.reimbursements.index') }}" class="hr-sidebar-sublink flex pl-11 pr-3 py-2 text-sm {{ $isReimbursementReview ? $subActive : '' }}">For Review Reimbursement</a>
                <a href="{{ route('admin.reimbursements.list.index') }}" class="hr-sidebar-sublink flex pl-11 pr-3 py-2 text-sm {{ $isReimbursementList ? $subActive : '' }}">List of Reimbursement</a>
                <a href="{{ route('admin.reimbursements.report.index') }}" class="hr-sidebar-sublink flex pl-11 pr-3 py-2 text-sm {{ $isReimbursementReport ? $subActive : '' }}">Report Reimbursement</a>
            </div>
        </div>
        @endif

        @if($showRequestMenu)
        <div class="dropdown-container">
            <button type="button" id="request-dropdown-btn" class="hr-sidebar-dropdown-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isRequest ? $btnActive : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span class="pointer-events-none">Request</span>
                {!! $badge($requestTotalPending) !!}
                <svg id="request-arrow" class="w-4 h-4 ml-auto shrink-0 transition-transform pointer-events-none {{ $arrow($isRequest) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="request-dropdown" class="space-y-1 mt-1 {{ $open($isRequest) }}">
                @if($nav('hr_nav_documents'))
                <a href="{{ route('admin.documents.index') }}" class="hr-sidebar-sublink flex items-center pl-11 pr-3 py-2 text-sm {{ $isReqDoc ? $subActive : '' }}">
                    <span>Request Document</span>{!! $badge($pendingCounts['documents'] ?? 0) !!}
                </a>
                @endif
                @if($nav('hr_nav_document_uploads'))
                <a href="{{ route('admin.document-uploads.index') }}" class="hr-sidebar-sublink flex items-center pl-11 pr-3 py-2 text-sm {{ $isReqUpload ? $subActive : '' }}">
                    <span>Request Upload</span>{!! $badge($pendingCounts['uploads'] ?? 0) !!}
                </a>
                @endif
                @if($nav('hr_nav_document_archive'))
                <a href="{{ route('admin.document-archive.index') }}" class="hr-sidebar-sublink flex items-center pl-11 pr-3 py-2 text-sm {{ $isDocArchive ? $subActive : '' }}">
                    <span>Document Archive</span>{!! $badge($pendingCounts['archive'] ?? 0) !!}
                </a>
                @endif
                @if($nav('hr_nav_bank_requests'))
                <a href="{{ route('admin.bank-requests.index') }}" class="hr-sidebar-sublink flex items-center pl-11 pr-3 py-2 text-sm {{ $isReqBank ? $subActive : '' }}">
                    <span>Request Bank</span>{!! $badge($pendingCounts['bank'] ?? 0) !!}
                </a>
                @endif
            </div>
        </div>
        @endif

        @if(! $restricted)
        <a href="{{ route('admin.activity-log.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isActivityLog ? $activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Activity Log</span>
        </a>
        <a href="{{ route('admin.progressive-discipline.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isProgressiveDiscipline ? $activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <span>Progressive Discipline</span>
        </a>

        <div class="dropdown-container">
            <button type="button" id="incident-report-dropdown-btn" class="hr-sidebar-dropdown-btn w-full flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isIncidentReport ? $btnActive : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="flex-1 text-left pointer-events-none">Incident Report</span>
                <svg id="incident-report-arrow" class="w-4 h-4 shrink-0 transition-transform pointer-events-none {{ $arrow($isIncidentReport) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="incident-report-dropdown" class="mb-2 ml-10 space-y-1 {{ $open($isIncidentReport) }}">
                <a href="{{ route('admin.incident-reports.create') }}" class="hr-sidebar-sublink block px-3 py-1.5 text-xs font-medium {{ $isIncidentReportAdd ? $subActive : '' }}">Add incident</a>
                <a href="{{ route('admin.incident-reports.submitted') }}" class="hr-sidebar-sublink flex items-center justify-between gap-2 px-3 py-1.5 text-xs font-medium {{ $isIncidentReportSubmitted ? $subActive : '' }}">
                    <span>Incident Submitted by Employee</span>{!! $badge($pendingCounts['incidents'] ?? 0) !!}
                </a>
                <a href="{{ route('admin.incident-reports.index') }}" class="hr-sidebar-sublink block px-3 py-1.5 text-xs font-medium {{ $isIncidentReportList ? $subActive : '' }}">List of incident</a>
            </div>
        </div>

        <a href="{{ route('admin.announcements.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isAnnouncement ? $activeClass : '' }}">Announcements</a>
        <a href="{{ route('admin.compensation.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isCompensation ? $activeClass : '' }}">Compensation</a>
        <a href="{{ route('admin.performance-reviews.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isPerformanceReview ? $activeClass : '' }}">Performance</a>

        <div class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-white/70">Setting</div>
        <a href="{{ route('admin.departments.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isDepartment ? $activeClass : '' }}">Department</a>
        <a href="{{ route('admin.employment-types.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isEmploymentType ? $activeClass : '' }}">Employment Type</a>
        <a href="{{ route('admin.accounts.index') }}" class="hr-sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg font-medium {{ $isAccounts ? $activeClass : '' }}">Accounts</a>
        @endif
    </nav>

    <div class="hr-sidebar-footer shrink-0 border-t border-white/20 p-4 space-y-2">
        <a href="{{ route('admin.module-select') }}" class="block text-xs font-medium text-white/80 hover:text-white">Back to Main Menu</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-xs font-medium text-white/80 hover:text-white">Logout</button></form>
    </div>
</aside>
<div id="admin-sidebar-backdrop" class="fixed inset-0 z-30 bg-black/40 hidden md:hidden"></div>
