<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Exceptions\InvalidCollectionException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\MissingRequiredParameterException;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\TemplateRenderer;

it('renders scalar placeholders with html escaping', function () {
    $html = file_get_contents(__DIR__.'/../Fixtures/html/welcome-scalar.html');
    $template = EmailTemplate::factory()->create([
        'subject' => 'Welcome {name}',
        'html_content' => $html,
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
            'application_name' => ['type' => 'string', 'required' => true],
        ],
    ]);

    $rendered = app(TemplateRenderer::class)->render($template, [
        'name' => '<script>x</script>',
        'application_name' => 'Acme',
    ]);

    // Subject is plain text (tags stripped); body HTML-escapes by default.
    expect($rendered->subject)->toBe('Welcome x')
        ->and($rendered->html)->toContain('Hello &lt;script&gt;x&lt;/script&gt;')
        ->and($rendered->html)->toContain('Acme');
});

it('expands collection table rows from invoice fixture', function () {
    $html = file_get_contents(__DIR__.'/../Fixtures/html/invoice-collection.html');
    $template = EmailTemplate::factory()->create([
        'subject' => 'Invoice {invoice_number}',
        'html_content' => $html,
        'parameters' => [
            'customer_name' => ['type' => 'string', 'required' => true],
            'invoice_number' => ['type' => 'string', 'required' => true],
            'invoice_items' => [
                'type' => 'collection',
                'required' => true,
                'empty_behavior' => 'headers_message',
                'columns' => [
                    ['field' => 'description', 'format' => 'plain'],
                    ['field' => 'quantity', 'format' => 'integer'],
                    ['field' => 'unit_price', 'format' => 'currency', 'currency' => 'MYR'],
                    ['field' => 'total', 'format' => 'currency', 'currency' => 'MYR'],
                ],
            ],
        ],
    ]);

    $rendered = app(TemplateRenderer::class)->render($template, [
        'customer_name' => 'Ali Ahmad',
        'invoice_number' => 'INV-1',
        'invoice_items' => [
            [
                'description' => 'Web development',
                'quantity' => 1,
                'unit_price' => 1500,
                'total' => 1500,
            ],
            [
                'description' => 'Hosting',
                'quantity' => 12,
                'unit_price' => 50,
                'total' => 600,
            ],
        ],
    ]);

    expect($rendered->subject)->toBe('Invoice INV-1')
        ->and($rendered->html)->toContain('Ali Ahmad')
        ->and($rendered->html)->toContain('Web development')
        ->and($rendered->html)->toContain('Hosting')
        ->and($rendered->html)->toContain('MYR 1,500.00')
        ->and($rendered->html)->not->toContain('data-email-row-template')
        ->and($rendered->html)->not->toContain('data-email-collection');
});

it('fails when collection has no row template', function () {
    $template = EmailTemplate::factory()->create([
        'subject' => 'X',
        'html_content' => '<table data-email-collection="items"><tbody></tbody></table>',
        'parameters' => [
            'items' => [
                'type' => 'collection',
                'required' => true,
                'columns' => [['field' => 'name']],
            ],
        ],
    ]);

    app(TemplateRenderer::class)->render($template, [
        'items' => [['name' => 'a']],
    ]);
})->throws(InvalidCollectionException::class);

it('fails on missing required parameters', function () {
    $template = EmailTemplate::factory()->create([
        'subject' => 'Hi {name}',
        'html_content' => '<p>{name}</p>',
        'parameters' => [
            'name' => ['type' => 'string', 'required' => true],
        ],
    ]);

    app(TemplateRenderer::class)->render($template, []);
})->throws(MissingRequiredParameterException::class);
