<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use NuzulFikrieCoder\LaravelMailmanager\Http\Controllers\EmailLogController;
use NuzulFikrieCoder\LaravelMailmanager\Http\Controllers\SettingsController;
use NuzulFikrieCoder\LaravelMailmanager\Http\Controllers\SmtpTestController;
use NuzulFikrieCoder\LaravelMailmanager\Http\Controllers\TemplateController;
use NuzulFikrieCoder\LaravelMailmanager\Http\Controllers\TemplatePreviewController;
use NuzulFikrieCoder\LaravelMailmanager\Http\Controllers\TemplateTestSendController;
use NuzulFikrieCoder\LaravelMailmanager\Http\Middleware\EnsureMailmanagerUiEnabled;

$middleware = array_values(array_filter([
    EnsureMailmanagerUiEnabled::class,
    ...((array) config('laravel-mailmanager.route.middleware', ['web', 'auth'])),
]));

Route::middleware($middleware)
    ->prefix((string) config('laravel-mailmanager.route.prefix', 'mailmanager'))
    ->name((string) config('laravel-mailmanager.route.name', 'mailmanager.'))
    ->group(function (): void {
        Route::get('/', [TemplateController::class, 'index'])->name('templates.index');
        Route::resource('templates', TemplateController::class)->except(['show']);

        Route::post('templates/{template}/duplicate', [TemplateController::class, 'duplicate'])
            ->name('templates.duplicate');
        Route::post('templates/{template}/activate', [TemplateController::class, 'activate'])
            ->name('templates.activate');
        Route::post('templates/{template}/deactivate', [TemplateController::class, 'deactivate'])
            ->name('templates.deactivate');
        Route::get('templates/{template}/versions', [TemplateController::class, 'versions'])
            ->name('templates.versions');
        Route::post('templates/{template}/preview', TemplatePreviewController::class)
            ->name('templates.preview');
        Route::post('templates/{template}/send-test', TemplateTestSendController::class)
            ->name('templates.send-test');

        Route::get('settings/mail', [SettingsController::class, 'edit'])->name('settings.mail.edit');
        Route::put('settings/mail', [SettingsController::class, 'update'])->name('settings.mail.update');
        Route::post('settings/mail/test', SmtpTestController::class)->name('settings.mail.test');

        Route::get('logs', [EmailLogController::class, 'index'])->name('logs.index');
        Route::get('logs/{log}', [EmailLogController::class, 'show'])->name('logs.show');
        Route::post('logs/{log}/retry', [EmailLogController::class, 'retry'])->name('logs.retry');
    });
