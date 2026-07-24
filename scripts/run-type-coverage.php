<?php

declare(strict_types=1);

/**
 * Run Pest type coverage safely for CI.
 *
 * 1. Re-apply vendor patch forcing single-process sync (no Pokio multi-fork)
 * 2. Delete any corrupt .temp/v3.php cache
 * 3. Run pest with --no-cache
 */

$root = dirname(__DIR__);

$patcher = $root.'/scripts/patch-type-coverage-sync.php';
if (is_file($patcher)) {
    passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($patcher), $patchCode);
    if ($patchCode !== 0) {
        exit($patchCode);
    }
}

$tempDir = $root.'/vendor/pestphp/pest-plugin-type-coverage/.temp';
if (is_dir($tempDir)) {
    foreach (glob($tempDir.'/v3.php*') ?: [] as $file) {
        @unlink($file);
    }
} else {
    @mkdir($tempDir, 0777, true);
}

$pest = $root.'/vendor/bin/pest';
if (! is_file($pest)) {
    fwrite(STDERR, "Pest not found at {$pest}\n");
    exit(1);
}

$bootstrap = $root.'/scripts/type-coverage-bootstrap.php';

$cmd = [
    escapeshellarg(PHP_BINARY),
    '-d',
    'variables_order=EGPCS',
];

// Prepend sets $_ENV in the Pest process itself (works even when variables_order=GPCS).
if (is_file($bootstrap)) {
    $cmd[] = '-d';
    $cmd[] = 'auto_prepend_file='.escapeshellarg($bootstrap);
}

$cmd[] = escapeshellarg($pest);
$cmd[] = '--type-coverage';
$cmd[] = '--min=100';
$cmd[] = '--no-cache';

$command = implode(' ', $cmd);

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

$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root, $env);

if (! is_resource($process)) {
    fwrite(STDERR, "Failed to start type-coverage process\n");
    exit(1);
}

exit(proc_close($process));
