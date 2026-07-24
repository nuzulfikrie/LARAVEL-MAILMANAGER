<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use NuzulFikrieCoder\LaravelMailmanager\Database\Factories\EmailLogFactory;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;

/**
 * @property int $id
 * @property int|null $email_template_id
 * @property int|null $email_template_version_id
 * @property string $recipient
 * @property array<int, string>|null $cc
 * @property array<int, string>|null $bcc
 * @property string $rendered_subject
 * @property string|null $rendered_html
 * @property array<string, mixed>|null $meta
 * @property EmailLogStatus $status
 * @property string|null $provider_message_id
 * @property string|null $failure_reason
 * @property EmailFailureType|null $failure_type
 * @property string|null $queue_job_id
 * @property bool $is_test
 * @property Carbon|null $sent_at
 */
class EmailLog extends Model
{
    /** @use HasFactory<EmailLogFactory> */
    use HasFactory;

    protected $fillable = [
        'email_template_id',
        'email_template_version_id',
        'recipient',
        'cc',
        'bcc',
        'rendered_subject',
        'rendered_html',
        'meta',
        'status',
        'provider_message_id',
        'failure_reason',
        'failure_type',
        'queue_job_id',
        'is_test',
        'sent_at',
    ];

    public function getTable(): string
    {
        return (string) config('laravel-mailmanager.tables.logs', 'email_logs');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cc' => 'array',
            'bcc' => 'array',
            'meta' => 'array',
            'status' => EmailLogStatus::class,
            'failure_type' => EmailFailureType::class,
            'is_test' => 'boolean',
            'sent_at' => 'datetime',
            'email_template_id' => 'integer',
            'email_template_version_id' => 'integer',
        ];
    }

    protected static function newFactory(): EmailLogFactory
    {
        return EmailLogFactory::new();
    }

    /**
     * @return BelongsTo<EmailTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    /**
     * @return BelongsTo<EmailTemplateVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(EmailTemplateVersion::class, 'email_template_version_id');
    }

    /**
     * @param  Builder<EmailLog>  $query
     * @return Builder<EmailLog>
     */
    public function scopeStatus(Builder $query, EmailLogStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<EmailLog>  $query
     * @return Builder<EmailLog>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', EmailLogStatus::Failed);
    }

    /**
     * @param  Builder<EmailLog>  $query
     * @return Builder<EmailLog>
     */
    public function scopeTest(Builder $query): Builder
    {
        return $query->where('is_test', true);
    }

    /**
     * MVP retry eligibility: stored HTML/subject and transport-class failure.
     */
    public function isRetryEligible(): bool
    {
        if ($this->rendered_html === null || $this->rendered_html === '') {
            return false;
        }

        if ($this->status !== EmailLogStatus::Failed) {
            return false;
        }

        return in_array($this->failure_type, [
            EmailFailureType::SmtpConnection,
            EmailFailureType::SmtpAuth,
            EmailFailureType::ProviderReject,
            EmailFailureType::Queue,
        ], true);
    }
}
