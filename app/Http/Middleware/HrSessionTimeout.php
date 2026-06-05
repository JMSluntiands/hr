<?php

namespace App\Http\Middleware;

use App\Services\HrSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HrSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $timeout = (int) config('hr.session_timeout_seconds', 300);
        $last = (int) session(HrSession::LAST_ACTIVITY, 0);

        if ($last > 0 && (time() - $last) > $timeout) {
            session()->flush();

            return redirect()->route('login', ['timeout' => 1]);
        }

        return $next($request);
    }
}
