<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Support;

final class ContentHasher
{
    /**
     * @param  array<string, mixed>  $designJson
     * @param  array<string, mixed>  $parameters
     */
    public static function hash(
        string $subject,
        string $htmlContent,
        array $designJson,
        array $parameters,
    ): string {
        $payload = [
            'subject' => $subject,
            'html_content' => $htmlContent,
            'design_json' => self::canonicalize($designJson),
            'parameters' => self::canonicalize($parameters),
        ];

        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return hash('sha256', $json);
    }

    public static function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $out = [];

        foreach ($value as $k => $v) {
            $out[$k] = self::canonicalize($v);
        }

        if (! $isList) {
            ksort($out, SORT_STRING);
        }

        return $out;
    }
}
