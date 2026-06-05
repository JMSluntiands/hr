<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\ActivityLogger;
use App\Services\HrSession;
use App\Services\TimeInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private HrSession $hrSession,
        private TimeInService $timeInService,
        private ActivityLogger $activityLogger,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->hrSession->isLoggedIn()) {
            return redirect()->route('home');
        }

        return view('auth.login', [
            'errors' => $this->mapQueryErrors($request),
            'timeout' => $request->boolean('timeout'),
            'privacyDeclined' => session()->pull('privacy_declined'),
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $action = strtolower(trim((string) $request->input('action', 'login')));
        if ($action !== 'timein') {
            $action = 'login';
        }

        if ($email === '' || $password === '') {
            return response()->json(['status' => 'error', 'message' => 'Please fill in all fields']);
        }

        if (! $this->authService->emailAllowed($email)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access is restricted to @luntiands.com email addresses only.',
            ]);
        }

        $user = $this->authService->findByCredentials($email, $password);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Invalid email or password']);
        }

        if ($inactive = $this->authService->validateActiveEmployee($user)) {
            return response()->json(['status' => 'error', 'message' => $inactive]);
        }

        if ($action === 'timein' && $user->isEmployee()) {
            return $this->handleTimeIn($user);
        }

        $this->authService->login($user, $password);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'role' => session(HrSession::ROLE),
            'is_default_password' => (bool) session(HrSession::IS_DEFAULT_PASSWORD),
            'cache_buster' => session(HrSession::LOGIN_CACHE_BUSTER),
        ]);
    }

    private function handleTimeIn($user): JsonResponse
    {
        $now = $this->timeInService->manilaNow();
        $today = $now->format('Y-m-d');
        $this->timeInService->ensureTable();

        if ($this->timeInService->hasTimeInToday((int) $user->id, $today)) {
            $nextReset = $now->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s');

            return response()->json([
                'status' => 'error',
                'message' => 'You have already timed in today. Please try again after 12:00 AM.',
                'action' => 'timein',
                'already_timed_in' => true,
                'next_reset' => $nextReset,
            ]);
        }

        if (! $this->timeInService->record((int) $user->id, $today, $now->format('Y-m-d H:i:s'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not save your Time In. Please try again.',
                'action' => 'timein',
            ]);
        }

        $name = $user->displayName();
        $this->activityLogger->log((int) $user->id, $name, 'Time In', "Recorded daily time in for {$today}");
        $this->timeInService->notifySlack($name);

        return response()->json([
            'status' => 'success',
            'message' => 'Time In recorded. Notification sent to Slack.',
            'action' => 'timein',
            'already_timed_in' => false,
            'time_in_date' => $today,
            'disable_until' => $now->copy()->addDay()->startOfDay()->format('Y-m-d').' 00:00:00',
        ]);
    }

    private function mapQueryErrors(Request $request): array
    {
        $key = (string) $request->query('error', '');

        return match ($key) {
            'google_not_configured' => ['Google Sign-In is not configured.'],
            'google_denied' => ['Google sign-in was cancelled or denied.'],
            'google_no_code' => ['Google did not return an authorization.'],
            'google_token_failed' => ['Could not verify Google sign-in. Try again.'],
            'google_no_email' => ['Google did not provide your email.'],
            'domain_restricted' => ['Access is restricted to @luntiands.com email addresses only.'],
            'no_hr_account' => ['No HR account found for this email. Ask admin to add you.'],
            'account_inactive' => ['Your account is inactive. Please contact HR.'],
            default => [],
        };
    }
}
