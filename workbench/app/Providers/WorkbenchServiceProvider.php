<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        config([
            'laravel-mailmanager.ui.enabled' => true,
            'laravel-mailmanager.route.middleware' => ['web'],
            'mail.default' => 'array',
        ]);

        Gate::before(static fn (): true => true);

        Route::get('/', function () {
            return redirect('/mailmanager');
        });
    }
}
