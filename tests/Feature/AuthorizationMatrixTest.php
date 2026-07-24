<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;

beforeEach(function () {
    config([
        'laravel-mailmanager.ui.enabled' => true,
        'laravel-mailmanager.route.middleware' => ['web'],
    ]);
});

/**
 * @return array<string, string>
 */
function abilityMap(): array
{
    return [
        'view templates' => 'email-templates.view',
        'create templates' => 'email-templates.create',
        'update templates' => 'email-templates.update',
        'delete templates' => 'email-templates.delete',
        'activate templates' => 'email-templates.activate',
        'send test' => 'email-templates.send-test',
        'view settings' => 'email-settings.view',
        'update settings' => 'email-settings.update',
        'view logs' => 'email-logs.view',
        'retry logs' => 'email-logs.retry',
    ];
}

it('denies each admin surface without the matching ability', function (string $method, string $uriFactory, string $requiredAbility) {
    $template = EmailTemplate::factory()->create(['slug' => 'authz-tpl']);
    $log = EmailLog::factory()->create();

    $uri = match ($uriFactory) {
        'templates.index' => route('mailmanager.templates.index'),
        'templates.create' => route('mailmanager.templates.create'),
        'templates.edit' => route('mailmanager.templates.edit', $template),
        'templates.destroy' => route('mailmanager.templates.destroy', $template),
        'templates.activate' => route('mailmanager.templates.activate', $template),
        'templates.send-test' => route('mailmanager.templates.send-test', $template),
        'settings.edit' => route('mailmanager.settings.mail.edit'),
        'settings.update' => route('mailmanager.settings.mail.update'),
        'logs.index' => route('mailmanager.logs.index'),
        'logs.retry' => route('mailmanager.logs.retry', $log),
        default => throw new InvalidArgumentException($uriFactory),
    };

    // Grant every ability except the required one.
    $all = array_values(abilityMap());
    $allowed = array_values(array_filter($all, fn (string $a): bool => $a !== $requiredAbility));
    $this->actingAsMailmanagerUser($allowed);

    $response = $this->{strtolower($method)}($uri, $method === 'PUT' ? [
        'host' => 'x.test',
        'encryption' => 'tls',
    ] : ($method === 'POST' && str_contains($uriFactory, 'send-test') ? [
        'to' => 'a@b.com',
        'parameters' => '{}',
    ] : []));

    $response->assertForbidden();
})->with([
    'view templates' => ['GET', 'templates.index', 'email-templates.view'],
    'create templates' => ['GET', 'templates.create', 'email-templates.create'],
    'update templates' => ['GET', 'templates.edit', 'email-templates.update'],
    'delete templates' => ['DELETE', 'templates.destroy', 'email-templates.delete'],
    'activate templates' => ['POST', 'templates.activate', 'email-templates.activate'],
    'send test' => ['POST', 'templates.send-test', 'email-templates.send-test'],
    'view settings' => ['GET', 'settings.edit', 'email-settings.view'],
    'update settings' => ['PUT', 'settings.update', 'email-settings.update'],
    'view logs' => ['GET', 'logs.index', 'email-logs.view'],
    'retry logs' => ['POST', 'logs.retry', 'email-logs.retry'],
]);

it('allows settings view without update ability but forbids password change', function () {
    $this->actingAsMailmanagerUser(['email-settings.view']);

    $this->get(route('mailmanager.settings.mail.edit'))->assertOk();

    $this->put(route('mailmanager.settings.mail.update'), [
        'host' => 'evil.test',
        'password' => 'new-secret',
        'encryption' => 'tls',
    ])->assertForbidden();
});
