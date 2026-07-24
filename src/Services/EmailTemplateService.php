<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use NuzulFikrieCoder\LaravelMailmanager\DTOs\RenderedEmail;
use NuzulFikrieCoder\LaravelMailmanager\DTOs\SendOptions;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Enums\TemplateStatus;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\CannotSendInactiveTemplateException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\DeliveryDisabledException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\ProtectedTemplateException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\TemplateNotFoundException;
use NuzulFikrieCoder\LaravelMailmanager\Mail\QueuedTemplateMailable;
use NuzulFikrieCoder\LaravelMailmanager\Mail\TemplateMailable;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\TemplateRenderer;
use Throwable;

final class EmailTemplateService
{
    public function __construct(
        private readonly TemplateVersionService $versions,
        private readonly TemplateRenderer $renderer,
        private readonly MailConfigApplier $mailConfig,
        private readonly EmailLogService $logs,
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId = null): EmailTemplate
    {
        $name = (string) ($data['name'] ?? '');
        $slug = (string) ($data['slug'] ?? Str::slug($name));

        $template = EmailTemplate::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'subject' => (string) ($data['subject'] ?? ''),
            'design_json' => $data['design_json'] ?? [],
            'html_content' => (string) ($data['html_content'] ?? ''),
            'parameters' => $data['parameters'] ?? [],
            'status' => TemplateStatus::Draft,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        if ($template->html_content !== '' || $template->subject !== '') {
            $this->versions->ensureCurrentVersion($template, $actorId);
        }

        return $template->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmailTemplate $template, array $data, ?int $actorId = null): EmailTemplate
    {
        unset($data['status']);

        $template->fill($data);
        $template->updated_by = $actorId;
        $template->save();

        $this->versions->snapshotIfContentChanged($template->refresh(), $actorId);

        return $template->refresh();
    }

    public function duplicate(EmailTemplate $template, ?string $name = null): EmailTemplate
    {
        $newName = $name ?? ($template->name.' (copy)');
        $baseSlug = Str::slug($newName);

        return $this->create([
            'name' => $newName,
            'slug' => $baseSlug.'-'.Str::lower(Str::random(6)),
            'description' => $template->description,
            'subject' => $template->subject,
            'design_json' => $template->design_json,
            'html_content' => $template->html_content,
            'parameters' => $template->parameters,
        ]);
    }

    public function activate(EmailTemplate $template, ?int $actorId = null): EmailTemplate
    {
        if (! in_array($template->status, [TemplateStatus::Draft, TemplateStatus::Inactive], true)) {
            // Allow re-activate from active as no-op
            if ($template->status === TemplateStatus::Active) {
                return $template;
            }
        }

        $template->forceFill([
            'status' => TemplateStatus::Active,
            'updated_by' => $actorId,
        ])->save();

        return $template->refresh();
    }

    public function deactivate(EmailTemplate $template, ?int $actorId = null): EmailTemplate
    {
        $template->forceFill([
            'status' => TemplateStatus::Inactive,
            'updated_by' => $actorId,
        ])->save();

        return $template->refresh();
    }

    public function archive(EmailTemplate $template, ?int $actorId = null): EmailTemplate
    {
        $template->forceFill([
            'status' => TemplateStatus::Archived,
            'updated_by' => $actorId,
        ])->save();

        return $template->refresh();
    }

    public function delete(EmailTemplate $template): void
    {
        $protected = config('laravel-mailmanager.protected_slugs', []);

        if (is_array($protected) && in_array($template->slug, $protected, true)) {
            throw new ProtectedTemplateException(
                "Template [{$template->slug}] is protected and cannot be deleted.",
            );
        }

        $template->delete();
    }

    public function isProtected(EmailTemplate $template): bool
    {
        $protected = config('laravel-mailmanager.protected_slugs', []);

        return is_array($protected) && in_array($template->slug, $protected, true);
    }

    /**
     * @param  string|list<string>  $to
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>|SendOptions  $options
     */
    public function send(
        string $template,
        string|array $to,
        array $parameters = [],
        array|SendOptions $options = [],
    ): EmailLog {
        $opts = SendOptions::from($options);

        if ($opts->queue || (bool) config('laravel-mailmanager.mail.queue_by_default', false)) {
            return $this->queue($template, $to, $parameters, $opts);
        }

        return $this->dispatch($template, $to, $parameters, $opts, queue: false);
    }

    /**
     * @param  string|list<string>  $to
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>|SendOptions  $options
     */
    public function queue(
        string $template,
        string|array $to,
        array $parameters = [],
        array|SendOptions $options = [],
    ): EmailLog {
        $opts = SendOptions::from($options);

        return $this->dispatch($template, $to, $parameters, $opts, queue: true);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function sendTest(string $template, string $to, array $parameters = []): EmailLog
    {
        return $this->send($template, $to, $parameters, new SendOptions(isTest: true));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function render(string $template, array $parameters = [], ?bool $strict = null): RenderedEmail
    {
        $model = EmailTemplate::query()->where('slug', $template)->first();

        if ($model === null) {
            throw new TemplateNotFoundException("Template [{$template}] was not found.");
        }

        $version = $this->versions->ensureCurrentVersion($model);

        return $this->renderer->render($version, $parameters, $strict);
    }

    /**
     * @param  string|list<string>  $to
     * @param  array<string, mixed>  $parameters
     */
    private function dispatch(
        string $templateSlug,
        string|array $to,
        array $parameters,
        SendOptions $options,
        bool $queue,
    ): EmailLog {
        $this->assertSerializable($parameters);

        $template = EmailTemplate::query()->where('slug', $templateSlug)->first();

        if ($template === null) {
            throw new TemplateNotFoundException("Template [{$templateSlug}] was not found.");
        }

        if (! $template->isSendable()) {
            throw new CannotSendInactiveTemplateException(
                "Template [{$templateSlug}] is not active and cannot be sent.",
            );
        }

        $this->mailConfig->apply();

        $isTest = $options->isTest;
        $deliveryEnabled = $this->mailConfig->isDeliveryEnabled();
        $allowTest = (bool) config('laravel-mailmanager.mail.allow_test_when_disabled', true);

        $version = $this->versions->ensureCurrentVersion($template);
        $rendered = $this->renderer->render($version, $parameters, $options->strict);

        $redirectTo = $this->mailConfig->redirectTo();
        $meta = [];

        if ($redirectTo !== null) {
            $meta = [
                'original_recipient' => $to,
                'original_cc' => $options->cc,
                'original_bcc' => $options->bcc,
                'redirected' => true,
            ];
            $to = $redirectTo;
            $options = new SendOptions(
                cc: null,
                bcc: null,
                replyTo: $options->replyTo,
                attachments: $options->attachments,
                queue: $options->queue,
                queueConnection: $options->queueConnection,
                queueName: $options->queueName,
                isTest: $options->isTest,
                strict: $options->strict,
                mailer: $options->mailer,
            );
        }

        if ((bool) config('laravel-mailmanager.mail.log_parameter_keys', false)) {
            $meta['parameter_keys'] = array_keys($parameters);
        }

        $recipient = is_array($to) ? implode(',', $to) : $to;

        $log = $this->logs->create([
            'email_template_id' => $template->id,
            'email_template_version_id' => $version->id,
            'recipient' => $recipient,
            'cc' => $this->normalizeAddresses($options->cc),
            'bcc' => $this->normalizeAddresses($options->bcc),
            'rendered_subject' => $rendered->subject,
            'rendered_html' => (bool) config('laravel-mailmanager.mail.store_rendered_html_in_logs', false)
                ? $rendered->html
                : null,
            'meta' => $meta === [] ? null : $meta,
            'status' => EmailLogStatus::Queued,
            'is_test' => $isTest,
        ]);

        if (! $deliveryEnabled && ! ($isTest && $allowTest)) {
            $this->logs->markSuppressed($log);

            if ((bool) config('laravel-mailmanager.mail.suppress_throws', true)) {
                throw new DeliveryDisabledException('Outgoing email delivery is disabled.');
            }

            return $log->refresh();
        }

        $mailerName = $options->mailer ?? (string) config('laravel-mailmanager.mail.mailer_name', 'mailmanager');

        try {
            if ($queue) {
                $mailable = new QueuedTemplateMailable(
                    templateKey: $template->slug,
                    parameters: $parameters,
                    versionId: $version->id,
                    isTest: $isTest,
                    emailLogId: $log->id,
                    mailerName: $mailerName,
                    strict: $options->strict,
                );

                $pending = Mail::mailer($mailerName)->to($to);

                if ($options->cc) {
                    $pending->cc($options->cc);
                }

                if ($options->bcc) {
                    $pending->bcc($options->bcc);
                }

                $pending->queue($mailable);
                $this->logs->markQueued($log);

                return $log->refresh();
            }

            $mailable = new TemplateMailable(
                templateKey: $template->slug,
                parameters: $parameters,
                versionId: $version->id,
                isTest: $isTest,
                emailLogId: $log->id,
                mailerName: $mailerName,
                strict: $options->strict,
            );

            $pending = Mail::mailer($mailerName)->to($to);

            if ($options->cc) {
                $pending->cc($options->cc);
            }

            if ($options->bcc) {
                $pending->bcc($options->bcc);
            }

            $pending->send($mailable);
            $this->logs->markSent($log);

            return $log->refresh();
        } catch (DeliveryDisabledException $e) {
            throw $e;
        } catch (Throwable $e) {
            $mail = $this->settings->group('mail');
            $this->logs->markFailed(
                $log,
                $e->getMessage(),
                EmailFailureType::ProviderReject,
                [
                    isset($mail['password']) ? (string) $mail['password'] : null,
                    isset($mail['username']) ? (string) $mail['username'] : null,
                ],
            );

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function assertSerializable(array $parameters): void
    {
        try {
            json_encode($parameters, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Template parameters must be JSON-serializable.', 0, $e);
        }
    }

    /**
     * @param  string|list<string>|null  $addresses
     * @return list<string>|null
     */
    private function normalizeAddresses(string|array|null $addresses): ?array
    {
        if ($addresses === null || $addresses === '') {
            return null;
        }

        if (is_string($addresses)) {
            return [$addresses];
        }

        /** @var list<string> $normalized */
        $normalized = array_map(static fn (mixed $address): string => (string) $address, $addresses);

        return $normalized;
    }
}
