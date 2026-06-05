<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleAuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private HrSession $hrSession,
    ) {}

    public function redirect(): RedirectResponse
    {
        $config = $this->loadConfig();
        if ($config === null) {
            return redirect()->route('login', ['error' => 'google_not_configured']);
        }

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$params);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('login', ['error' => 'google_denied']);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('login', ['error' => 'google_no_code']);
        }

        $config = $this->loadConfig();
        if ($config === null) {
            return redirect()->route('login', ['error' => 'google_not_configured']);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()->route('login', ['error' => 'google_token_failed']);
        }

        $accessToken = $tokenResponse->json('access_token');
        if (! $accessToken) {
            return redirect()->route('login', ['error' => 'google_token_failed']);
        }

        $userInfo = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        $email = strtolower(trim((string) $userInfo->json('email', '')));

        if ($email === '') {
            return redirect()->route('login', ['error' => 'google_no_email']);
        }

        if (! $this->authService->emailAllowed($email)) {
            return redirect()->route('login', ['error' => 'domain_restricted']);
        }

        $user = $this->authService->findByEmail($email);
        if (! $user) {
            return redirect()->route('login', ['error' => 'no_hr_account']);
        }

        if ($inactive = $this->authService->validateActiveEmployee($user)) {
            return redirect()->route('login', ['error' => 'account_inactive']);
        }

        $this->authService->loginGoogle($user);

        return redirect()->route('home', [
            'cb' => session(HrSession::LOGIN_CACHE_BUSTER),
        ]);
    }

    private function loadConfig(): ?array
    {
        $path = config('hr.google_oauth');
        if (! is_file($path)) {
            return null;
        }

        $config = require $path;

        return is_array($config) ? $config : null;
    }
}
