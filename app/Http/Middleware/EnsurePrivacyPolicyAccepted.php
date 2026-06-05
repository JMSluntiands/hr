<?php

namespace App\Http\Middleware;

use App\Services\HrSession;
use App\Services\PrivacyPolicyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivacyPolicyAccepted
{
    public function __construct(
        private HrSession $hrSession,
        private PrivacyPolicyService $privacyPolicy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (int) ($this->hrSession->userId() ?? 0);
        if ($userId <= 0) {
            return $next($request);
        }

        if ($this->privacyPolicy->hasAccepted($userId)) {
            return $next($request);
        }

        if ($request->routeIs('privacy-policy.*')) {
            return $next($request);
        }

        session(['privacy_policy_intended_url' => $request->fullUrl()]);

        return redirect()->route('privacy-policy.show');
    }
}
