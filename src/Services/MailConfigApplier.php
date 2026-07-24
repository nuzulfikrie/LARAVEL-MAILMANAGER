<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Config;

final class MailConfigApplier
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly Application $app,
    ) {}

    public function apply(): void
    {
        $mail = $this->settings->group('mail');
        $mailerName = (string) config('laravel-mailmanager.mail.mailer_name', 'mailmanager');
        $tls = $this->mapEncryptionToMailerTls(isset($mail['encryption']) ? (string) $mail['encryption'] : 'tls');

        $payload = [
            'transport' => $mail['mailer'] ?? 'smtp',
            'scheme' => $tls['scheme'],
            'host' => $mail['host'] ?? null,
            'port' => (int) ($mail['port'] ?? 587),
            'username' => $mail['username'] ?? null,
            'password' => $mail['password'] ?? null,
            'timeout' => $mail['timeout'] ?? 30,
            'url' => null,
            'local_domain' => $mail['local_domain'] ?? null,
            'auto_tls' => $tls['auto_tls'],
            'require_tls' => $tls['require_tls'],
        ];

        $filtered = array_filter(
            $payload,
            fn (mixed $v, string|int $k): bool => $v !== null || in_array((string) $k, ['password', 'username'], true),
            ARRAY_FILTER_USE_BOTH,
        );

        Config::set("mail.mailers.{$mailerName}", $filtered);

        if ((bool) config('laravel-mailmanager.mail.set_as_default', false)) {
            Config::set('mail.default', $mailerName);
        }

        if ((bool) config('laravel-mailmanager.mail.apply_global_from', false) && ! empty($mail['from_address'])) {
            Config::set('mail.from.address', $mail['from_address']);
            Config::set('mail.from.name', $mail['from_name'] ?? '');
        }

        /** @var MailManager $manager */
        $manager = $this->app->make(MailManager::class);
        $manager->purge($mailerName);

        if ((bool) config('laravel-mailmanager.mail.set_as_default', false)) {
            $manager->purge();
        }
    }

    public function isDeliveryEnabled(): bool
    {
        $value = $this->settings->get(
            'mail',
            'delivery_enabled',
            config('laravel-mailmanager.mail.delivery_enabled_default', true),
        );

        return (bool) $value;
    }

    public function redirectTo(): ?string
    {
        $value = $this->settings->get('mail', 'redirect_to', config('laravel-mailmanager.mail.redirect_to'));

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array{scheme: string, auto_tls: bool|null, require_tls: bool|null}
     */
    public function mapEncryptionToMailerTls(?string $encryption): array
    {
        return match (strtolower((string) $encryption)) {
            'ssl', 'smtps' => [
                'scheme' => 'smtps',
                'auto_tls' => null,
                'require_tls' => null,
            ],
            'none', '', 'null' => [
                'scheme' => 'smtp',
                'auto_tls' => false,
                'require_tls' => false,
            ],
            'tls', 'starttls' => [
                'scheme' => 'smtp',
                'auto_tls' => true,
                'require_tls' => true,
            ],
            default => [
                'scheme' => 'smtp',
                'auto_tls' => true,
                'require_tls' => true,
            ],
        };
    }
}
