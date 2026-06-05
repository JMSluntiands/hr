<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\HrSession;
use App\Services\PrivacyPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivacyPolicyController extends Controller
{
    public function __construct(
        private HrSession $hrSession,
        private PrivacyPolicyService $privacyPolicy,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $this->hrSession->isLoggedIn()) {
            return redirect()->route('login');
        }

        $userId = (int) $this->hrSession->userId();
        if ($this->privacyPolicy->hasAccepted($userId)) {
            return redirect()->to($this->intendedUrl());
        }

        return view('auth.privacy-policy', [
            'userName' => session(HrSession::NAME, 'User'),
            'policyVersion' => $this->privacyPolicy->currentVersion(),
            'lastUpdated' => (string) config('hr.privacy_policy_last_updated', 'June 2026'),
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        if (! $this->hrSession->isLoggedIn()) {
            return redirect()->route('login');
        }

        if (! $request->boolean('agree')) {
            return redirect()
                ->route('privacy-policy.show')
                ->with('error', 'You must agree to the Privacy Policy to continue.');
        }

        $userId = (int) $this->hrSession->userId();
        $this->privacyPolicy->accept($userId);
        session()->forget('privacy_policy_intended_url');

        return redirect()
            ->to($this->intendedUrl())
            ->with('success', 'Privacy Policy accepted. Welcome!');
    }

    public function decline(): RedirectResponse
    {
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('privacy_declined', 'You must accept the Privacy Policy to use this system. You have been logged out.');
    }

    private function intendedUrl(): string
    {
        $stored = (string) session('privacy_policy_intended_url', '');
        if ($stored !== '' && ! str_contains($stored, '/privacy-policy')) {
            return $stored;
        }

        return route('home');
    }
}
