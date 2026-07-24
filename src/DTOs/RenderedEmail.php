<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\DTOs;

final readonly class RenderedEmail
{
    public function __construct(
        public string $subject,
        public string $html,
        public ?int $templateId,
        public ?int $versionId,
    ) {}
}
