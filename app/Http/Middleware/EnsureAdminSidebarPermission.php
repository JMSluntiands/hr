<?php

namespace App\Http\Middleware;

use App\Services\AdminPermissionService;
use App\Services\HrSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSidebarPermission
{
    public function __construct(
        private AdminPermissionService $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) session(HrSession::USER_ID, 0);
        $routeName = $request->route()?->getName();
        $tab = $request->routeIs('inventory.items.index') ? (string) $request->query('tab', 'list') : null;

        $exempt = [
            'admin.module-select',
            'admin.workforce.building',
            'admin.dashboard',
            'home',
        ];

        if ($routeName && in_array($routeName, $exempt, true)) {
            return $next($request);
        }

        if (! $this->permissions->canAccessRoute($userId, $routeName, $tab)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to access this page.'], 403);
            }

            return redirect()
                ->route('admin.module-select')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
