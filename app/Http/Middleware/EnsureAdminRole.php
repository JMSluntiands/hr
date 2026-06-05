<?php

namespace App\Http\Middleware;

use App\Services\HrSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function __construct(private HrSession $hrSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hrSession->role() !== 'admin') {
            return redirect()->route('employee.dashboard');
        }

        return $next($request);
    }
}
