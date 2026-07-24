<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use InvalidArgumentException;

class QueuedTemplateMailable extends TemplateMailable implements ShouldQueue
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        string $templateKey,
        array $parameters = [],
        ?int $versionId = null,
        bool $isTest = false,
        ?int $emailLogId = null,
        ?string $mailerName = null,
        ?bool $strict = null,
    ) {
        parent::__construct(
            templateKey: $templateKey,
            parameters: $parameters,
            versionId: $versionId,
            isTest: $isTest,
            emailLogId: $emailLogId,
            mailerName: $mailerName,
            strict: $strict,
        );

        if ($this->versionId === null) {
            throw new InvalidArgumentException('QueuedTemplateMailable requires a versionId.');
        }
    }
}
