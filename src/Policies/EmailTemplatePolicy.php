<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;

class EmailTemplatePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'templates.view');
    }

    public function view(Authenticatable $user, EmailTemplate $template): bool
    {
        return $this->allows($user, 'templates.view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user, 'templates.create');
    }

    public function update(Authenticatable $user, EmailTemplate $template): bool
    {
        return $this->allows($user, 'templates.update');
    }

    public function delete(Authenticatable $user, EmailTemplate $template): bool
    {
        return $this->allows($user, 'templates.delete');
    }

    public function activate(Authenticatable $user, EmailTemplate $template): bool
    {
        return $this->allows($user, 'templates.activate');
    }

    public function sendTest(Authenticatable $user, EmailTemplate $template): bool
    {
        return $this->allows($user, 'templates.send_test');
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
