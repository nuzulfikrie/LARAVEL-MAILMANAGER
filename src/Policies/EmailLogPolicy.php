<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;

class EmailLogPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'logs.view');
    }

    public function view(Authenticatable $user, EmailLog $log): bool
    {
        return $this->allows($user, 'logs.view');
    }

    public function retry(Authenticatable $user, EmailLog $log): bool
    {
        return $this->allows($user, 'logs.retry');
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
