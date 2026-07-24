<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering;

/**
 * Best-effort HTML cleanup for email body content (US-026).
 * Not a full HTML purifier — strips common XSS vectors used in email HTML.
 */
final class HtmlSanitizer
{
    public function sanitize(string $html): string
    {
        if (! (bool) config('laravel-mailmanager.sanitizer.enabled', true)) {
            return $html;
        }

        if ((bool) config('laravel-mailmanager.sanitizer.strip_scripts', true)) {
            $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
            $html = (string) preg_replace('/<\/?script\b[^>]*>/i', '', $html);
            $html = (string) preg_replace('/<(iframe|object|embed|applet)\b[^>]*>.*?<\/\1>/is', '', $html);
            $html = (string) preg_replace('/<(iframe|object|embed|applet)\b[^>]*\/?>/i', '', $html);
        }

        if ((bool) config('laravel-mailmanager.sanitizer.strip_event_handlers', true)) {
            $html = (string) preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        }

        // Neutralize javascript:/vbscript: URLs in common attributes.
        $html = (string) preg_replace(
            '/\s(href|src|xlink:href|action)\s*=\s*(["\'])\s*(javascript|vbscript|data)\s*:/i',
            ' $1=$2#blocked:',
            $html,
        );

        // Strip meta refresh / base redirects sometimes injected into HTML.
        $html = (string) preg_replace('/<meta\b[^>]*http-equiv\s*=\s*["\']?refresh["\']?[^>]*>/i', '', $html);

        return $html;
    }
}
