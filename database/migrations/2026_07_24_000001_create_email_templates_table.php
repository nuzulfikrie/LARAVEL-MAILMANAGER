<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('laravel-mailmanager.tables.templates', 'email_templates');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('subject', 998);
            $table->json('design_json')->nullable();
            $table->longText('html_content');
            $table->json('parameters')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('name');
            $table->unique('slug');
            $table->index('status');
            $table->index('deleted_at');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    public function down(): void
    {
        $tableName = config('laravel-mailmanager.tables.templates', 'email_templates');

        Schema::dropIfExists($tableName);
    }
};
