<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Tests\Browser;

use Laravel\Dusk\Browser;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;
use PHPUnit\Framework\Attributes\Test;

class AdminUiDuskTest extends DuskTestCase
{
    #[Test]
    public function templates_index_renders_and_can_create_template(): void
    {
        $this->browseAsAdmin(function (Browser $browser): void {
            $browser->visit('/mailmanager')
                ->waitForText('Email templates', 15)
                ->assertSee('Email templates')
                ->assertSee('New template')
                ->clickLink('New template')
                ->waitForText('Create template', 15)
                ->type('name', 'Dusk Welcome')
                ->type('slug', 'dusk-welcome')
                ->type('subject', 'Hello {name}')
                ->type('html_content', '<p>Hello {name}</p>')
                ->type('parameters', json_encode([
                    'name' => ['type' => 'string', 'required' => true],
                ], JSON_PRETTY_PRINT))
                ->press('Save template')
                ->waitForText('Template created.', 15)
                ->assertSee('Edit Dusk Welcome')
                ->assertInputValue('name', 'Dusk Welcome')
                ->assertInputValue('slug', 'dusk-welcome');
        });

        $this->assertDatabaseHas('email_templates', [
            'slug' => 'dusk-welcome',
            'name' => 'Dusk Welcome',
        ]);
    }

    #[Test]
    public function can_activate_deactivate_and_open_versions(): void
    {
        $service = app(EmailTemplateService::class);
        $template = $service->create([
            'name' => 'Status Flow',
            'slug' => 'status-flow',
            'subject' => 'Status',
            'html_content' => '<p>ok</p>',
            'parameters' => [],
        ]);

        $this->browseAsAdmin(function (Browser $browser) use ($template): void {
            $browser->visit('/mailmanager')
                ->waitForText('Status Flow', 15)
                ->assertSee('draft')
                ->press('Activate')
                ->waitForText('Template activated.', 15)
                ->assertSee('active')
                ->press('Deactivate')
                ->waitForText('Template deactivated.', 15)
                ->assertSee('inactive')
                ->clickLink('Edit')
                ->waitForText('Edit Status Flow', 15)
                ->clickLink('Versions & audit')
                ->waitForText('Content versions', 15)
                ->assertSee('Audit trail')
                ->assertSee($template->name);
        });
    }

    #[Test]
    public function can_update_smtp_settings_without_showing_password(): void
    {
        $this->browseAsAdmin(function (Browser $browser): void {
            $browser->visit('/mailmanager/settings/mail')
                ->waitForText('SMTP settings', 15)
                // Use trivial test doubles only (never real SMTP credentials in the repo).
                ->type('host', 'smtp.example.test')
                ->type('port', '587')
                ->select('encryption', 'tls')
                ->type('username', 'mailer@example.test')
                ->type('password', 'password')
                ->type('from_address', 'noreply@example.test')
                ->type('from_name', 'Dusk App')
                ->check('delivery_enabled')
                ->press('Save settings')
                ->waitForText('Mail settings saved.', 15)
                ->assertInputValue('host', 'smtp.example.test')
                ->assertInputValue('username', 'mailer@example.test')
                // Password input must stay blank after save (masked; never echoed).
                ->assertInputValue('password', '');
        });

        $settings = app(SettingsRepository::class)->group('mail');
        $this->assertSame('smtp.example.test', $settings['host']);
        $this->assertSame('password', $settings['password']);

        $display = app(SettingsRepository::class)->groupForDisplay('mail');
        $this->assertTrue((bool) ($display['password_set'] ?? false));
        $this->assertSame('********', $display['password'] ?? null);
    }

    #[Test]
    public function can_preview_template_and_browse_logs(): void
    {
        $service = app(EmailTemplateService::class);
        $template = $service->create([
            'name' => 'Preview Me',
            'slug' => 'preview-me',
            'subject' => 'Hi {name}',
            'html_content' => '<p id="hello">Hi {name}</p>',
            'parameters' => [
                'name' => ['type' => 'string', 'required' => true],
            ],
        ]);
        $service->activate($template);

        $version = EmailTemplateVersion::query()
            ->where('email_template_id', $template->id)
            ->orderByDesc('version')
            ->firstOrFail();

        EmailLog::query()->create([
            'email_template_id' => $template->id,
            'email_template_version_id' => $version->id,
            'recipient' => 'dusk-log@example.com',
            'rendered_subject' => 'Logged from Dusk',
            'status' => EmailLogStatus::Sent,
            'is_test' => false,
            'sent_at' => now(),
        ]);

        $this->browseAsAdmin(function (Browser $browser) use ($template): void {
            $browser->visit('/mailmanager/templates/'.$template->id.'/edit')
                ->waitForText('Edit Preview Me', 15)
                ->type('#preview_parameters', '{"name":"Ali"}')
                ->press('Render preview')
                ->pause(1500)
                ->withinFrame('#mm-preview-frame', function (Browser $frame): void {
                    $frame->assertSee('Hi Ali');
                });

            $browser->visit('/mailmanager/logs')
                ->waitForText('Email logs', 15)
                ->assertSee('dusk-log@example.com')
                ->assertSee('Logged from Dusk')
                ->clickLink('View')
                ->waitForText('Log #', 15)
                ->assertSee('dusk-log@example.com');
        });
    }

    #[Test]
    public function navigation_links_work_across_admin_sections(): void
    {
        $this->browseAsAdmin(function (Browser $browser): void {
            $browser->visit('/mailmanager')
                ->waitForText('Email templates', 15)
                ->clickLink('SMTP Settings')
                ->waitForText('SMTP settings', 15)
                ->assertPathIs('/mailmanager/settings/mail')
                ->clickLink('Email Logs')
                ->waitForText('Email logs', 15)
                ->assertPathIs('/mailmanager/logs')
                ->clickLink('Templates')
                ->waitForText('Email templates', 15)
                ->assertPathIs('/mailmanager');
        });
    }

    #[Test]
    public function can_duplicate_template_from_index(): void
    {
        app(EmailTemplateService::class)->create([
            'name' => 'Original',
            'slug' => 'original-tpl',
            'subject' => 'Orig',
            'html_content' => '<p>orig</p>',
        ]);

        $this->browseAsAdmin(function (Browser $browser): void {
            $browser->visit('/mailmanager')
                ->waitForText('Original', 15)
                ->press('Duplicate')
                ->waitForText('Template duplicated.', 15)
                ->assertSee('Edit')
                ->assertInputValueIsNot('slug', 'original-tpl');
        });

        $this->assertGreaterThanOrEqual(2, EmailTemplate::query()->count());
    }
}
