<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Services\MailConfigApplier;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;

it('maps encryption none tls and ssl correctly and purges mailer config', function () {
    $repo = app(SettingsRepository::class);
    $applier = app(MailConfigApplier::class);

    $repo->putMany('mail', [
        'mailer' => 'smtp',
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user',
        'password' => 'secret',
        'encryption' => 'none',
    ]);

    $applier->apply();
    $mailer = config('mail.mailers.mailmanager');

    expect($mailer['scheme'])->toBe('smtp')
        ->and($mailer['auto_tls'])->toBeFalse()
        ->and($mailer['require_tls'])->toBeFalse()
        ->and($mailer)->not->toHaveKey('encryption')
        ->and($mailer['host'])->toBe('smtp.example.com');

    $repo->putMany('mail', ['encryption' => 'tls']);
    $applier->apply();
    $mailer = config('mail.mailers.mailmanager');
    expect($mailer['scheme'])->toBe('smtp')
        ->and($mailer['auto_tls'])->toBeTrue()
        ->and($mailer['require_tls'])->toBeTrue();

    $repo->putMany('mail', ['encryption' => 'ssl']);
    $applier->apply();
    $mailer = config('mail.mailers.mailmanager');
    expect($mailer['scheme'])->toBe('smtps');
});
