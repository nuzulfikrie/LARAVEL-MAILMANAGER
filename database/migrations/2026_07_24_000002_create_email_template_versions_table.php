<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('laravel-mailmanager.tables.template_versions', 'email_template_versions');
        $templatesTable = config('laravel-mailmanager.tables.templates', 'email_templates');

        Schema::create($tableName, function (Blueprint $table) use ($templatesTable): void {
            $table->id();
            $table->foreignId('email_template_id')
                ->constrained($templatesTable)
                ->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('content_hash', 64);
            $table->string('subject', 998);
            $table->json('design_json')->nullable();
            $table->longText('html_content');
            $table->json('parameters')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['email_template_id', 'version']);
            $table->index('content_hash');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        $tableName = config('laravel-mailmanager.tables.template_versions', 'email_template_versions');

        Schema::dropIfExists($tableName);
    }
};
