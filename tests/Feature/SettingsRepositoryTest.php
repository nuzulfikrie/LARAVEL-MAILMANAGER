<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;

it('stores encrypted password and returns decrypted in group', function () {
    $repo = app(SettingsRepository::class);

    $repo->putMany('mail', [
        'host' => 'smtp.example.test',
        'port' => 587,
        // Trivial test double only — never commit real SMTP credentials.
        'password' => 'password',
        'delivery_enabled' => true,
    ]);

    $mail = $repo->group('mail');

    expect($mail['host'])->toBe('smtp.example.test')
        ->and($mail['port'])->toBe(587)
        ->and($mail['password'])->toBe('password')
        ->and($mail['delivery_enabled'])->toBeTrue();

    $display = $repo->groupForDisplay('mail');

    expect($display['password'])->toBe('********')
        ->and($display['password_set'])->toBeTrue()
        ->and($display['password'])->not->toBe('password');
});

it('keeps existing password when blank on putMany', function () {
    $repo = app(SettingsRepository::class);

    $repo->set('mail', 'password', 'original', SettingType::Encrypted, encrypted: true);
    $repo->putMany('mail', ['password' => '', 'host' => 'smtp.x.test']);

    expect($repo->get('mail', 'password'))->toBe('original')
        ->and($repo->get('mail', 'host'))->toBe('smtp.x.test');
});

it('invalidates cache after write', function () {
    $repo = app(SettingsRepository::class);
    $repo->set('mail', 'host', 'a.example');
    expect($repo->get('mail', 'host'))->toBe('a.example');

    $repo->set('mail', 'host', 'b.example');
    expect($repo->get('mail', 'host'))->toBe('b.example');
});
