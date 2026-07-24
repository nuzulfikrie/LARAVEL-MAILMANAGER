<div align="center">
    <h1>Laravel Mailmanager</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/nuzul-fikrie-salam/laravel-mailmanager"><img src="https://img.shields.io/packagist/v/nuzul-fikrie-salam/laravel-mailmanager.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/nuzul-fikrie-salam/laravel-mailmanager"><img src="https://img.shields.io/packagist/php-v/nuzul-fikrie-salam/laravel-mailmanager.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://badge.laravel.cloud/badge/nuzul-fikrie-salam/laravel-mailmanager?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/nuzul-fikrie-salam/laravel-mailmanager/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/nuzul-fikrie-salam/laravel-mailmanager/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/nuzul-fikrie-salam/laravel-mailmanager"><img src="https://img.shields.io/packagist/dt/nuzul-fikrie-salam/laravel-mailmanager.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Centrally managed email templates for Laravel with Unlayer design JSON + HTML, scalar and collection parameters, encrypted SMTP settings, delivery logs, and an optional admin UI.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- [owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing) (required dependency for template/settings audit trail)

## Installation

```bash
composer require nuzul-fikrie-salam/laravel-mailmanager
```

Publish and migrate:

```bash
php artisan vendor:publish --tag="laravel-mailmanager-config"
php artisan vendor:publish --tag="laravel-mailmanager-migrations"
php artisan vendor:publish --tag="laravel-mailmanager-assets"
php artisan migrate
```

Or publish everything:

```bash
php artisan vendor:publish --tag="laravel-mailmanager"
```

## Quick start (programmatic)

```php
use NuzulFikrieCoder\LaravelMailmanager\Facades\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Facades\MailmanagerSettings;

MailmanagerSettings::putMany('mail', [
    'mailer' => 'smtp',
    'host' => 'smtp.example.com',
    'port' => 587,
    'encryption' => 'tls', // none | tls | ssl
    'username' => 'user@example.com',
    'password' => 'secret', // encrypted at rest
    'from_address' => 'noreply@example.com',
    'from_name' => 'My App',
    'delivery_enabled' => true,
]);

$template = EmailTemplate::create([
    'name' => 'User Welcome',
    'slug' => 'user-welcome',
    'subject' => 'Welcome {name}',
    'html_content' => '<p>Hello {name}</p>',
    'parameters' => [
        'name' => ['type' => 'string', 'required' => true],
    ],
]);

EmailTemplate::activate($template);

EmailTemplate::send('user-welcome', $user->email, [
    'name' => $user->name,
]);
```

### Queue & test send

```php
EmailTemplate::queue('user-welcome', $user->email, ['name' => $user->name]);
EmailTemplate::sendTest('user-welcome', 'you@example.com', ['name' => 'Ali']);
```

### Collection tables

Mark tables in HTML with `data-email-collection` and a row template:

```html
<table data-email-collection="invoice_items">
  <tbody>
    <tr data-email-row-template>
      <td>{description}</td>
      <td>{total}</td>
    </tr>
  </tbody>
</table>
```

Pass arrays for collection parameters when sending.

## Admin UI

Disabled by default (`MAILMANAGER_UI_ENABLED=false`). Enable after defining gates:

```env
MAILMANAGER_UI_ENABLED=true
MAILMANAGER_UNLAYER_PROJECT_ID=   # optional; without it, edit HTML/JSON textareas
```

```php
// AppServiceProvider::boot()
use Illuminate\Support\Facades\Gate;

Gate::define('email-templates.view', fn ($user) => $user->isAdmin());
Gate::define('email-templates.create', fn ($user) => $user->isAdmin());
Gate::define('email-templates.update', fn ($user) => $user->isAdmin());
Gate::define('email-templates.delete', fn ($user) => $user->isAdmin());
Gate::define('email-templates.activate', fn ($user) => $user->isAdmin());
Gate::define('email-templates.send-test', fn ($user) => $user->isAdmin());
Gate::define('email-settings.view', fn ($user) => $user->isAdmin());
Gate::define('email-settings.update', fn ($user) => $user->isAdmin()); // SMTP password
Gate::define('email-logs.view', fn ($user) => $user->isAdmin());
Gate::define('email-logs.retry', fn ($user) => $user->isAdmin());
```

Then open `/mailmanager` (configurable via `laravel-mailmanager.route.prefix`).

| Ability | Default gate name | Purpose |
|---|---|---|
| templates.view | `email-templates.view` | List / preview / version history |
| templates.create | `email-templates.create` | Create / duplicate |
| templates.update | `email-templates.update` | Edit design/schema |
| templates.delete | `email-templates.delete` | Soft delete |
| templates.activate | `email-templates.activate` | Activate / deactivate |
| templates.send_test | `email-templates.send-test` | Test send |
| settings.view | `email-settings.view` | View SMTP form (password masked) |
| settings.update | `email-settings.update` | Change SMTP / delivery flags |
| logs.view | `email-logs.view` | View logs |
| logs.retry | `email-logs.retry` | Retry eligible failures only |

SMTP credentials require **`email-settings.update`**. Viewers with only `settings.view` never receive the plaintext password.

## Configuration notes

Key file: `config/laravel-mailmanager.php` after publish.

| Area | Notes |
|---|---|
| Tables | Default settings table is `mailmanager_settings` (override via `tables.settings`) |
| SMTP apply | Runtime mailer uses Laravel 12/13 `scheme` + Symfony `auto_tls` / `require_tls`, then `MailManager::purge()` |
| Multi-node | Use a shared cache store for settings (`cache.settings_store`) so kill-switch/password rotates propagate |
| Protected templates | `protected_slugs` cannot be soft-deleted |
| Sanitizer | Strips scripts, event handlers, iframes, and `javascript:` URLs from rendered HTML |
| Audit | OwenIt audits `EmailTemplate` and `Setting` (setting **values** excluded) |

### SMTP probe

```bash
php artisan mailmanager:smtp-test you@example.com
```

## Facades

| Facade | Prefer for |
|---|---|
| `EmailTemplate` | CRUD, send, queue, render |
| `MailmanagerSettings` | Settings group get/put |
| `LaravelMailmanager` | **Deprecated** — empty scaffold left for compatibility |

## Publish tags

| Tag | Resource |
|---|---|
| `laravel-mailmanager` | All |
| `laravel-mailmanager-config` | Config |
| `laravel-mailmanager-migrations` | Migrations (includes `audits`) |
| `laravel-mailmanager-views` | Blade views |
| `laravel-mailmanager-lang` | Translations |
| `laravel-mailmanager-assets` | CSS / Unlayer bridge JS |

## Testing (package)

```bash
composer test        # analyse + lint + type coverage + pest
composer test:unit   # pest only
composer analyse     # phpstan
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes. Design notes live in [docs/DESIGN.md](docs/DESIGN.md); roadmap in [ROADMAP.md](ROADMAP.md).

## Contributing

Thank you for considering contributing to Laravel Mailmanager! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [NUZUL FIKRIE SALAM](https://github.com/nuzul-fikrie-salam)
- [All Contributors](../../contributors)

## License

Laravel Mailmanager is open-sourced software licensed under the [MIT license](LICENSE.md).
