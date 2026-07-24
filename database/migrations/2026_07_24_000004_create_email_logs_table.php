<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('laravel-mailmanager.tables.logs', 'email_logs');
        $templatesTable = config('laravel-mailmanager.tables.templates', 'email_templates');
        $versionsTable = config('laravel-mailmanager.tables.template_versions', 'email_template_versions');

        Schema::create($tableName, function (Blueprint $table) use ($templatesTable, $versionsTable): void {
            $table->id();
            $table->foreignId('email_template_id')
                ->nullable()
                ->constrained($templatesTable)
                ->nullOnDelete();
            $table->foreignId('email_template_version_id')
                ->nullable()
                ->constrained($versionsTable)
                ->nullOnDelete();
            $table->string('recipient', 320);
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('rendered_subject');
            $table->longText('rendered_html')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 32);
            $table->string('provider_message_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('failure_type', 64)->nullable();
            $table->string('queue_job_id')->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['email_template_id', 'created_at']);
            $table->index('recipient');
            $table->index('is_test');
            $table->index('failure_type');
        });
    }

    public function down(): void
    {
        $tableName = config('laravel-mailmanager.tables.logs', 'email_logs');

        Schema::dropIfExists($tableName);
    }
};
