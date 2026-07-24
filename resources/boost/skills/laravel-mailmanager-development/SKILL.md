---
name: laravel-mailmanager-development
description: >
  Configure and apply the Laravel Mailmanager package in Laravel applications:
  install, publish, gates, SMTP settings, templates, send/queue, admin UI, Unlayer.
license: MIT
metadata:
  author: NUZUL FIKRIE SALAM
---

# Laravel Mailmanager

Use this skill when a Laravel application needs to integrate `nuzul-fikrie-salam/laravel-mailmanager`.

## Primary Goal

Apply the package public API in the smallest correct way: migrate tables, configure SMTP settings, define gates if using the admin UI, and send templated mail via `EmailTemplate`.

## Workflow

### 1. Install and publish

```bash
composer require nuzul-fikrie-salam/laravel-mailmanager
php artisan vendor:publish --tag=laravel-mailmanager-config
php artisan vendor:publish --tag=laravel-mailmanager-migrations
php artisan vendor:publish --tag=laravel-mailmanager-assets
php artisan migrate
```

### 2. Configure SMTP via settings repository

```php
use NuzulFikrieCoder\LaravelMailmanager\Facades\MailmanagerSettings;

MailmanagerSettings::putMany('mail', [
    'mailer' => 'smtp',
    'host' => env('MAIL_HOST'),
    'port' => 587,
    'encryption' => 'tls', // none|tls|ssl — mapped to Laravel scheme + auto_tls
    'username' => env('MAIL_USERNAME'),
    'password' => env('MAIL_PASSWORD'), // encrypted; blank keep on later updates
    'from_address' => env('MAIL_FROM_ADDRESS'),
    'from_name' => env('MAIL_FROM_NAME'),
    'delivery_enabled' => true,
]);
```

Probe: `php artisan mailmanager:smtp-test admin@example.com`

### 3. Create / activate / send templates

```php
use NuzulFikrieCoder\LaravelMailmanager\Facades\EmailTemplate;

$t = EmailTemplate::create([
    'name' => 'Welcome',
    'slug' => 'user-welcome',
    'subject' => 'Hello {name}',
    'html_content' => '<p>Hello {name}</p>',
    'parameters' => ['name' => ['type' => 'string', 'required' => true]],
]);
EmailTemplate::activate($t);

EmailTemplate::send('user-welcome', $user->email, ['name' => $user->name]);
// EmailTemplate::queue(...); EmailTemplate::sendTest(...);
```

Collection tables use `data-email-collection` + `data-email-row-template` markers in HTML.

### 4. Optional admin UI

```env
MAILMANAGER_UI_ENABLED=true
MAILMANAGER_UNLAYER_PROJECT_ID=   # optional
```

Define gates for abilities under `config('laravel-mailmanager.permissions')` (e.g. `email-templates.view`, `email-settings.update`). UI is 404 until enabled; missing gates deny access.

### 5. Multi-node ops

- Shared cache store for settings (`laravel-mailmanager.cache.settings_store`)
- Mailer uses `scheme` + purge — no manual `config:cache` rebuild after settings save

## Rules, References, and Templates

- Config: `config/laravel-mailmanager.php` keys `tables`, `mail`, `ui`, `permissions`, `protected_slugs`, `sanitizer`
- Facades: `EmailTemplate`, `MailmanagerSettings` (prefer over deprecated `LaravelMailmanager`)
- Publish tags: `laravel-mailmanager-*` (config, migrations, views, lang, assets)
- README: package root `README.md`
- Design: `docs/DESIGN.md` for deeper contracts

## Examples

- **Host app welcome email:** create slug `user-welcome`, activate, call `EmailTemplate::send` from registration listener.
- **Invoice email:** collection param `invoice_items` + HTML table markers; pass nested arrays at send time.
- **Admin only:** enable UI, map Spatie roles to the ten gate ability strings, publish assets.

## Anti-patterns

- Do not use the deprecated empty `LaravelMailmanager` facade for new code.
- Do not log or return decrypted SMTP passwords to the frontend (`groupForDisplay` masks them).
- Do not store full parameter values in email logs by default.
- Do not enable UI without defining permission gates (secure-by-default deny).
- Do not treat package internals (`src/Rendering/*` details) as stable public API beyond documented facades/services.
