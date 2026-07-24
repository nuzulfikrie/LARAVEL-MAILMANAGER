# Laravel Mailmanager — Product Roadmap

> Centrally managed, Unlayer-designed email templates with scalar & collection parameters, SMTP settings, and delivery logs — shipped as an idiomatic Laravel package.

**Source of truth:** `.idea/task.md`  
**Package:** `nuzul-fikrie-salam/laravel-mailmanager`  
**Stack:** PHP 8.3+, Laravel 12/13, Pest 4, Orchestra Testbench, Unlayer, Tailwind CSS

---

## Phase 0 — Foundation (Week 1–2)

*Prove package wiring, schema, and domain skeletons exist without admin UI.*

- [x] Replace placeholder config in `config/laravel-mailmanager.php` with real keys: table names, route prefix/middleware, Unlayer project ID env key, mailer name, cache TTL, strict parameter mode default, delivery kill-switch defaults
- [x] Migrations (publishable via existing tags):
  - [x] `email_templates` — name, slug (unique), description, subject, design_json, html_content, parameters (JSON schema), status (`draft|active|inactive|archived`), created_by, updated_by, timestamps, soft deletes
  - [x] `email_template_versions` — template_id, version number, subject, design_json, html_content, parameters snapshot, content_hash, created_by, timestamps
  - [x] `mailmanager_settings` — group, key, value, type, is_encrypted, timestamps; unique(group, key)
  - [x] `email_logs` — template_id, template_version_id, recipient, cc, bcc, rendered_subject, rendered_html, meta, status, provider_message_id, failure_reason, failure_type, queue_job_id, is_test, sent_at, timestamps
- [x] Remove placeholder migration and `laravel-mailmanager:placeholder` command
- [x] Eloquent models under `src/Models/`: `EmailTemplate`, `EmailTemplateVersion`, `Setting`, `EmailLog` with casts, relations, scopes (`active()`, `group()`)
- [x] Enums under `src/Enums/`: `TemplateStatus`, `EmailLogStatus`, `EmailFailureType`, `SettingType`, `EmptyCollectionBehavior`, `ParameterType`, `ColumnFormat`
- [x] Service provider: config merge, optional UI routes, publish tags; service bindings left for PR-02+
- [x] Factories under `database/factories/`
- [x] Pest: schema, model casts/relations, config/migration publish tags
- [x] Keep `composer test` / `composer analyse` / `composer lint:check` green

**Exit criteria:** Fresh install can migrate four real tables; models resolve; no placeholder domain left. ✅ (PR-01)

---

## Phase 1 — MVP Core Backend (Week 3–5)

*An app can define templates (API/services), render parameters, send via Laravel Mail, configure SMTP, and log delivery — without requiring Unlayer UI yet.*

### 1.1 Template domain (US-001–007) — PR-04 ✅

- [x] `EmailTemplateService`: create, update, duplicate, soft-delete, activate/deactivate/archive
- [x] Default status `draft`; Unlayer `design_json` + `html_content` separate
- [x] Content-hash version snapshots (`TemplateVersionService` + `ContentHasher`)
- [x] Domain exceptions for inactive send attempts
- [x] OwenIt audit trail on template changes
- [x] Pest: CRUD/status/versioning/audit

### 1.2 Parameter schema & renderer (US-008–013 revised) — PR-03 ✅

- [x] Parameter schema JSON types + collection columns
- [x] Scalar renderer with HTML-escape default
- [x] Collection renderer (`data-email-collection` / `data-email-row-template`)
- [x] Formats: plain, integer, decimal, currency, date (+ datetime/percentage stubs)
- [x] Empty collection behaviours + validation (required/strict/malformed)
- [x] Pest: golden fixtures (scalar + invoice table)

### 1.3 Laravel mail integration (US-013–016) — PR-06b/c ✅

- [x] `TemplateMailable` + `QueuedTemplateMailable` + `RawHtmlMailable`
- [x] `EmailTemplateService::send|queue|sendTest|render`
- [x] Version id captured at send; test flag on logs
- [x] Pest: Mail::fake send path, inactive/suppress failures

### 1.4 Settings + SMTP (US-017–021, US-027) — PR-02/05 ✅

- [x] `SettingsRepository` + `MailmanagerSettings` facade
- [x] Encrypted password, blank keep, cache invalidation
- [x] `MailConfigApplier` scheme/auto_tls/require_tls + purge
- [x] Delivery kill-switch + redirect_to meta
- [x] `mailmanager:smtp-test` + `SmtpProbeService`
- [x] Pest: encryption, TLS mapping

### 1.5 Email logs (US-022–023 basic) — PR-06a ✅

- [x] `EmailLogService` queued/sent/failed/suppressed
- [x] No parameter values stored by default
- [x] Retry eligibility helper on model

### MVP Definition of Done

> A Laravel app can store an Unlayer-exported template, define scalar + collection parameters, send (or queue) a personalized email through Laravel Mail using encrypted SMTP settings, and inspect basic delivery logs — all via package public APIs and migrations.

**Backend MVP (PR-01…PR-06c): complete.**

---

## Phase 2 — Admin UI + Unlayer (Week 6–8)

*Administrators manage templates and mail settings in-browser with Tailwind + Unlayer.*

### 2.1 Package UI shell — PR-07 ✅

- [x] Blade + committed CSS layout (`resources/views`, `public/css/mailmanager.css`)
- [x] Configurable route prefix/middleware; UI gated by `ui.enabled` middleware
- [x] Policies for templates/logs; gates for settings abilities
- [x] Workbench provider + demo seeder enabled in `testbench.yaml`

### 2.2 Template manager (US-001–006, US-016) — PR-07/08 ✅

- [x] List / create / edit / duplicate / delete / activate / deactivate
- [x] Unlayer bridge when `MAILMANAGER_UNLAYER_PROJECT_ID` set; HTML/JSON fallback otherwise
- [x] Preview via JSON endpoint + sample parameters
- [x] Parameter schema JSON editor + `{name}` insert helper
- [x] Send test email form

### 2.3 Settings & logs UI (US-017–020, US-022–023) — PR-09 ✅

- [x] SMTP settings form with masked password
- [x] Test SMTP connection action
- [x] Delivery enable/disable toggle + redirect_to
- [x] Email log index/filters/detail; retry when eligible

**Milestone:** Full MVP from `.idea/task.md` list (Unlayer create/edit through test send + logs) usable in workbench.

---

## Phase 3 — Hardening (Week 9–10)

*Security, authorization, audit, and package polish.*

- [x] Policies/gates for suggested permissions (`email-templates.*`, `email-settings.*`, `email-logs.*`) — US-025
- [x] Authorization matrix Pest tests + README gate table
- [x] HTML sanitization of generated content; strip scripts/event handlers/iframes/js URLs — US-026
- [x] Template change audit (OwenIt) + version history admin page — US-024
- [x] Soft-delete protections for `protected_slugs` — US-005
- [x] README usage examples; Boost skill updated
- [x] Arch tests + type coverage still at package bar (`composer test`)
- [x] CHANGELOG updated; first tagged release prep (do not publish autonomously)

**Milestone:** Production-ready package surface for installers who bring their own auth. ✅

---

## Phase 4 — Enhancement (v1.x / post-MVP)

*Expand beyond the first release.*

- [ ] Full column format suite (datetime, percentage, alignment, visibility)
- [ ] Rich empty-table UX and Unlayer custom tools packaging
- [ ] Retry UI + provider webhook ingestion for delivery status
- [ ] Multi-mailer / per-template mailer override
- [ ] Localization of admin UI beyond `lang/en`
- [ ] Optional Filament / Livewire Flux resource packs as separate optional paths
- [ ] Template import/export JSON packs

---

## MVP Scope

### In ✅

- Create/edit templates with Unlayer (JSON + HTML stored separately)
- Activate / deactivate templates
- Scalar `{param}` + collection dynamic tables
- Parameter validation before send (strict mode optional)
- `TemplateMailable` + `EmailTemplateService::send` + queue support
- SMTP via `settings` table with encrypted password
- Test emails + basic delivery logs
- Version snapshot at send time
- Package install path: config, migrations, views, routes, assets publish tags

### Out ❌ (v1.x+)

- Full marketing “campaign” builder / audience lists
- Built-in user authentication (host app provides auth)
- Multi-tenant isolation as a first-class package concern
- Complete delivery webhook provider matrix
- WYSIWYG alternatives to Unlayer
- Advanced A/B testing of templates
- Storing full parameter payloads in logs by default

---

## Dependency Map

```
Phase 0 (Foundation: schema, models, config)
  └── Phase 1 (MVP backend: templates, renderer, mail, settings, logs)
        └── Phase 2 (Admin UI + Unlayer + workbench)
              └── Phase 3 (Authz, sanitize, audit, release polish)
                    └── Phase 4 (Enhancements / v1.x)
```

Phase 1 subdomains can proceed in parallel after models exist: **renderer**, **settings**, **template CRUD** — then wire **send + logs**.

---

## Tech Debt / Future

- Placeholder service class `LaravelMailmanager` may collapse into facades for `EmailTemplateService` / `Settings`
- Unlayer license / project ID is host-app config — document clearly; never hardcode secrets
- Collection table markers depend on stable HTML conventions; document as package contract
- Settings table is generic — avoid becoming a full options framework beyond mail (and optional package groups)
- Consider extracting pure HTML renderer as testable unit without Laravel container

---

## Quick Reference

| Command | Purpose |
|---|---|
| `composer test` | Analyse + Pint check + type coverage + Pest |
| `composer test:unit` | Pest parallel |
| `composer analyse` | PHPStan / Larastan |
| `composer lint:check` | Pint dry-run |
| `composer build` | Workbench build |
| `composer serve` | Workbench HTTP server |
| `php artisan vendor:publish --tag=laravel-mailmanager` | Publish all package resources |
| `php artisan vendor:publish --tag=laravel-mailmanager-migrations` | Migrations only |
| `php artisan migrate` | Apply package tables after publish |
| `./vendor/bin/pest --filter=EmailTemplate` | Focus template tests |

## Traceability (MVP → stories)

| MVP item | Stories |
|---|---|
| Unlayer create/edit + JSON/HTML | US-001, US-002 |
| Activate/deactivate | US-006, US-007 |
| Parameters + detection | US-008–010 (revised), US-011–012 |
| Collection tables | US-010–013 (revised) |
| Send + Mailable + queue | US-013–015 |
| Test email | US-016 |
| SMTP + settings + encrypt | US-017–021, US-027 |
| Logs + version at send | US-022, US-028 |
| Preview / duplicate / delete | US-003–005 (UI Phase 2) |
| Authz / sanitize / audit | US-024–026 (Phase 3) |
