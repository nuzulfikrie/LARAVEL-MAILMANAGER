<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Facades;

use Illuminate\Support\Facades\Facade;
use NuzulFikrieCoder\LaravelMailmanager\DTOs\RenderedEmail;
use NuzulFikrieCoder\LaravelMailmanager\DTOs\SendOptions;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate as EmailTemplateModel;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;

/**
 * @method static EmailLog send(string $template, string|array<int, string> $to, array<string, mixed> $parameters = [], array<string, mixed>|SendOptions $options = [])
 * @method static EmailLog queue(string $template, string|array<int, string> $to, array<string, mixed> $parameters = [], array<string, mixed>|SendOptions $options = [])
 * @method static EmailLog sendTest(string $template, string $to, array<string, mixed> $parameters = [])
 * @method static RenderedEmail render(string $template, array<string, mixed> $parameters = [], ?bool $strict = null)
 * @method static EmailTemplateModel create(array<string, mixed> $data, ?int $actorId = null)
 * @method static EmailTemplateModel update(EmailTemplateModel $template, array<string, mixed> $data, ?int $actorId = null)
 * @method static EmailTemplateModel activate(EmailTemplateModel $template, ?int $actorId = null)
 * @method static EmailTemplateModel deactivate(EmailTemplateModel $template, ?int $actorId = null)
 *
 * @see EmailTemplateService
 */
class EmailTemplate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmailTemplateService::class;
    }
}
