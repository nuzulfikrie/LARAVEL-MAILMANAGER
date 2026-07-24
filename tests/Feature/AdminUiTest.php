<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;

beforeEach(function () {
    config([
        'laravel-mailmanager.ui.enabled' => true,
        'laravel-mailmanager.route.middleware' => ['web'],
    ]);
});

it('returns 404 for admin ui when disabled', function () {
    config(['laravel-mailmanager.ui.enabled' => false]);
    $this->actingAsMailmanagerUser();

    $this->get('/mailmanager')->assertNotFound();
});

it('forbids template index without abilities', function () {
    $this->actingAsMailmanagerUser([]);

    $this->get(route('mailmanager.templates.index'))->assertForbidden();
});

it('lists templates when authorized', function () {
    $this->actingAsMailmanagerUser();
    EmailTemplate::factory()->create(['slug' => 'listed-one', 'name' => 'Listed One']);

    $this->get(route('mailmanager.templates.index'))
        ->assertOk()
        ->assertSee('Listed One');
});

it('creates a template via the admin form', function () {
    $this->actingAsMailmanagerUser();

    $this->post(route('mailmanager.templates.store'), [
        'name' => 'Welcome UI',
        'slug' => 'welcome-ui',
        'subject' => 'Hello {name}',
        'html_content' => '<p>Hello {name}</p>',
        'parameters' => json_encode([
            'name' => ['type' => 'string', 'required' => true],
        ]),
        'design_json' => '{}',
    ])->assertRedirect();

    expect(EmailTemplate::query()->where('slug', 'welcome-ui')->exists())->toBeTrue();
});

it('activates a template via admin action', function () {
    $this->actingAsMailmanagerUser();
    $template = EmailTemplate::factory()->create(['slug' => 'to-activate']);

    $this->post(route('mailmanager.templates.activate', $template))->assertRedirect();

    expect($template->fresh()?->status->value)->toBe('active');
});

it('updates mail settings without exposing password', function () {
    $this->actingAsMailmanagerUser();

    $this->put(route('mailmanager.settings.mail.update'), [
        'host' => 'smtp.ui.test',
        'port' => 587,
        'encryption' => 'tls',
        'password' => 'super-secret',
        'delivery_enabled' => '1',
        'from_address' => 'noreply@example.com',
    ])->assertRedirect(route('mailmanager.settings.mail.edit'));

    $this->get(route('mailmanager.settings.mail.edit'))
        ->assertOk()
        ->assertSee('smtp.ui.test')
        ->assertDontSee('super-secret');
});

it('forbids settings without settings ability', function () {
    $this->actingAsMailmanagerUser(['email-templates.view']);

    $this->get(route('mailmanager.settings.mail.edit'))->assertForbidden();
});

it('shows email logs when authorized', function () {
    $this->actingAsMailmanagerUser();
    EmailLog::factory()->create([
        'recipient' => 'loguser@example.com',
        'rendered_subject' => 'Logged subject',
        'status' => EmailLogStatus::Sent,
    ]);

    $this->get(route('mailmanager.logs.index'))
        ->assertOk()
        ->assertSee('loguser@example.com')
        ->assertSee('Logged subject');
});

it('rejects ineligible log retry', function () {
    $this->actingAsMailmanagerUser();
    $log = EmailLog::factory()->failed()->create([
        'rendered_html' => null,
        'failure_type' => EmailFailureType::Validation,
    ]);

    $this->post(route('mailmanager.logs.retry', $log))
        ->assertRedirect()
        ->assertSessionHasErrors('retry');
});

it('previews a template as json', function () {
    $this->actingAsMailmanagerUser();
    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'Previewable',
        'slug' => 'previewable',
        'subject' => 'Hi {name}',
        'html_content' => '<p>Hi {name}</p>',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);

    $this->postJson(route('mailmanager.templates.preview', $template), [
        'parameters' => ['name' => 'Ali'],
    ])->assertOk()
        ->assertJsonPath('subject', 'Hi Ali');
});

it('sends a test email from the admin ui', function () {
    Mail::fake();
    $this->actingAsMailmanagerUser();

    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'Testable',
        'slug' => 'testable-ui',
        'subject' => 'Hi {name}',
        'html_content' => '<p>Hi {name}</p>',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);
    $service->activate($template);

    $this->post(route('mailmanager.templates.send-test', $template), [
        'to' => 'tester@example.com',
        'parameters' => json_encode(['name' => 'Ali']),
    ])->assertRedirect();

    expect(EmailLog::query()->where('is_test', true)->exists())->toBeTrue();
});
