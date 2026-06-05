<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HrSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(HrSession $hrSession): View
    {
        $stats = [
            'totalEmployees' => 0,
            'openRequests' => 0,
            'pendingApprovals' => 0,
            'pendingLeaves' => 0,
            'pendingReimbursements' => 0,
            'pendingDocuments' => 0,
            'departments' => [],
            'recentActivity' => [],
        ];

        if (Schema::hasTable('employees')) {
            $stats['totalEmployees'] = (int) DB::table('employees')->where('status', 'Active')->count();
        }

        if (Schema::hasTable('leave_requests')) {
            $stats['pendingLeaves'] = (int) DB::table('leave_requests')->where('status', 'Pending')->count();
            $stats['pendingApprovals'] += $stats['pendingLeaves'];
        }

        if (Schema::hasTable('document_requests')) {
            $stats['pendingDocuments'] = (int) DB::table('document_requests')->where('status', 'Pending')->count();
            $stats['openRequests'] = $stats['pendingDocuments'];
        }

        if (Schema::hasTable('reimbursements')) {
            $stats['pendingReimbursements'] = (int) DB::table('reimbursements')->where('status', 'Pending')->count();
            $stats['pendingApprovals'] += $stats['pendingReimbursements'];
        }

        if (Schema::hasTable('employees')) {
            $stats['departments'] = DB::table('employees')
                ->select('department', DB::raw('COUNT(*) as count'))
                ->where('status', 'Active')
                ->groupBy('department')
                ->orderByDesc('count')
                ->limit(8)
                ->get()
                ->all();
        }

        if (Schema::hasTable('activity_logs')) {
            $stats['recentActivity'] = DB::table('activity_logs')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->all();
        }

        $adminName = (string) session(HrSession::NAME, 'Admin');
        $displayName = $adminName;
        if (str_contains($adminName, '@')) {
            $local = explode('@', $adminName)[0];
            $displayName = ucfirst(str_replace(['.', '_'], ' ', $local));
        }

        return view('admin.dashboard', [
            'adminName' => $adminName,
            'displayName' => $displayName,
            'stats' => $stats,
        ]);
    }
}
