<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = ActivityLog::query()
            ->where('entity_type', 'not like', 'Inventory%')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        return view('admin.activity-log.index', compact('logs'));
    }
}
