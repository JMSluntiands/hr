<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BankRequestController;
use App\Http\Controllers\Admin\CompensationController;
use App\Http\Controllers\Admin\DocumentArchiveController;
use App\Http\Controllers\Admin\DocumentUploadController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PerformanceReviewController;
use App\Http\Controllers\Admin\ProgressiveDisciplineController;
use App\Http\Controllers\Admin\EmploymentTypeController;
use App\Http\Controllers\Admin\IncidentReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentRequestController as AdminDocumentRequestController;
use App\Http\Controllers\Admin\LeaveAllocationController;
use App\Http\Controllers\Admin\LeaveRequestController as AdminLeaveRequestController;
use App\Http\Controllers\Admin\LeaveSummaryController;
use App\Http\Controllers\Admin\ModuleSelectController as AdminModuleSelectController;
use App\Http\Controllers\Admin\ReimbursementController as AdminReimbursementController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\WorkforceBuildingController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PrivacyPolicyController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\LeaveRequestController as EmployeeLeaveRequestController;
use App\Http\Controllers\Employee\ModuleSelectController as EmployeeModuleSelectController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\ReimbursementController as EmployeeReimbursementController;
use App\Http\Controllers\Employee\RequestHubController;
use App\Http\Controllers\Employee\LegacyPageController;
use App\Http\Controllers\Employee\TimekeepingBuildingController;
use App\Http\Controllers\Employee\TimeoffController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Inventory\ActivityLogController as InventoryActivityLogController;
use App\Http\Controllers\Inventory\AllocationController as InventoryAllocationController;
use App\Http\Controllers\Inventory\DashboardController as InventoryDashboardController;
use App\Http\Controllers\Inventory\DecommissionRequestController as InventoryDecommissionRequestController;
use App\Http\Controllers\Inventory\EmployeeRequestController as InventoryEmployeeRequestController;
use App\Http\Controllers\Inventory\ItemController as InventoryItemController;
use App\Http\Controllers\Inventory\MessagesController as InventoryMessagesController;
use App\Http\Controllers\Inventory\ReportController as InventoryReportController;
use App\Http\Controllers\Legacy\LegacyProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::redirect('/legacy/index.php', '/login');
Route::redirect('/logout.php', '/logout');
Route::post('/login/process', [LoginController::class, 'process'])->name('login.process');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', LogoutController::class)->name('logout');
Route::get('/logout', LogoutController::class);

Route::middleware(['hr.session', 'hr.auth'])->group(function () {
    Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('privacy-policy.show');
    Route::post('/privacy-policy/accept', [PrivacyPolicyController::class, 'accept'])->name('privacy-policy.accept');
    Route::post('/privacy-policy/decline', [PrivacyPolicyController::class, 'decline'])->name('privacy-policy.decline');

    Route::middleware(['hr.privacy'])->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::middleware(['hr.admin', 'hr.admin.sidebar'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/module-select', [AdminModuleSelectController::class, 'show'])->name('module-select');
        Route::post('/module-select', [AdminModuleSelectController::class, 'store']);
        Route::get('/workforce', WorkforceBuildingController::class)->name('workforce.building');
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{employee}/edit', [StaffController::class, 'edit'])->name('staff.edit')->whereNumber('employee');
        Route::put('/staff/{employee}', [StaffController::class, 'update'])->name('staff.update')->whereNumber('employee');
        Route::get('/staff/{employee}', [StaffController::class, 'show'])->name('staff.show')->whereNumber('employee');
        Route::get('/leave-requests', [AdminLeaveRequestController::class, 'index'])->name('leave-requests.index');
        Route::get('/leaves/history', [AdminLeaveRequestController::class, 'history'])->name('leaves.history.index');
        Route::post('/leave-requests/{id}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leave-requests.approve');
        Route::post('/leave-requests/{id}/decline', [AdminLeaveRequestController::class, 'decline'])->name('leave-requests.decline');
        Route::redirect('/leaves', '/admin/leave-requests');
        Route::post('/leaves/{id}/approve', [AdminLeaveRequestController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{id}/decline', [AdminLeaveRequestController::class, 'decline'])->name('leaves.decline');
        Route::get('/reimbursements', [AdminReimbursementController::class, 'index'])->name('reimbursements.index');
        Route::get('/reimbursements/list', [AdminReimbursementController::class, 'list'])->name('reimbursements.list.index');
        Route::get('/reimbursements/report', [AdminReimbursementController::class, 'report'])->name('reimbursements.report.index');
        Route::post('/reimbursements/{id}/attach-receipt', [AdminReimbursementController::class, 'attachReceipt'])->name('reimbursements.attach-receipt');
        Route::post('/reimbursements/{id}/approve', [AdminReimbursementController::class, 'approve'])->name('reimbursements.approve');
        Route::post('/reimbursements/{id}/decline', [AdminReimbursementController::class, 'decline'])->name('reimbursements.decline');
        Route::get('/bank-requests', [BankRequestController::class, 'index'])->name('bank-requests.index');
        Route::post('/bank-requests/{id}/approve', [BankRequestController::class, 'approve'])->name('bank-requests.approve');
        Route::post('/bank-requests/{id}/decline', [BankRequestController::class, 'decline'])->name('bank-requests.decline');
        Route::get('/document-archive', [DocumentArchiveController::class, 'index'])->name('document-archive.index');
        Route::get('/document-archive/{id}/file', [DocumentArchiveController::class, 'file'])->name('document-archive.file')->whereNumber('id');
        Route::post('/document-archive/{id}/approve', [DocumentArchiveController::class, 'approve'])->name('document-archive.approve');
        Route::post('/document-archive/{id}/reject', [DocumentArchiveController::class, 'reject'])->name('document-archive.reject');
        Route::get('/document-uploads', [DocumentUploadController::class, 'index'])->name('document-uploads.index');
        Route::get('/document-uploads/{id}/file', [DocumentUploadController::class, 'file'])->name('document-uploads.file')->whereNumber('id');
        Route::post('/document-uploads/{id}/approve', [DocumentUploadController::class, 'approve'])->name('document-uploads.approve');
        Route::post('/document-uploads/{id}/decline', [DocumentUploadController::class, 'decline'])->name('document-uploads.decline');
        Route::get('/documents', [AdminDocumentRequestController::class, 'index'])->name('documents.index');
        Route::post('/documents/{id}/approve', [AdminDocumentRequestController::class, 'approve'])->name('documents.approve');
        Route::post('/documents/{id}/decline', [AdminDocumentRequestController::class, 'decline'])->name('documents.decline');
        Route::get('/performance-reviews', [PerformanceReviewController::class, 'index'])->name('performance-reviews.index');
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/incident-reports', [IncidentReportController::class, 'index'])->name('incident-reports.index');
        Route::get('/incident-reports/create', [IncidentReportController::class, 'create'])->name('incident-reports.create');
        Route::post('/incident-reports', [IncidentReportController::class, 'store'])->name('incident-reports.store');
        Route::post('/incident-reports/{id}/delete', [IncidentReportController::class, 'destroy'])->name('incident-reports.destroy')->whereNumber('id');
        Route::get('/incident-reports/submitted', [IncidentReportController::class, 'submitted'])->name('incident-reports.submitted');
        Route::post('/incident-reports/{id}/approve', [IncidentReportController::class, 'approve'])->name('incident-reports.approve')->whereNumber('id');
        Route::post('/incident-reports/{id}/decline', [IncidentReportController::class, 'decline'])->name('incident-reports.decline')->whereNumber('id');
        Route::get('/progressive-discipline', [ProgressiveDisciplineController::class, 'index'])->name('progressive-discipline.index');
        Route::post('/progressive-discipline', [ProgressiveDisciplineController::class, 'store'])->name('progressive-discipline.store');
        Route::post('/progressive-discipline/{id}/status', [ProgressiveDisciplineController::class, 'updateStatus'])->name('progressive-discipline.update-status')->whereNumber('id');
        Route::get('/compensation', [CompensationController::class, 'index'])->name('compensation.index');
        Route::get('/compensation/employees', [CompensationController::class, 'employees'])->name('compensation.employees');
        Route::get('/compensation/employee-salary', [CompensationController::class, 'employeeSalary'])->name('compensation.employee-salary');
        Route::post('/compensation/adjustments', [CompensationController::class, 'store'])->name('compensation.store');
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::post('/departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::post('/departments/{id}/delete', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::get('/employment-types', [EmploymentTypeController::class, 'index'])->name('employment-types.index');
        Route::post('/employment-types', [EmploymentTypeController::class, 'store'])->name('employment-types.store');
        Route::post('/employment-types/{id}', [EmploymentTypeController::class, 'update'])->name('employment-types.update');
        Route::post('/employment-types/{id}/delete', [EmploymentTypeController::class, 'destroy'])->name('employment-types.destroy');
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts/create-employee', [AccountController::class, 'createEmployee'])->name('accounts.create-employee');
        Route::post('/accounts/{id}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');
        Route::post('/accounts/{id}/role', [AccountController::class, 'updateRole'])->name('accounts.update-role');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/leaves-allocation', [LeaveAllocationController::class, 'index'])->name('leaves-allocation.index');
        Route::get('/leaves-summary', [LeaveSummaryController::class, 'index'])->name('leaves-summary.index');
    });

    Route::middleware(['hr.admin', 'hr.admin.sidebar'])->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryDashboardController::class, 'index'])->name('dashboard');
        Route::get('/items', [InventoryItemController::class, 'index'])->name('items.index');
        Route::post('/items', [InventoryItemController::class, 'store'])->name('items.store');
        Route::put('/items/{item}', [InventoryItemController::class, 'update'])->name('items.update')->whereNumber('item');
        Route::delete('/items/{item}', [InventoryItemController::class, 'destroy'])->name('items.destroy')->whereNumber('item');
        Route::get('/allocation', [InventoryAllocationController::class, 'index'])->name('allocation.index');
        Route::post('/allocation', [InventoryAllocationController::class, 'store'])->name('allocation.store');
        Route::post('/allocation/return', [InventoryAllocationController::class, 'returnItem'])->name('allocation.return');
        Route::get('/allocation/export/pdf', [InventoryAllocationController::class, 'exportPdf'])->name('allocation.export.pdf');
        Route::get('/requests', [InventoryEmployeeRequestController::class, 'index'])->name('requests.index');
        Route::post('/requests/status', [InventoryEmployeeRequestController::class, 'updateStatus'])->name('requests.update-status');
        Route::get('/decommission', [InventoryDecommissionRequestController::class, 'index'])->name('decommission.index');
        Route::post('/decommission/status', [InventoryDecommissionRequestController::class, 'updateStatus'])->name('decommission.update-status');
        Route::get('/report', [InventoryReportController::class, 'index'])->name('report.index');
        Route::get('/report/export/excel', [InventoryReportController::class, 'exportExcel'])->name('report.export.excel');
        Route::get('/report/export/pdf', [InventoryReportController::class, 'exportPdf'])->name('report.export.pdf');
        Route::get('/messages', [InventoryMessagesController::class, 'index'])->name('messages.index');
        Route::post('/messages/read', [InventoryMessagesController::class, 'markRead'])->name('messages.mark-read');
        Route::post('/messages/read-all', [InventoryMessagesController::class, 'markAllRead'])->name('messages.mark-all-read');
        Route::get('/activity-log', [InventoryActivityLogController::class, 'index'])->name('activity-log.index');
    });

    Route::get('/inventory/item.php', function (\Illuminate\Http\Request $request) {
        $params = array_filter([
            'tab' => $request->query('tab'),
            'status' => $request->query('status'),
            'message' => $request->query('message'),
            'edit_id' => $request->query('edit_id'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('inventory.items.index', $params);
    });

    Route::redirect('/inventory/inventory.php', '/inventory');
    Route::redirect('/inventory/index.php', '/inventory');
    Route::redirect('/legacy/inventory/inventory.php', '/inventory');
    Route::redirect('/legacy/inventory/index.php', '/inventory');
    Route::redirect('/legacy/inventory/item.php', '/inventory/items');

    Route::get('/inventory/report.php', function (\Illuminate\Http\Request $request) {
        $export = strtolower((string) $request->query('export', ''));
        if ($export === 'excel') {
            return redirect()->route('inventory.report.export.excel');
        }
        if ($export === 'pdf') {
            return redirect()->route('inventory.report.export.pdf');
        }

        return redirect()->route('inventory.report.index', array_filter([
            'status' => $request->query('status'),
            'message' => $request->query('message'),
        ]));
    });

    Route::get('/inventory/allocation.php', function (\Illuminate\Http\Request $request) {
        if (strtolower((string) $request->query('export', '')) === 'pdf') {
            return redirect()->route('inventory.allocation.export.pdf', array_filter([
                'employee_id' => $request->query('employee_id'),
            ]));
        }

        return redirect()->route('inventory.allocation.index', array_filter([
            'status' => $request->query('status'),
            'message' => $request->query('message'),
            'employee_id' => $request->query('employee_id'),
        ]));
    });

    Route::redirect('/inventory/request.php', '/inventory/requests');
    Route::redirect('/inventory/decommission-request.php', '/inventory/decommission');
    Route::redirect('/inventory/messages.php', '/inventory/messages');
    Route::redirect('/inventory/activity-log.php', '/inventory/activity-log');
    Route::redirect('/legacy/inventory/report.php', '/inventory/report');
    Route::redirect('/legacy/inventory/allocation.php', '/inventory/allocation');
    Route::redirect('/legacy/inventory/request.php', '/inventory/requests');
    Route::redirect('/legacy/inventory/decommission-request.php', '/inventory/decommission');
    Route::redirect('/legacy/inventory/messages.php', '/inventory/messages');
    Route::redirect('/legacy/inventory/activity-log.php', '/inventory/activity-log');

    Route::middleware('hr.employee')->prefix('employee')->name('employee.')->group(function () {
        Route::get('/module-select', [EmployeeModuleSelectController::class, 'show'])->name('module-select');
        Route::post('/module-select', [EmployeeModuleSelectController::class, 'store']);
        Route::get('/timekeeping', TimekeepingBuildingController::class)->name('timekeeping.building');
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/timeoff', [TimeoffController::class, 'index'])->name('timeoff.index');
        Route::post('/leaves', [EmployeeLeaveRequestController::class, 'store'])->name('leaves.store');
        Route::get('/reimbursements', [EmployeeReimbursementController::class, 'index'])->name('reimbursements.index');
        Route::post('/reimbursements', [EmployeeReimbursementController::class, 'store'])->name('reimbursements.store');
        Route::get('/requests', [RequestHubController::class, 'index'])->name('requests.index');
        Route::get('/compensation', [LegacyPageController::class, 'compensation'])->name('compensation');
        Route::get('/settings', [LegacyPageController::class, 'settings'])->name('settings');
        Route::get('/inventory', [LegacyPageController::class, 'inventory'])->name('inventory');
        Route::get('/progressive-discipline', [LegacyPageController::class, 'progressiveDiscipline'])->name('progressive-discipline');
        Route::get('/performance', [LegacyPageController::class, 'performance'])->name('performance');
        Route::get('/performance/my-reviews', [LegacyPageController::class, 'performanceMyReviews'])->name('performance.my-reviews');
        Route::get('/performance/form-review', [LegacyPageController::class, 'performanceFormReview'])->name('performance.form-review');
        Route::get('/performance/submissions', [LegacyPageController::class, 'performanceReviewSubmissions'])->name('performance.review-submissions');
        Route::get('/incident-reports', [LegacyPageController::class, 'incidentReports'])->name('incident-reports.index');
        Route::get('/incident-reports/create', [LegacyPageController::class, 'incidentReportCreate'])->name('incident-reports.create');
    });

    // Redirect old .php URLs to Laravel routes
    $adminRedirects = [
        'staff.php' => '/admin/staff',
        'staff-add.php' => '/admin/staff/create',
        'request-leaves.php' => '/admin/leave-requests',
        'reimbursement-review.php' => '/admin/reimbursements',
        'reimbursement-list.php' => '/admin/reimbursements/list',
        'reimbursement-report.php' => '/admin/reimbursements/report',
        'request-document.php' => '/admin/documents',
        'request-bank.php' => '/admin/bank-requests',
        'request-upload.php' => '/admin/document-uploads',
        'document-archive.php' => '/admin/document-archive',
        'accounts.php' => '/admin/accounts',
        'employment-type.php' => '/admin/employment-types',
        'department.php' => '/admin/departments',
        'performance-review.php' => '/admin/performance-reviews',
        'compensation.php' => '/admin/compensation',
        'announcement.php' => '/admin/announcements',
        'incident-report-add.php' => '/admin/incident-reports/create',
        'incident-report-submitted.php' => '/admin/incident-reports/submitted',
        'incident-report-list.php' => '/admin/incident-reports',
        'progressive-discipline.php' => '/admin/progressive-discipline',
        'activity-log.php' => '/admin/activity-log',
        'leaves-allocation.php' => '/admin/leave-requests',
        'leaves-summary.php' => '/admin/leaves-summary',
        'index.php' => '/admin/dashboard',
    ];
    foreach ($adminRedirects as $from => $to) {
        Route::redirect('/admin/'.$from, $to);
    }

    Route::get('/admin/staff-view.php', function () {
        $id = (int) request()->query('id');

        return $id > 0
            ? redirect()->route('admin.staff.show', $id)
            : redirect()->route('admin.staff.index');
    });

    Route::get('/admin/staff-edit.php', function () {
        $id = (int) request()->query('id');

        return $id > 0
            ? redirect()->route('admin.staff.edit', $id)
            : redirect()->route('admin.staff.index');
    });

    $employeeRedirects = [
        'index.php' => '/employee/dashboard',
        'profile.php' => '/employee/profile',
        'timeoff.php' => '/employee/timeoff',
        'reimbursement.php' => '/employee/reimbursements',
        'request.php' => '/employee/requests',
        'compensation.php' => '/employee/compensation',
        'settings.php' => '/employee/settings',
        'inventory.php' => '/employee/inventory',
        'progressive-discipline.php' => '/employee/progressive-discipline',
        'performance.php' => '/employee/performance',
        'performance-my-reviews.php' => '/employee/performance/my-reviews',
        'performance-form-review.php' => '/employee/performance/form-review',
        'performance-review-submissions.php' => '/employee/performance/submissions',
        'incident-report-list.php' => '/employee/incident-reports',
        'incident-report-add.php' => '/employee/incident-reports/create',
        'module-select.php' => '/employee/module-select',
    ];
    foreach ($employeeRedirects as $from => $to) {
        Route::redirect('/employee/'.$from, $to);
    }
    Route::redirect('/employee/submit-leave-request.php', '/employee/timeoff');
    Route::redirect('/employee/timekeeping/index.php', '/employee/timekeeping');
    Route::redirect('/legacy/employee/timekeeping/index.php', '/employee/timekeeping');
    });
});

Route::middleware(['hr.session', 'hr.auth', 'hr.privacy'])->group(function () {
    $legacyModules = ['admin', 'employee', 'inventory', 'workforce', 'permission', 'controller'];
    foreach ($legacyModules as $module) {
        Route::any("/{$module}/{path?}", function (?string $path = null) use ($module) {
            $relative = $module.($path ? '/'.$path : '/index.php');

            return app(LegacyProxyController::class)->handle(request(), $relative);
        })->where('path', '.*')->name("legacy.{$module}");
    }
});

Route::any('/legacy/{path?}', [LegacyProxyController::class, 'handle'])
    ->where('path', '.*')
    ->defaults('path', '')
    ->name('legacy.path');

Route::redirect('/index.php', '/');
