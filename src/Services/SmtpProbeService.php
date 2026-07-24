<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Services;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\SmtpConfigurationException;
use NuzulFikrieCoder\LaravelMailmanager\Support\Mask;
use Throwable;

final class SmtpProbeService
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly MailConfigApplier $applier,
    ) {}

    public function sendTest(string $to): void
    {
        $this->applier->apply();

        $mailerName = (string) config('laravel-mailmanager.mail.mailer_name', 'mailmanager');
        $mail = $this->settings->group('mail');
        $secrets = [
            isset($mail['password']) ? (string) $mail['password'] : null,
            isset($mail['username']) ? (string) $mail['username'] : null,
        ];

        try {
            Mail::mailer($mailerName)->raw(
                'Laravel Mailmanager SMTP probe message.',
                function (Message $message) use ($to, $mail): void {
                    $message->to($to)->subject('Mailmanager SMTP test');

                    if (! empty($mail['from_address'])) {
                        $message->from(
                            (string) $mail['from_address'],
                            (string) ($mail['from_name'] ?? ''),
                        );
                    }
                },
            );
        } catch (Throwable $e) {
            throw new SmtpConfigurationException(
                Mask::secrets($e->getMessage(), $secrets),
                0,
                $e,
            );
        }
    }
}
