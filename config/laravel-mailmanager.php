<?php

declare(strict_types=1);

return [

    'name' => 'Laravel Mailmanager',

    'tables' => [
        'templates' => 'email_templates',
        'template_versions' => 'email_template_versions',
        'settings' => 'mailmanager_settings',
        'logs' => 'email_logs',
    ],

    'route' => [
        'prefix' => 'mailmanager',
        'name' => 'mailmanager.',
        'middleware' => ['web', 'auth'],
    ],

    'ui' => [
        // Secure-by-default: off until the host enables after defining permission gates.
        'enabled' => (bool) env('MAILMANAGER_UI_ENABLED', false),
        'layout' => 'laravel-mailmanager::layouts.admin',
        'brand' => 'Mailmanager',
    ],

    'unlayer' => [
        'project_id' => env('MAILMANAGER_UNLAYER_PROJECT_ID'),
        'display_mode' => 'email',
        'locale' => 'en-US',
        'cdn' => 'https://editor.unlayer.com/embed.js',
    ],

    'parameters' => [
        'strict' => (bool) env('MAILMANAGER_STRICT_PARAMETERS', false),
        'placeholder_pattern' => '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
        'html_escape_default' => true,
        'raw_opt_in_key' => 'allow_raw_html',
        'reserved_names' => ['app_name', 'app_url', 'year'],
        'fail_on_unresolved_subject' => true,
        'fail_on_unresolved_body' => false,
    ],

    'mail' => [
        'mailer_name' => 'mailmanager',
        'apply_on_boot' => false,
        'set_as_default' => false,
        'apply_global_from' => false,
        'delivery_enabled_default' => true,
        'allow_test_when_disabled' => true,
        'suppress_throws' => true,
        'redirect_to' => env('MAILMANAGER_REDIRECT_TO'),
        'queue_by_default' => false,
        'store_rendered_html_in_logs' => false,
        'log_parameter_keys' => false,
        'reject_if_template_inactive_on_worker' => false,
    ],

    'cache' => [
        'settings_store' => env('MAILMANAGER_CACHE_STORE'),
        'settings_ttl' => 3600,
        'settings_key' => 'laravel-mailmanager.settings',
    ],

    'sanitizer' => [
        'enabled' => true,
        'strip_scripts' => true,
        'strip_event_handlers' => true,
    ],

    // Gate ability names the host should define. Nested keys avoid Laravel config
    // collision with dotted array access (e.g. permissions.templates.view).
    'permissions' => [
        'templates' => [
            'view' => 'email-templates.view',
            'create' => 'email-templates.create',
            'update' => 'email-templates.update',
            'delete' => 'email-templates.delete',
            'activate' => 'email-templates.activate',
            'send_test' => 'email-templates.send-test',
        ],
        'settings' => [
            'view' => 'email-settings.view',
            'update' => 'email-settings.update',
        ],
        'logs' => [
            'view' => 'email-logs.view',
            'retry' => 'email-logs.retry',
        ],
    ],

    'protected_slugs' => [
        // Templates that cannot be soft-deleted (Phase 3 enforcement).
    ],

];
