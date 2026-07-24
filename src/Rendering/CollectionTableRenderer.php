<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmptyCollectionBehavior;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\InvalidCollectionException;
use NuzulFikrieCoder\LaravelMailmanager\Rendering\Formatters\ValueFormatter;

final class CollectionTableRenderer
{
    public function __construct(
        private readonly ValueFormatter $formatter = new ValueFormatter,
    ) {}

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $parameters
     */
    public function expand(string $html, array $schema, array $parameters): string
    {
        if (! str_contains($html, 'data-email-collection')) {
            return $html;
        }

        $wrapped = '<div id="mailmanager-root">'.$html.'</div>';
        $dom = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$wrapped, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new DOMXPath($dom);
        /** @var list<DOMElement> $hosts */
        $hosts = [];

        foreach ($xpath->query('//*[@data-email-collection]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $hosts[] = $node;
            }
        }

        // Innermost first: process deepest nodes before parents.
        usort($hosts, fn (DOMElement $a, DOMElement $b): int => $this->depth($b) <=> $this->depth($a));

        foreach ($hosts as $host) {
            $this->expandHost($host, $schema, $parameters, $dom);
        }

        $root = $dom->getElementById('mailmanager-root');

        if ($root === null) {
            return $html;
        }

        $htmlOut = '';
        foreach ($root->childNodes as $child) {
            $htmlOut .= $dom->saveHTML($child);
        }

        return $htmlOut;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $parameters
     */
    private function expandHost(DOMElement $host, array $schema, array $parameters, DOMDocument $dom): void
    {
        $name = $host->getAttribute('data-email-collection');
        $definition = is_array($schema[$name] ?? null) ? $schema[$name] : [];
        $rows = $parameters[$name] ?? [];

        if (! is_array($rows)) {
            throw new InvalidCollectionException("Collection [{$name}] must be an array.");
        }

        $templates = [];
        foreach ($host->getElementsByTagName('*') as $el) {
            if ($el->hasAttribute('data-email-row-template')) {
                $templates[] = $el;
            }
        }

        if ($templates === []) {
            throw new InvalidCollectionException("Collection [{$name}] has no row template.");
        }

        $template = $templates[0];
        for ($i = 1, $count = count($templates); $i < $count; $i++) {
            $templates[$i]->parentNode?->removeChild($templates[$i]);
        }

        $behavior = EmptyCollectionBehavior::tryFrom((string) ($definition['empty_behavior'] ?? 'headers_message'))
            ?? EmptyCollectionBehavior::HeadersMessage;

        if ($rows === []) {
            match ($behavior) {
                EmptyCollectionBehavior::Hide => $host->parentNode?->removeChild($host),
                EmptyCollectionBehavior::Fail => throw new InvalidCollectionException("Collection [{$name}] is empty."),
                EmptyCollectionBehavior::HeadersMessage, EmptyCollectionBehavior::CustomFallback => $this->injectEmptyMessage(
                    $host,
                    $template,
                    (string) ($definition['empty_message'] ?? 'No items are available.'),
                    $dom,
                ),
            };

            if ($host->parentNode !== null) {
                $host->removeAttribute('data-email-collection');
            }

            return;
        }

        $parent = $template->parentNode;
        /** @var list<mixed> $columns */
        $columns = array_values(is_array($definition['columns'] ?? null) ? $definition['columns'] : []);

        foreach ($rows as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }

            if (! is_array($row)) {
                throw new InvalidCollectionException("Collection [{$name}] rows must be arrays or objects.");
            }

            $clone = $template->cloneNode(true);

            if (! $clone instanceof DOMElement) {
                continue;
            }

            $clone->removeAttribute('data-email-row-template');
            $this->fillRow($clone, $row, $columns, $dom);
            $parent?->insertBefore($clone, $template);
        }

        $parent?->removeChild($template);
        $host->removeAttribute('data-email-collection');
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<mixed>  $columns
     */
    private function fillRow(DOMElement $clone, array $row, array $columns, DOMDocument $dom): void
    {
        $html = '';
        foreach ($clone->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        foreach ($columns as $column) {
            if (! is_array($column) || ! isset($column['field'])) {
                continue;
            }

            $field = (string) $column['field'];
            $formatted = $this->formatter->format($row[$field] ?? null, $column);
            $allowRaw = (bool) ($column['allow_raw_html'] ?? false);
            $safe = $allowRaw
                ? $formatted
                : htmlspecialchars($formatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $html = str_replace('{'.$field.'}', $safe, $html);

            if (isset($column['alignment']) && in_array($column['alignment'], ['left', 'center', 'right'], true)) {
                // Applied after reload via style on cells containing the value — best-effort in HTML string.
                $html = preg_replace(
                    '/(<t[dh]\b)([^>]*>)'.preg_quote($safe, '/').'/',
                    '$1 style="text-align:'.$column['alignment'].'"$2'.$safe,
                    $html,
                    1,
                ) ?? $html;
            }
        }

        while ($clone->firstChild) {
            $clone->removeChild($clone->firstChild);
        }

        $tmp = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $tmp->loadHTML('<?xml encoding="UTF-8"><div id="r">'.$html.'</div>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $r = $tmp->getElementById('r');

        if ($r === null) {
            return;
        }

        foreach (iterator_to_array($r->childNodes) as $child) {
            $clone->appendChild($clone->ownerDocument->importNode($child, true));
        }
    }

    private function injectEmptyMessage(DOMElement $host, DOMElement $template, string $message, DOMDocument $dom): void
    {
        $parent = $template->parentNode;
        $template->parentNode?->removeChild($template);

        $tr = $dom->createElement('tr');
        $td = $dom->createElement('td', htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $tr->appendChild($td);
        $parent?->appendChild($tr);
    }

    private function depth(DOMNode $node): int
    {
        $depth = 0;
        while ($node->parentNode !== null) {
            $depth++;
            $node = $node->parentNode;
        }

        return $depth;
    }
}
