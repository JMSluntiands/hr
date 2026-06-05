<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AccountManagementService;
use App\Services\ActivityLogger;
use App\Services\HrSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private AccountManagementService $accounts,
        private ActivityLogger $activityLogger,
        private HrSession $hrSession,
    ) {}

    public function index(): View
    {
        $schemaMessage = $this->accounts->schemaMessage();

        return view('admin.accounts.index', [
            'accounts' => $this->accounts->listAccounts(),
            'schemaMessage' => $schemaMessage,
            'generatedPassword' => session('generated_password'),
            'generatedEmail' => session('generated_email', ''),
            'generatedMode' => session('generated_mode', ''),
        ]);
    }

    public function createEmployee(Request $request): RedirectResponse
    {
        $employeeId = (int) $request->input('employee_id');
        if ($employeeId <= 0) {
            return back()->with('error', 'Invalid employee id.');
        }

        try {
            $result = $this->accounts->createEmployeeAccount($employeeId);
            $this->log('Create Account', (int) ($result['id'] ?? 0), "Created employee login account for {$result['email']}");

            return redirect()->route('admin.accounts.index')
                ->with('success', 'Employee account created. Copy the generated password below.')
                ->with('generated_password', $result['password'])
                ->with('generated_email', $result['email'])
                ->with('generated_mode', 'created');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetPassword(int $id): RedirectResponse
    {
        try {
            $result = $this->accounts->resetEmployeePassword($id);
            $this->log('Reset Password', $id, "Generated new random password for {$result['email']}");

            return redirect()->route('admin.accounts.index')
                ->with('success', 'Password reset complete. Copy the generated password below.')
                ->with('generated_password', $result['password'])
                ->with('generated_email', $result['email'])
                ->with('generated_mode', 'reset');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $request->validate(['role' => 'required|in:admin,employee']);

        try {
            $this->accounts->updateRole($id, $request->input('role'));
            $this->log('Edit Role', $id, 'Role set to '.$request->input('role')." for account id {$id}");

            return redirect()->route('admin.accounts.index')->with('success', 'Role updated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function log(string $action, int $entityId, string $description): void
    {
        $adminId = (int) $this->hrSession->userId();
        $adminName = (string) session(HrSession::NAME, 'Admin');
        $this->activityLogger->log($adminId, $adminName, $action, $description, 'user_login');
    }
}
