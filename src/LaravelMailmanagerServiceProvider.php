<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use NuzulFikrieCoder\LaravelMailmanager\Console\Commands\TestSmtpCommand;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Policies\EmailLogPolicy;
use NuzulFikrieCoder\LaravelMailmanager\Policies\EmailTemplatePolicy;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\TemplateRenderer;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailLogService;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;
use NuzulFikrieCoder\LaravelMailmanager\Services\MailConfigApplier;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;
use NuzulFikrieCoder\LaravelMailmanager\Services\SmtpProbeService;
use NuzulFikrieCoder\LaravelMailmanager\Services\TemplateVersionService;

class LaravelMailmanagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-mailmanager.php', 'laravel-mailmanager');

        $this->app->singleton(LaravelMailmanager::class);

        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(MailConfigApplier::class);
        $this->app->singleton(TemplateRenderer::class);
        $this->app->singleton(TemplateVersionService::class);
        $this->app->singleton(EmailLogService::class);
        $this->app->singleton(EmailTemplateService::class);
        $this->app->singleton(SmtpProbeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(EmailTemplate::class, EmailTemplatePolicy::class);
        Gate::policy(EmailLog::class, EmailLogPolicy::class);

        // Always register routes; EnsureMailmanagerUiEnabled aborts 404 when UI is disabled.
        $this->loadRoutesFrom(__DIR__.'/../routes/laravel-mailmanager.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-mailmanager');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravel-mailmanager');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-mailmanager.php' => config_path('laravel-mailmanager.php'),
        ], ['laravel-mailmanager', 'laravel-mailmanager-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-mailmanager'),
        ], ['laravel-mailmanager', 'laravel-mailmanager-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/laravel-mailmanager'),
        ], ['laravel-mailmanager', 'laravel-mailmanager-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/laravel-mailmanager'),
        ], ['laravel-mailmanager', 'laravel-mailmanager-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['laravel-mailmanager', 'laravel-mailmanager-migrations']);

        $this->commands([
            TestSmtpCommand::class,
        ]);
    }
}
