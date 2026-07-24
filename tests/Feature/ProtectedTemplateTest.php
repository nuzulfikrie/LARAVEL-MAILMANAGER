<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Exceptions\ProtectedTemplateException;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;

it('blocks service delete for protected slugs', function () {
    config(['laravel-mailmanager.protected_slugs' => ['system-welcome']]);

    $template = EmailTemplate::factory()->create(['slug' => 'system-welcome']);
    $service = app(EmailTemplateService::class);

    expect($service->isProtected($template))->toBeTrue();

    $service->delete($template);
})->throws(ProtectedTemplateException::class);

it('allows delete for non-protected templates', function () {
    config(['laravel-mailmanager.protected_slugs' => ['system-welcome']]);

    $template = EmailTemplate::factory()->create(['slug' => 'custom-one']);
    app(EmailTemplateService::class)->delete($template);

    expect(EmailTemplate::query()->whereKey($template->id)->exists())->toBeFalse();
});

it('blocks admin destroy for protected slugs', function () {
    config([
        'laravel-mailmanager.ui.enabled' => true,
        'laravel-mailmanager.route.middleware' => ['web'],
        'laravel-mailmanager.protected_slugs' => ['locked-slug'],
    ]);

    $this->actingAsMailmanagerUser();
    $template = EmailTemplate::factory()->create(['slug' => 'locked-slug']);

    $this->delete(route('mailmanager.templates.destroy', $template))
        ->assertRedirect()
        ->assertSessionHasErrors('template');

    expect(EmailTemplate::withTrashed()->whereKey($template->id)->whereNull('deleted_at')->exists())->toBeTrue();
});

it('shows version history page for authorized users', function () {
    config([
        'laravel-mailmanager.ui.enabled' => true,
        'laravel-mailmanager.route.middleware' => ['web'],
    ]);

    $this->actingAsMailmanagerUser();
    $service = app(EmailTemplateService::class);
    $template = $service->create([
        'name' => 'Versioned',
        'slug' => 'versioned-ui',
        'subject' => 'Hello',
        'html_content' => '<p>v1</p>',
    ]);
    $service->update($template, [
        'subject' => 'Hello again',
        'html_content' => '<p>v2</p>',
    ]);

    $this->get(route('mailmanager.templates.versions', $template))
        ->assertOk()
        ->assertSee('Content versions')
        ->assertSee('Audit trail');
});
