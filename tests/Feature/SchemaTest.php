<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the email_templates table with expected columns', function () {
    $table = config('laravel-mailmanager.tables.templates');

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, [
            'id',
            'name',
            'slug',
            'description',
            'subject',
            'design_json',
            'html_content',
            'parameters',
            'status',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ]))->toBeTrue();
});

it('creates the email_template_versions table with content_hash', function () {
    $table = config('laravel-mailmanager.tables.template_versions');

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, [
            'id',
            'email_template_id',
            'version',
            'content_hash',
            'subject',
            'design_json',
            'html_content',
            'parameters',
            'created_by',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates the mailmanager_settings table', function () {
    $table = config('laravel-mailmanager.tables.settings');

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, [
            'id',
            'group',
            'key',
            'value',
            'type',
            'is_encrypted',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates the email_logs table with rendered_html and meta', function () {
    $table = config('laravel-mailmanager.tables.logs');

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, [
            'id',
            'email_template_id',
            'email_template_version_id',
            'recipient',
            'cc',
            'bcc',
            'rendered_subject',
            'rendered_html',
            'meta',
            'status',
            'provider_message_id',
            'failure_reason',
            'failure_type',
            'queue_job_id',
            'is_test',
            'sent_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});
