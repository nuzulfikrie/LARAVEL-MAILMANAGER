<?php

declare(strict_types=1);

/**
 * Run Pest type coverage without concurrent Pokio forks corrupting the cache.
 *
 * Failure mode on CI (especially multi-core Linux runners):
 *   ParseError in vendor/pestphp/pest-plugin-type-coverage/.temp/v3.php
 *   e.g. ");rray (" from two writers concatenating PHP array dumps.
 *
 * The plugin disables multi-chunk forks only when isset($_ENV['__PEST_PLUGIN_ENV']).
 * On many CI PHP builds variables_order is GPCS (no "E"), so putenv() does not
 * populate $_ENV. We therefore start Pest with:
 *   - auto_prepend_file that sets $_ENV in the Pest process itself
 *   - variables_order=EGPCS so process env is imported if needed
 *   - --no-cache so a previously corrupt v3.php is never included
 */

$root = dirname(__DIR__);
$tempDir = $root.'/vendor/pestphp/pest-plugin-type-coverage/.temp';
$bootstrap = $root.'/scripts/type-coverage-bootstrap.php';
$pest = $root.'/vendor/bin/pest';

if (! is_file($pest)) {
    fwrite(STDERR, "Pest binary not found at {$pest}\n");
    exit(1);
}

if (! is_file($bootstrap)) {
    fwrite(STDERR, "Bootstrap not found at {$bootstrap}\n");
    exit(1);
}

if (is_dir($tempDir)) {
    foreach (glob($tempDir.'/v3.php*') ?: [] as $file) {
        @unlink($file);
    }
} else {
    @mkdir($tempDir, 0777, true);
}

// Also wipe any corrupt leftover at the well-known path before Pest boots.
$cacheFile = $tempDir.'/v3.php';
if (is_file($cacheFile)) {
    @unlink($cacheFile);
}

$php = PHP_BINARY;

$command = implode(' ', [
    escapeshellarg($php),
    // Ensure environment variables are imported into $_ENV in the Pest process.
    '-d',
    'variables_order=EGPCS',
    // Set $_ENV inside Pest *before* plugins/autoload run analysis.
    '-d',
    'auto_prepend_file='.escapeshellarg($bootstrap),
    escapeshellarg($pest),
    '--type-coverage',
    '--min=100',
    // Avoid reading a half-written cache from a previous crashed run.
    '--no-cache',
]);

$descriptors = [
    0 => STDIN,
    1 => STDOUT,
    2 => STDERR,
];

// Explicit env for the child (more reliable than putenv alone across CI images).
$env = [];
foreach ($_SERVER as $key => $value) {
    if (is_string($key) && is_string($value)) {
        $env[$key] = $value;
    }
}
foreach ($_ENV as $key => $value) {
    if (is_string($key) && is_string($value)) {
        $env[$key] = $value;
    }
}
$env['__PEST_PLUGIN_ENV'] = '1';

$process = proc_open($command, $descriptors, $pipes, $root, $env);

if (! is_resource($process)) {
    fwrite(STDERR, "Failed to start type-coverage process.\n");
    exit(1);
}

$exitCode = proc_close($process);

exit($exitCode);
