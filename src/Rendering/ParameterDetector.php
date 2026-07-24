<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering;

final class ParameterDetector
{
    /**
     * @return array{scalars: list<string>, collections: list<string>}
     */
    public function detect(string $subject, string $html): array
    {
        $pattern = (string) config(
            'laravel-mailmanager.parameters.placeholder_pattern',
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
        );

        $scalars = [];

        if (preg_match_all($pattern, $subject.' '.$html, $matches) > 0) {
            $scalars = array_values(array_unique($matches[1]));
        }

        $collections = [];

        if (preg_match_all('/data-email-collection=["\']([a-zA-Z_][a-zA-Z0-9_]*)["\']/', $html, $cMatches) > 0) {
            $collections = array_values(array_unique($cMatches[1]));
        }

        // Column placeholders inside collections are not top-level; leave as-is for UI warnings only.
        return [
            'scalars' => $scalars,
            'collections' => $collections,
        ];
    }
}
