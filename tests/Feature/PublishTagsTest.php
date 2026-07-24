<?php

declare(strict_types=1);

it('ships a complete package config skeleton', function () {
    $config = require __DIR__.'/../../config/laravel-mailmanager.php';

    expect($config)->toBeArray()
        ->and($config['tables']['settings'])->toBe('mailmanager_settings')
        ->and($config['tables']['templates'])->toBe('email_templates')
        ->and($config['ui']['enabled'])->toBeFalse()
        ->and($config['permissions']['templates']['view'])->toBe('email-templates.view');
});

it('ships package migrations for core tables and audits', function () {
    $files = collect(glob(__DIR__.'/../../database/migrations/*.php') ?: [])
        ->map(fn (string $path): string => basename($path));

    expect($files->first(fn (string $name): bool => str_contains($name, 'audits')))->not->toBeNull()
        ->and($files->first(fn (string $name): bool => str_contains($name, 'email_templates')))->not->toBeNull()
        ->and($files->first(fn (string $name): bool => str_contains($name, 'email_template_versions')))->not->toBeNull()
        ->and($files->first(fn (string $name): bool => str_contains($name, 'mailmanager_settings')))->not->toBeNull()
        ->and($files->first(fn (string $name): bool => str_contains($name, 'email_logs')))->not->toBeNull();
});
