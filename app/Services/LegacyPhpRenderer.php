<?php

namespace App\Services;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LegacyPhpRenderer
{
    public function renderEmployee(string $script): Response
    {
        return $this->render('employee/'.$script);
    }

    public function render(string $relativePath): Response
    {
        $path = base_path('legacy/'.ltrim($relativePath, '/'));
        if (! is_file($path)) {
            abort(HttpResponse::HTTP_NOT_FOUND);
        }

        if (! defined('HR_LEGACY_EMBEDDED')) {
            define('HR_LEGACY_EMBEDDED', true);
        }
        if (! defined('HR_APP_URL')) {
            define('HR_APP_URL', rtrim(url('/'), '/'));
        }

        $this->bootstrapLegacySession();

        $cwd = getcwd();
        chdir(dirname($path));

        ob_start();
        $previousHandler = set_error_handler(function (int $severity, string $message): bool {
            if ($severity === E_NOTICE && str_contains($message, 'session_start(): Ignoring session_start()')) {
                return true;
            }

            return false;
        });

        try {
            require $path;
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
                $response->headers->set('Location', $this->rewriteLegacyUrl($location), true);
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

        if ($url === 'request.php' || str_ends_with($url, '/request.php') || str_ends_with($url, 'employee/request.php')) {
            return url('/employee/requests');
        }

        if ($url === 'incident-report-list.php' || str_ends_with($url, 'incident-report-list.php')) {
            return url('/employee/incident-reports');
        }

        if ($url === 'incident-report-add.php' || str_ends_with($url, 'incident-report-add.php')) {
            return url('/employee/incident-reports/create');
        }

        if ($url === 'performance-form-review.php' || str_ends_with($url, 'performance-form-review.php')) {
            return url('/employee/performance/form-review');
        }

        if ($url === 'performance-review-submissions.php' || str_ends_with($url, 'performance-review-submissions.php')) {
            return url('/employee/performance/submissions');
        }

        if ($url === 'performance.php' || str_ends_with($url, '/performance.php')) {
            return url('/employee/performance');
        }

        if ($url === 'performance-my-reviews.php' || str_ends_with($url, 'performance-my-reviews.php')) {
            return url('/employee/performance/my-reviews');
        }

        if (! str_starts_with($url, '/')) {
            return url('/employee/'.$url);
        }

        return url($url);
    }
}
