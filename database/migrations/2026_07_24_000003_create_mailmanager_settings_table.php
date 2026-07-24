<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('laravel-mailmanager.tables.settings', 'mailmanager_settings');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('group', 64);
            $table->string('key', 128);
            $table->text('value')->nullable();
            $table->string('type', 32)->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        $tableName = config('laravel-mailmanager.tables.settings', 'mailmanager_settings');

        Schema::dropIfExists($tableName);
    }
};
