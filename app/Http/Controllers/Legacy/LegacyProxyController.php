<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyProxyController extends Controller
{
    /**
     * Serve legacy PHP pages and static assets under /legacy/{path}.
     */
    public function handle(Request $request, string $relativePath = ''): Response|BinaryFileResponse
    {
        $base = rtrim(config('hr.legacy_path'), DIRECTORY_SEPARATOR);
        $relativePath = trim(str_replace(['..', '\\'], ['', '/'], $relativePath), '/');
        if ($relativePath === '') {
            $relativePath = 'index.php';
        }
        if (! str_contains(basename($relativePath), '.')) {
            $asPhp = rtrim($relativePath, '/').'.php';
            $phpFile = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $asPhp);
            $relativePath = is_file($phpFile) ? $asPhp : rtrim($relativePath, '/').'/index.php';
        }
        $full = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (is_dir($full)) {
            $full = rtrim($full, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'index.php';
        }

        if (! is_file($full)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        if ($ext !== 'php') {
            return response()->file($full);
        }

        $this->bootstrapLegacySession();

        $cwd = getcwd();
        chdir(dirname($full));

        $safeSession = $base.DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'safe_session_start.php';
        if (is_file($safeSession)) {
            require_once $safeSession;
        }

        ob_start();
        $previousHandler = set_error_handler(function (int $severity, string $message): bool {
            if ($severity === E_NOTICE && str_contains($message, 'session_start(): Ignoring session_start()')) {
                return true;
            }

            return false;
        });

        try {
            require $full;
            $content = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            chdir($cwd);
            throw $e;
        } finally {
            if ($previousHandler !== null) {
                set_error_handler($previousHandler);
            } else {
                restore_error_handler();
            }
        }

        chdir($cwd);

        $response = response($content);
        $this->forwardLegacyHeaders($response);

        return $response;
    }

    private function bootstrapLegacySession(): void
    {
        if (! defined('HR_LEGACY_EMBEDDED')) {
            define('HR_LEGACY_EMBEDDED', true);
        }
        if (! defined('HR_APP_URL')) {
            define('HR_APP_URL', rtrim(url('/'), '/'));
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        foreach ([
            'user_id' => session('user_id'),
            'role' => session('role'),
            'name' => session('name'),
            'last_activity' => session('last_activity'),
            'admin_module' => session('admin_module'),
            'employee_module' => session('employee_module'),
            'is_default_password' => session('is_default_password'),
            'login_cache_buster' => session('login_cache_buster'),
        ] as $key => $value) {
            if ($value !== null) {
                $_SESSION[$key] = $value;
            }
        }
    }

    private function forwardLegacyHeaders(Response $response): void
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'location:') === 0) {
                $location = trim(substr($header, 9));
                $location = $this->rewriteLegacyUrl($location);
                $response->headers->set('Location', $location, true);
            }
        }
    }

    private function rewriteLegacyUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (str_contains($url, 'index.php') && ! str_contains($url, 'legacy')) {
            return url('/');
        }

        if ($url === 'request.php' || str_ends_with($url, '/request.php') || str_contains($url, 'employee/request.php')) {
            return url('/employee/requests');
        }

        if (str_contains($url, '/employee/requests') || $url === '/employee/requests') {
            return url('/employee/requests');
        }

        if ($url === 'inventory.php' || str_starts_with($url, 'inventory.php?')) {
            return url('/employee/inventory'.(str_contains($url, '?') ? substr($url, strpos($url, '?')) : ''));
        }

        $inventoryRedirects = [
            'inventory.php' => '/inventory',
            'index.php' => '/inventory',
        ];
        foreach ($inventoryRedirects as $file => $target) {
            if (str_ends_with($url, '/inventory/'.$file) || $url === 'inventory/'.$file) {
                return url($target);
            }
        }

        if (preg_match('#^\.\./#', $url) || preg_match('#^\./#', $url) || ! str_starts_with($url, '/')) {
            return url('/legacy/'.ltrim(str_replace('../', '', $url), '/'));
        }

        if (preg_match('#(?:^/?legacy)?/?inventory/(inventory|index)\.php#i', $url)) {
            return url('/inventory');
        }

        if (! str_starts_with($url, '/legacy')) {
            return url('/legacy'.(str_starts_with($url, '/') ? '' : '/').ltrim($url, '/'));
        }

        return url($url);
    }
}
