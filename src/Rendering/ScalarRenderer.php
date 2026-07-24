<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering;

final class ScalarRenderer
{
    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $schema
     */
    public function replace(string $content, array $parameters, array $schema, bool $isHtml): string
    {
        $pattern = (string) config(
            'laravel-mailmanager.parameters.placeholder_pattern',
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
        );

        $replaced = preg_replace_callback($pattern, function (array $matches) use ($parameters, $schema, $isHtml): string {
            $name = $matches[1];
            $definition = is_array($schema[$name] ?? null) ? $schema[$name] : [];

            if (($definition['type'] ?? null) === 'collection') {
                return $matches[0];
            }

            if (! array_key_exists($name, $parameters) || $parameters[$name] === null) {
                return (string) ($definition['fallback'] ?? '');
            }

            $value = $this->stringify($parameters[$name]);
            $allowRaw = (bool) ($definition['allow_raw_html'] ?? false)
                || (bool) ($definition[config('laravel-mailmanager.parameters.raw_opt_in_key', 'allow_raw_html')] ?? false);

            if ($isHtml) {
                if ($allowRaw || ! (bool) config('laravel-mailmanager.parameters.html_escape_default', true)) {
                    return $value;
                }

                return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            return strip_tags($value);
        }, $content);

        return is_string($replaced) ? $replaced : $content;
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }
}
