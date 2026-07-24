<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Enums\TemplateStatus;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\CannotSendInactiveTemplateException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\DeliveryDisabledException;
use NuzulFikrieCoder\LaravelMailmanager\Mail\TemplateMailable;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;
use NuzulFikrieCoder\LaravelMailmanager\Support\ContentHasher;
use OwenIt\Auditing\Models\Audit;

it('creates draft templates and versions content', function () {
    $service = app(EmailTemplateService::class);

    $template = $service->create([
        'name' => 'Welcome',
        'slug' => 'user-welcome',
        'subject' => 'Hello {name}',
        'html_content' => '<p>Hello {name}</p>',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);

    expect($template->status)->toBe(TemplateStatus::Draft)
        ->and($template->versions)->toHaveCount(1)
        ->and($template->versions->first()?->content_hash)->toBe(
            ContentHasher::hash(
                'Hello {name}',
                '<p>Hello {name}</p>',
                $template->design_json ?? [],
                $template->parameters ?? [],
            ),
        );
});

it('does not create a new version when only description changes', function () {
    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'A',
        'slug' => 'a-template',
        'subject' => 'S',
        'html_content' => '<p>H</p>',
        'parameters' => [],
    ]);

    $service->update($template, ['description' => 'only meta']);

    expect($template->fresh()->versions)->toHaveCount(1);
});

it('activates deactivates and audits template changes', function () {
    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'B',
        'slug' => 'b-template',
        'subject' => 'S',
        'html_content' => '<p>H</p>',
    ]);

    $service->activate($template);
    expect($template->fresh()->status)->toBe(TemplateStatus::Active);

    $service->deactivate($template->fresh());
    expect($template->fresh()->status)->toBe(TemplateStatus::Inactive);

    expect(Audit::query()->where('auditable_type', EmailTemplate::class)->count())->toBeGreaterThan(0);
});

it('sends active templates via mailable and logs', function () {
    Mail::fake();

    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'Welcome',
        'slug' => 'send-welcome',
        'subject' => 'Hello {name}',
        'html_content' => '<p>Hello {name}</p>',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);
    $service->activate($template);

    app(SettingsRepository::class)->putMany('mail', [
        'mailer' => 'array',
        'delivery_enabled' => true,
        'from_address' => 'noreply@example.com',
        'from_name' => 'App',
    ]);

    $log = $service->send('send-welcome', 'user@example.com', ['name' => 'Ali']);

    Mail::assertSent(TemplateMailable::class);
    expect($log->status)->toBe(EmailLogStatus::Sent)
        ->and($log->recipient)->toBe('user@example.com')
        ->and($log->email_template_version_id)->not->toBeNull()
        ->and($log->is_test)->toBeFalse();
});

it('rejects sending inactive templates', function () {
    $service = app(EmailTemplateService::class);
    $service->create([
        'name' => 'C',
        'slug' => 'inactive-send',
        'subject' => 'S',
        'html_content' => '<p>H</p>',
    ]);

    $service->send('inactive-send', 'a@b.com');
})->throws(CannotSendInactiveTemplateException::class);

it('suppresses delivery when disabled', function () {
    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'D',
        'slug' => 'suppress-send',
        'subject' => 'S',
        'html_content' => '<p>H</p>',
    ]);
    $service->activate($template);

    app(SettingsRepository::class)->putMany('mail', [
        'delivery_enabled' => false,
    ]);

    $service->send('suppress-send', 'a@b.com');
})->throws(DeliveryDisabledException::class);

it('content hash is stable regardless of key insertion order', function () {
    $a = ContentHasher::hash('S', '<p>x</p>', ['b' => 1, 'a' => 2], ['z' => ['type' => 'string'], 'm' => ['type' => 'string']]);
    $b = ContentHasher::hash('S', '<p>x</p>', ['a' => 2, 'b' => 1], ['m' => ['type' => 'string'], 'z' => ['type' => 'string']]);

    expect($a)->toBe($b);
});
