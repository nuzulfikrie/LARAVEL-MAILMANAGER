<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Settings are not bound to a model instance; controllers call authorize('view', 'mail-settings').
 */
class MailSettingsPolicy
{
    public function view(Authenticatable $user): bool
    {
        return $this->allows($user, 'settings.view');
    }

    public function update(Authenticatable $user): bool
    {
        return $this->allows($user, 'settings.update');
    }

    private function allows(Authenticatable $user, string $permissionKey): bool
    {
        $ability = config("laravel-mailmanager.permissions.{$permissionKey}");

        if (! is_string($ability) || $ability === '') {
            return false;
        }

        return Gate::forUser($user)->check($ability);
    }
}
