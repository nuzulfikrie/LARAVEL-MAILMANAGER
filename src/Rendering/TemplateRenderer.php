<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering;

use NuzulFikrieCoder\LaravelMailmanager\DTOs\RenderedEmail;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\MissingRequiredParameterException;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;

final class TemplateRenderer
{
    public function __construct(
        private readonly ParameterValidator $validator = new ParameterValidator,
        private readonly CollectionTableRenderer $collections = new CollectionTableRenderer,
        private readonly ScalarRenderer $scalars = new ScalarRenderer,
        private readonly HtmlSanitizer $sanitizer = new HtmlSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function render(
        EmailTemplate|EmailTemplateVersion $source,
        array $parameters,
        ?bool $strict = null,
    ): RenderedEmail {
        $strict ??= (bool) config('laravel-mailmanager.parameters.strict', false);
        /** @var array<string, mixed> $schema */
        $schema = $source->parameters ?? [];

        $this->validator->validate($schema, $parameters, $strict);

        $html = $this->collections->expand($source->html_content, $schema, $parameters);
        $html = $this->scalars->replace($html, $parameters, $schema, true);
        $subject = $this->scalars->replace($source->subject, $parameters, $schema, false);
        $html = $this->sanitizer->sanitize($html);

        $this->assertNoUnresolved($subject, $html, $strict);

        $templateId = $source instanceof EmailTemplate
            ? $source->id
            : $source->email_template_id;
        $versionId = $source instanceof EmailTemplateVersion ? $source->id : null;

        return new RenderedEmail(
            subject: $subject,
            html: $html,
            templateId: $templateId,
            versionId: $versionId,
        );
    }

    private function assertNoUnresolved(string $subject, string $html, bool $strict): void
    {
        $pattern = (string) config(
            'laravel-mailmanager.parameters.placeholder_pattern',
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
        );

        $failSubject = (bool) config('laravel-mailmanager.parameters.fail_on_unresolved_subject', true);
        $failBody = $strict || (bool) config('laravel-mailmanager.parameters.fail_on_unresolved_body', false);

        if ($failSubject && preg_match($pattern, $subject) === 1) {
            throw new MissingRequiredParameterException('Unresolved placeholders remain in the email subject.');
        }

        if ($failBody && preg_match($pattern, $html) === 1) {
            throw new MissingRequiredParameterException('Unresolved placeholders remain in the email body.');
        }
    }
}
