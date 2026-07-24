<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\DTOs;

use Illuminate\Mail\Mailables\Attachment;

final readonly class SendOptions
{
    /**
     * @param  string|list<string>|null  $cc
     * @param  string|list<string>|null  $bcc
     * @param  string|list<string>|null  $replyTo
     * @param  list<string|Attachment>  $attachments
     */
    public function __construct(
        public string|array|null $cc = null,
        public string|array|null $bcc = null,
        public string|array|null $replyTo = null,
        public array $attachments = [],
        public bool $queue = false,
        public ?string $queueConnection = null,
        public ?string $queueName = null,
        public bool $isTest = false,
        public ?bool $strict = null,
        public ?string $mailer = null,
    ) {}

    /**
     * @param  array<string, mixed>|self  $options
     */
    public static function from(array|self $options): self
    {
        if ($options instanceof self) {
            return $options;
        }

        /** @var list<string|Attachment> $attachments */
        $attachments = $options['attachments'] ?? [];

        return new self(
            cc: $options['cc'] ?? null,
            bcc: $options['bcc'] ?? null,
            replyTo: $options['replyTo'] ?? $options['reply_to'] ?? null,
            attachments: $attachments,
            queue: (bool) ($options['queue'] ?? false),
            queueConnection: $options['queueConnection'] ?? $options['queue_connection'] ?? null,
            queueName: $options['queueName'] ?? $options['queue_name'] ?? null,
            isTest: (bool) ($options['isTest'] ?? $options['is_test'] ?? false),
            strict: array_key_exists('strict', $options) ? (bool) $options['strict'] : null,
            mailer: $options['mailer'] ?? null,
        );
    }
}
