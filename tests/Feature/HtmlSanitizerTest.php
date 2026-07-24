<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\HtmlSanitizer;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\TemplateRenderer;

it('strips script tags and event handlers from html', function () {
    $sanitizer = new HtmlSanitizer;
    $dirty = '<p onclick="alert(1)">Hi</p><script>alert(2)</script><a href="javascript:alert(3)">x</a>';

    $clean = $sanitizer->sanitize($dirty);

    expect($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('Hi');
});

it('strips iframe tags', function () {
    $clean = (new HtmlSanitizer)->sanitize('<div><iframe src="https://evil.test"></iframe>ok</div>');

    expect($clean)->not->toContain('iframe')
        ->and($clean)->toContain('ok');
});

it('applies sanitizer during template render', function () {
    $template = EmailTemplate::factory()->create([
        'subject' => 'S',
        'html_content' => '<p>Hello {name}</p><script>steal()</script>',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);

    $rendered = app(TemplateRenderer::class)->render($template, ['name' => 'Ali']);

    expect($rendered->html)->toContain('Hello Ali')
        ->and($rendered->html)->not->toContain('<script');
});

it('can disable sanitizer via config', function () {
    config(['laravel-mailmanager.sanitizer.enabled' => false]);

    $html = '<script>x</script>';
    expect((new HtmlSanitizer)->sanitize($html))->toBe($html);
});
