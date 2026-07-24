<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\TemplateStatus;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;
use NuzulFikrieCoder\LaravelMailmanager\Models\Setting;

it('persists an email template with casts and default draft status', function () {
    $template = EmailTemplate::factory()->create([
        'name' => 'Welcome Email',
        'slug' => 'user-welcome',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);

    expect($template->status)->toBe(TemplateStatus::Draft)
        ->and($template->parameters)->toBeArray()
        ->and($template->design_json)->toBeArray()
        ->and($template->isSendable())->toBeFalse();
});

it('scopes active and sendable templates', function () {
    EmailTemplate::factory()->create(['slug' => 'draft-one']);
    $active = EmailTemplate::factory()->active()->create(['slug' => 'active-one']);
    EmailTemplate::factory()->inactive()->create(['slug' => 'inactive-one']);

    expect(EmailTemplate::query()->active()->pluck('slug')->all())->toBe(['active-one'])
        ->and(EmailTemplate::query()->sendable()->pluck('slug')->all())->toBe(['active-one'])
        ->and($active->fresh()->isSendable())->toBeTrue();
});

it('soft deletes templates', function () {
    $template = EmailTemplate::factory()->active()->create(['slug' => 'soft-delete-me']);
    $template->delete();

    expect(EmailTemplate::query()->whereKey($template->id)->exists())->toBeFalse()
        ->and(EmailTemplate::withTrashed()->whereKey($template->id)->exists())->toBeTrue()
        ->and($template->trashed())->toBeTrue()
        ->and(EmailTemplate::withTrashed()->find($template->id)?->deleted_at)->not->toBeNull();
});

it('relates versions and logs to a template', function () {
    $template = EmailTemplate::factory()->create(['slug' => 'with-relations']);
    $version = EmailTemplateVersion::factory()->create([
        'email_template_id' => $template->id,
        'version' => 1,
    ]);
    $log = EmailLog::factory()->create([
        'email_template_id' => $template->id,
        'email_template_version_id' => $version->id,
    ]);

    expect($template->versions)->toHaveCount(1)
        ->and($template->latestVersion?->is($version))->toBeTrue()
        ->and($template->logs)->toHaveCount(1)
        ->and($version->template->is($template))->toBeTrue()
        ->and($log->template->is($template))->toBeTrue()
        ->and($log->version->is($version))->toBeTrue();
});

it('casts settings type and group scope', function () {
    Setting::factory()->create([
        'group' => 'mail',
        'key' => 'host',
        'value' => 'smtp.example.com',
        'type' => SettingType::String,
    ]);
    Setting::factory()->create([
        'group' => 'other',
        'key' => 'host',
        'value' => 'ignored',
    ]);

    $mail = Setting::query()->group('mail')->get();

    expect($mail)->toHaveCount(1)
        ->and($mail->first()?->type)->toBe(SettingType::String)
        ->and($mail->first()?->key)->toBe('host');
});

it('casts email log enums and evaluates retry eligibility', function () {
    $eligible = EmailLog::factory()->failed()->create([
        'rendered_html' => '<p>Hello</p>',
        'failure_type' => EmailFailureType::SmtpAuth,
    ]);
    $notEligible = EmailLog::factory()->failed()->create([
        'rendered_html' => null,
        'failure_type' => EmailFailureType::Validation,
    ]);

    expect($eligible->status)->toBe(EmailLogStatus::Failed)
        ->and($eligible->failure_type)->toBe(EmailFailureType::SmtpAuth)
        ->and($eligible->isRetryEligible())->toBeTrue()
        ->and($notEligible->isRetryEligible())->toBeFalse();
});

it('uses configurable table names on models', function () {
    expect((new EmailTemplate)->getTable())->toBe('email_templates')
        ->and((new EmailTemplateVersion)->getTable())->toBe('email_template_versions')
        ->and((new Setting)->getTable())->toBe('mailmanager_settings')
        ->and((new EmailLog)->getTable())->toBe('email_logs');
});
