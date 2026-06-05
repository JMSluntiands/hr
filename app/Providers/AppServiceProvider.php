<?php

namespace App\Providers;

use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\EmployeeLayoutComposer;
use App\View\Composers\InventoryLayoutComposer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Manila'));

        try {
            DB::statement("SET time_zone = '+08:00'");
        } catch (\Throwable) {
            // database may be unavailable during install
        }

        View::composer('layouts.admin', AdminLayoutComposer::class);
        View::composer(['layouts.employee', 'partials.employee-sidebar'], EmployeeLayoutComposer::class);
        View::composer('layouts.inventory', InventoryLayoutComposer::class);
        View::composer('admin.*', AdminLayoutComposer::class);
        View::composer('employee.*', EmployeeLayoutComposer::class);
        View::composer('inventory.*', InventoryLayoutComposer::class);
        View::composer('partials.inventory-sidebar', InventoryLayoutComposer::class);
        View::composer('partials.admin-sidebar', AdminLayoutComposer::class);
    }
}
