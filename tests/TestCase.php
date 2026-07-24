<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Support\Facades\Gate;
use NuzulFikrieCoder\LaravelMailmanager\LaravelMailmanagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use OwenIt\Auditing\AuditingServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AuditingServiceProvider::class,
            LaravelMailmanagerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
        $app['config']->set('audit.enabled', true);
        $app['config']->set('audit.console', true);
        $app['config']->set('laravel-mailmanager.route.middleware', ['web']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * @param  list<string>|true  $abilities  true = allow all package abilities
     */
    protected function actingAsMailmanagerUser(array|true $abilities = true): Authenticatable
    {
        $user = new class extends AuthUser
        {
            public $id = 1;

            public $email = 'admin@example.com';

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int
            {
                return 1;
            }
        };

        $this->actingAs($user);

        $all = [
            'email-templates.view',
            'email-templates.create',
            'email-templates.update',
            'email-templates.delete',
            'email-templates.activate',
            'email-templates.send-test',
            'email-settings.view',
            'email-settings.update',
            'email-logs.view',
            'email-logs.retry',
        ];

        $allowed = $abilities === true ? $all : $abilities;

        foreach ($all as $ability) {
            Gate::define($ability, fn () => in_array($ability, $allowed, true));
        }

        return $user;
    }
}
