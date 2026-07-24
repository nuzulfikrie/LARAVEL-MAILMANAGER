<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Support;

final class Mask
{
    /**
     * @param  list<string|null>  $secrets
     */
    public static function secrets(string $message, array $secrets = []): string
    {
        foreach ($secrets as $secret) {
            if ($secret === null || $secret === '') {
                continue;
            }

            $message = str_replace($secret, '********', $message);
        }

        return $message;
    }

    public static function password(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '********';
    }
}
