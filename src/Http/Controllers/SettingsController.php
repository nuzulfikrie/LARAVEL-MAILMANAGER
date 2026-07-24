<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use NuzulFikrieCoder\LaravelMailmanager\Http\Requests\UpdateMailSettingsRequest;
use NuzulFikrieCoder\LaravelMailmanager\Services\MailConfigApplier;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;

class SettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly MailConfigApplier $mailConfig,
    ) {}

    public function edit(): View
    {
        Gate::authorize((string) config('laravel-mailmanager.permissions.settings.view'));

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::settings.mail';

        return view($viewName, [
            'settings' => $this->settings->groupForDisplay('mail'),
        ]);
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        Gate::authorize((string) config('laravel-mailmanager.permissions.settings.update'));

        $this->settings->putMany('mail', $request->settingsData());
        $this->mailConfig->apply();

        return redirect()
            ->route(config('laravel-mailmanager.route.name').'settings.mail.edit')
            ->with('status', 'Mail settings saved.');
    }
}
