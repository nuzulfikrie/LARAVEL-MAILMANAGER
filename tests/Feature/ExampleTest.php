<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use NuzulFikrieCoder\LaravelMailmanager\LaravelMailmanager;

it('resolves the singleton', function () {
    expect(app(LaravelMailmanager::class))->toBeInstanceOf(LaravelMailmanager::class);
});

it('returns the same instance from the container', function () {
    expect(app(LaravelMailmanager::class))->toBe(app(LaravelMailmanager::class));
});

it('merges the package config skeleton', function () {
    expect(config('laravel-mailmanager.tables.templates'))->toBe('email_templates')
        ->and(config('laravel-mailmanager.tables.settings'))->toBe('mailmanager_settings')
        ->and(config('laravel-mailmanager.ui.enabled'))->toBeFalse()
        ->and(config('laravel-mailmanager.mail.mailer_name'))->toBe('mailmanager')
        ->and(config('laravel-mailmanager.permissions.templates.view'))->toBe('email-templates.view');
});

it('loads the package translations', function () {
    expect(trans('laravel-mailmanager::messages.placeholder'))->toBe('LaravelMailmanager placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('laravel-mailmanager::placeholder'))->toBeTrue();
});

it('does not register the removed placeholder command', function () {
    expect(Artisan::all())->not->toHaveKey('laravel-mailmanager:placeholder');
});
