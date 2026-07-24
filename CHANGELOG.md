# Release Notes

## [Unreleased](https://github.com/nuzul-fikrie-salam/laravel-mailmanager/compare/v0.1.0...1.x)

### Added
- Full package config skeleton (tables, UI, Unlayer, parameters, mail, cache, sanitizer, permissions)
- Migrations for `email_templates`, `email_template_versions`, `mailmanager_settings`, `email_logs`, and OwenIt `audits`
- Domain enums, Eloquent models, and factories for templates, versions, settings, and logs
- `SettingsRepository` with encryption, group cache, and `MailmanagerSettings` facade
- Scalar + collection template renderer (DOM table expansion, formatters, sanitizer)
- `EmailTemplateService` CRUD/status workflow, content-hash versioning, send/queue/test-send
- `MailConfigApplier` (Laravel 12/13 `scheme` + `auto_tls`/`require_tls` + `MailManager::purge`)
- `TemplateMailable`, `QueuedTemplateMailable`, `RawHtmlMailable`, and `EmailLogService`
- `mailmanager:smtp-test` Artisan command
- OwenIt Laravel Auditing on `EmailTemplate` and `Setting` (password values excluded)
- Admin UI (Blade + CSS): templates, SMTP settings, email logs, Unlayer bridge JS
- Policies/gates for template and log abilities; `EnsureMailmanagerUiEnabled` middleware
- Workbench demo seeder + provider for `composer serve`
- Hardened HTML sanitizer (scripts, handlers, iframes, javascript: URLs)
- Protected template slugs; version history + OwenIt audit UI page
- Authorization matrix tests; full README and Boost skill

### Deprecated
- `LaravelMailmanager` facade/class — use `EmailTemplate` and `MailmanagerSettings`

### Removed
- Placeholder migration, Artisan command, and config keys

## [v0.1.0](https://github.com/nuzul-fikrie-salam/laravel-mailmanager/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
