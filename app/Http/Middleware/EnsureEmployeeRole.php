<?php

namespace App\Http\Middleware;

use App\Services\HrSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeRole
{
    public function __construct(private HrSession $hrSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hrSession->role() !== 'employee') {
            return redirect()->route('admin.module-select');
        }

        return $next($request);
    }
}
