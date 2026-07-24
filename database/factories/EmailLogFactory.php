<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_template_id' => EmailTemplate::factory(),
            'email_template_version_id' => null,
            'recipient' => fake()->safeEmail(),
            'cc' => null,
            'bcc' => null,
            'rendered_subject' => 'Hello World',
            'rendered_html' => null,
            'meta' => null,
            'status' => EmailLogStatus::Sent,
            'provider_message_id' => null,
            'failure_reason' => null,
            'failure_type' => null,
            'queue_job_id' => null,
            'is_test' => false,
            'sent_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (EmailLog $log): void {
            if ($log->email_template_version_id !== null || $log->email_template_id === null) {
                return;
            }

            $version = EmailTemplateVersion::factory()->create([
                'email_template_id' => $log->email_template_id,
            ]);

            $log->forceFill([
                'email_template_version_id' => $version->id,
            ])->save();
        });
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => EmailLogStatus::Failed,
            'failure_type' => EmailFailureType::SmtpConnection,
            'failure_reason' => 'Connection timed out',
            'sent_at' => null,
        ]);
    }

    public function test(): static
    {
        return $this->state(fn (): array => [
            'is_test' => true,
        ]);
    }
}
