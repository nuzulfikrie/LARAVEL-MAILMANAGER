<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Services;

use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Support\Mask;

final class EmailLogService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EmailLog
    {
        return EmailLog::query()->create($attributes);
    }

    public function markQueued(EmailLog $log, ?string $jobId = null): EmailLog
    {
        $log->forceFill([
            'status' => EmailLogStatus::Queued,
            'queue_job_id' => $jobId,
        ])->save();

        return $log->refresh();
    }

    public function markSent(EmailLog $log, ?string $providerMessageId = null): EmailLog
    {
        $log->forceFill([
            'status' => EmailLogStatus::Sent,
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
            'failure_reason' => null,
            'failure_type' => null,
        ])->save();

        return $log->refresh();
    }

    /**
     * @param  list<string|null>  $secrets
     */
    public function markFailed(
        EmailLog $log,
        string $reason,
        EmailFailureType $type,
        array $secrets = [],
    ): EmailLog {
        $log->forceFill([
            'status' => EmailLogStatus::Failed,
            'failure_reason' => Mask::secrets(mb_substr($reason, 0, 2000), $secrets),
            'failure_type' => $type,
            'sent_at' => null,
        ])->save();

        return $log->refresh();
    }

    public function markSuppressed(EmailLog $log, string $reason = 'Delivery disabled'): EmailLog
    {
        $log->forceFill([
            'status' => EmailLogStatus::Suppressed,
            'failure_reason' => $reason,
            'failure_type' => EmailFailureType::Suppressed,
            'sent_at' => null,
        ])->save();

        return $log->refresh();
    }
}
