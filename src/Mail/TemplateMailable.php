<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use JsonException;
use NuzulFikrieCoder\LaravelMailmanager\DTOs\RenderedEmail;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\CannotSendInactiveTemplateException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\TemplateNotFoundException;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\TemplateRenderer;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailLogService;
use NuzulFikrieCoder\LaravelMailmanager\Services\MailConfigApplier;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;
use NuzulFikrieCoder\LaravelMailmanager\Services\TemplateVersionService;
use Throwable;

class TemplateMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    private ?RenderedEmail $rendered = null;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $templateKey,
        public array $parameters = [],
        public ?int $versionId = null,
        public bool $isTest = false,
        public ?int $emailLogId = null,
        public ?string $mailerName = null,
        public ?bool $strict = null,
    ) {
        $this->assertParametersSerializable($parameters);

        if ($this->mailerName !== null) {
            $this->mailer($this->mailerName);
        }
    }

    public function envelope(): Envelope
    {
        $settings = app(SettingsRepository::class)->group('mail');
        $rendered = $this->renderNow();

        $from = null;

        if (! empty($settings['from_address'])) {
            $from = new Address(
                (string) $settings['from_address'],
                (string) ($settings['from_name'] ?? ''),
            );
        }

        $replyTo = [];

        if (! empty($settings['reply_to'])) {
            $replyTo = [new Address((string) $settings['reply_to'])];
        }

        return new Envelope(
            from: $from,
            replyTo: $replyTo,
            subject: $rendered->subject,
        );
    }

    public function content(): Content
    {
        $rendered = $this->renderNow();

        return new Content(htmlString: $rendered->html);
    }

    public function renderNow(): RenderedEmail
    {
        if ($this->rendered !== null) {
            return $this->rendered;
        }

        app(MailConfigApplier::class)->apply();

        try {
            $version = $this->resolveVersion();
            $this->rendered = app(TemplateRenderer::class)->render($version, $this->parameters, $this->strict);
        } catch (Throwable $e) {
            $this->failLog(EmailFailureType::Validation, $e->getMessage());

            throw $e;
        }

        return $this->rendered;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function assertParametersSerializable(array $parameters): void
    {
        try {
            json_encode($parameters, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Template parameters must be JSON-serializable.', 0, $e);
        }
    }

    protected function resolveVersion(): EmailTemplateVersion
    {
        if ($this->versionId !== null) {
            $version = EmailTemplateVersion::query()->find($this->versionId);

            if ($version === null) {
                throw new TemplateNotFoundException("Template version [{$this->versionId}] was not found.");
            }

            if ((bool) config('laravel-mailmanager.mail.reject_if_template_inactive_on_worker', false)) {
                $template = $version->template;

                if ($template === null || ! $template->isSendable()) {
                    throw new CannotSendInactiveTemplateException(
                        "Template [{$this->templateKey}] is not active.",
                    );
                }
            }

            return $version;
        }

        $template = EmailTemplate::query()
            ->where('slug', $this->templateKey)
            ->sendable()
            ->first();

        if ($template === null) {
            throw new TemplateNotFoundException("Active template [{$this->templateKey}] was not found.");
        }

        $version = app(TemplateVersionService::class)->ensureCurrentVersion($template);
        $this->versionId = $version->id;

        return $version;
    }

    protected function failLog(EmailFailureType $type, string $reason): void
    {
        if ($this->emailLogId === null) {
            return;
        }

        $log = EmailLog::query()->find($this->emailLogId);

        if ($log === null) {
            return;
        }

        app(EmailLogService::class)->markFailed($log, $reason, $type);
    }
}
