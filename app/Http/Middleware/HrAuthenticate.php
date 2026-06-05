<?php

namespace App\Http\Middleware;

use App\Services\HrSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HrAuthenticate
{
    public function __construct(private HrSession $hrSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hrSession->isLoggedIn()) {
            return redirect()->route('login', $request->query('timeout') ? ['timeout' => 1] : []);
        }

        $this->hrSession->touch();

        return $next($request);
    }
}
