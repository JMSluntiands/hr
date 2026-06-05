<?php

use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureAdminSidebarPermission;
use App\Http\Middleware\EnsureEmployeeRole;
use App\Http\Middleware\EnsurePrivacyPolicyAccepted;
use App\Http\Middleware\HrAuthenticate;
use App\Http\Middleware\HrSessionTimeout;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'hr.auth' => HrAuthenticate::class,
            'hr.session' => HrSessionTimeout::class,
            'hr.admin' => EnsureAdminRole::class,
            'hr.admin.sidebar' => EnsureAdminSidebarPermission::class,
            'hr.employee' => EnsureEmployeeRole::class,
            'hr.privacy' => EnsurePrivacyPolicyAccepted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
