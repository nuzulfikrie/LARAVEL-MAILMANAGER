<?php

declare(strict_types=1);

/**
 * Run Pest type coverage in a single process.
 *
 * pest-plugin-type-coverage forks via Pokio when available and writes a shared
 * cache at vendor/pestphp/pest-plugin-type-coverage/.temp/v3.php. Concurrent
 * writers can corrupt that file (e.g. ");rray (") and crash CI with ParseError.
 *
 * Setting __PEST_PLUGIN_ENV disables forking in the plugin Analyser.
 */

// Disable Pokio fork path (checked via isset($_ENV['__PEST_PLUGIN_ENV'])).
$_ENV['__PEST_PLUGIN_ENV'] = '1';
$_SERVER['__PEST_PLUGIN_ENV'] = '1';
putenv('__PEST_PLUGIN_ENV=1');

$tempDir = dirname(__DIR__).'/vendor/pestphp/pest-plugin-type-coverage/.temp';

if (is_dir($tempDir)) {
    foreach (glob($tempDir.'/v3.php*') ?: [] as $file) {
        @unlink($file);
    }
} else {
    @mkdir($tempDir, 0777, true);
}

$pest = dirname(__DIR__).'/vendor/bin/pest';
$php = PHP_BINARY;

// Re-exec pest in this process tree with env already set for child PHP.
$command = escapeshellarg($php).' '.escapeshellarg($pest).' --type-coverage --min=100';

passthru($command, $exitCode);

exit($exitCode);
