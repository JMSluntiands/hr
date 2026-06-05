@php
    $path = request()->path();
    $route = request()->route()?->getName() ?? '';
    $hit = fn (...$needles) => collect($needles)->contains(fn ($n) => str_contains($path, $n) || ($route && str_contains((string) $route, $n)));
    $activeClass = 'bg-white/20';
    $link = 'flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white transition-colors';
    $isDashboard = $route === 'employee.dashboard';
    $isProfile = $hit('employee/profile', 'profile.show');
    $isTimeoff = $hit('timeoff');
    $isRequests = $hit('employee/requests', 'requests.index');
    $isReimbursement = $hit('reimbursements', 'reimbursement');
    $isCompensation = $hit('compensation');
    $isInventory = $hit('inventory');
    $isPerformance = $hit('performance');
    $isIncident = $hit('incident-report');
    $isDiscipline = $hit('progressive-discipline');
    $isSettings = $hit('settings');
    $invOpen = $isInventory ? '' : ' hidden';
    $invArrow = $isInventory ? ' rotate-180' : '';
    $perfOpen = $isPerformance ? '' : ' hidden';
    $perfArrow = $isPerformance ? ' rotate-180' : '';
    $incOpen = $isIncident ? '' : ' hidden';
    $incArrow = $isIncident ? ' rotate-180' : '';
    $photoUrl = ($employeePhoto ?? null) ? asset('uploads/'.$employeePhoto) : null;
@endphp

<header class="md:hidden fixed inset-x-0 top-0 z-30 bg-[#FA9800] text-white flex items-center justify-between px-4 py-3 shadow">
    <div class="flex items-center gap-2 min-w-0">
        <div class="w-9 h-9 rounded-full bg-white/20 overflow-hidden flex items-center justify-center shrink-0">
            @if($photoUrl)<img src="{{ $photoUrl }}" alt="" class="w-full h-full object-cover">@else<span class="text-lg font-semibold">{{ strtoupper(substr($employeeName ?? 'E', 0, 1)) }}</span>@endif
        </div>
        <span class="text-sm font-medium truncate">{{ $employeeName ?? 'Employee' }}</span>
    </div>
    <button type="button" class="p-2 rounded-md bg-white/10 hover:bg-white/20" data-employee-sidebar-toggle aria-label="Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
</header>

<aside id="employee-sidebar" class="fixed inset-y-0 left-0 z-40 flex max-h-[100dvh] w-64 max-w-full flex-col overflow-hidden bg-[#FA9800] text-white transform -translate-x-full transition-transform duration-200 md:translate-x-0">
    <div class="p-6 flex shrink-0 items-center gap-4 border-b border-white/20">
        <div class="w-14 h-14 rounded-full bg-white/20 overflow-hidden flex items-center justify-center shrink-0">
            @if($photoUrl)<img src="{{ $photoUrl }}" alt="" class="w-full h-full object-cover">@else<span class="text-2xl font-semibold">{{ strtoupper(substr($employeeName ?? 'E', 0, 1)) }}</span>@endif
        </div>
        <div class="min-w-0">
            <div class="font-medium text-sm truncate">{{ $employeeName ?? 'Employee' }}</div>
            <div class="text-xs text-white/80">Employee</div>
        </div>
    </div>
    <nav class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-y-contain p-4 space-y-1 text-sm">
        <a href="{{ route('employee.dashboard') }}" class="{{ $link }}{{ $isDashboard ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('employee.profile.show') }}" class="{{ $link }}{{ $isProfile ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>My Profile</span>
        </a>
        <a href="{{ route('employee.timeoff.index') }}" class="{{ $link }}{{ $isTimeoff ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Time off / Leave</span>
        </a>
        <a href="{{ route('employee.requests.index') }}" class="{{ $link }}{{ $isRequests ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Request COE</span>
        </a>
        <a href="{{ route('employee.reimbursements.index') }}" class="{{ $link }}{{ $isReimbursement ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2m6 4H9m6-8H9m10 14H5a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2z"/></svg>
            <span>My Reimbursement</span>
        </a>
        <a href="{{ route('employee.compensation') }}" class="{{ $link }}{{ $isCompensation ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>My Compensation</span>
        </a>
        <div class="dropdown-container">
            <button type="button" id="employee-inv-dropdown-btn" class="flex w-full items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white{{ $isInventory ? ' '.$activeClass : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span class="flex-1 text-left pointer-events-none">My Inventory</span>
                <svg id="employee-inv-arrow" class="w-4 h-4 shrink-0 transition-transform pointer-events-none{{ $invArrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="employee-inv-dropdown" class="mb-2 ml-10 space-y-1{{ $invOpen }}">
                <a href="{{ route('employee.inventory', ['view' => 'list']) }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">List of my items</a>
                <a href="{{ route('employee.inventory', ['view' => 'request']) }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">Request item</a>
                <a href="{{ route('employee.inventory', ['view' => 'decommission']) }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">Decommission Request</a>
            </div>
        </div>
        @if($performanceReviewNavEnabled ?? false)
        @php
            $isPerfFormReview = $hit('performance/form-review', 'performance.form-review');
            $isPerfSubmissions = $hit('performance/submissions', 'performance.review-submissions');
        @endphp
        <div class="dropdown-container">
            <button type="button" id="employee-perf-dropdown-btn" class="flex w-full items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white{{ $isPerformance ? ' '.$activeClass : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="flex-1 text-left pointer-events-none">Performance</span>
                <svg id="employee-perf-arrow" class="w-4 h-4 shrink-0 transition-transform pointer-events-none{{ $perfArrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="employee-perf-dropdown" class="mb-2 ml-10 space-y-1{{ $perfOpen }}">
                @if($performanceFormReviewNav ?? false)
                <a href="{{ route('employee.performance.form-review') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10{{ $isPerfFormReview ? ' bg-white/20' : '' }}">Form Review</a>
                <a href="{{ route('employee.performance.review-submissions') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10{{ $isPerfSubmissions ? ' bg-white/20' : '' }}">Reviews Submitted</a>
                @endif
                <a href="{{ route('employee.performance') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">Self Performance Review</a>
                <a href="{{ route('employee.performance.my-reviews') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">My Performance Review</a>
            </div>
        </div>
        @endif
        <a href="{{ route('employee.progressive-discipline') }}" class="{{ $link }}{{ $isDiscipline ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <span>Progressive Discipline</span>
        </a>
        <div class="dropdown-container">
            <button type="button" id="incident-report-dropdown-btn" class="flex w-full items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 text-sm font-medium text-white{{ $isIncident ? ' '.$activeClass : '' }}">
                <svg class="w-5 h-5 shrink-0 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="flex-1 text-left pointer-events-none">Incident Report</span>
                <svg id="incident-report-arrow" class="w-4 h-4 shrink-0 transition-transform pointer-events-none{{ $incArrow }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="incident-report-dropdown" class="mb-2 ml-10 space-y-1{{ $incOpen }}">
                <a href="{{ route('employee.incident-reports.create') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">Add incident</a>
                <a href="{{ route('employee.incident-reports.index') }}" class="block rounded-lg px-3 py-1.5 text-xs font-medium text-white/90 hover:bg-white/10">List of incident</a>
            </div>
        </div>
        <a href="{{ route('employee.settings') }}" class="{{ $link }}{{ $isSettings ? ' '.$activeClass : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Settings</span>
        </a>
    </nav>
    <div class="hr-sidebar-footer shrink-0 border-t border-white/20 p-4 space-y-2">
        <a href="{{ route('employee.module-select') }}" class="block text-xs font-medium text-white/80 hover:text-white">Back to Main Menu</a>
        <a href="{{ route('logout') }}" class="block text-xs font-medium text-white/80 hover:text-white">Logout</a>
    </div>
</aside>
<div id="employee-sidebar-backdrop" class="fixed inset-0 z-30 bg-black/40 md:hidden" style="display:none" aria-hidden="true"></div>
