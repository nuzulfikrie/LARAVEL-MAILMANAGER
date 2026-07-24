<?php

declare(strict_types=1);

/**
 * CI-safe type coverage entrypoint used by `composer test:types`.
 */

$root = dirname(__DIR__);
$patcher = $root.'/scripts/patch-type-coverage-sync.php';
$pest = $root.'/vendor/bin/pest';
$bootstrap = $root.'/scripts/type-coverage-bootstrap.php';
$analyser = $root.'/vendor/pestphp/pest-plugin-type-coverage/src/Analyser.php';
$tempDir = $root.'/vendor/pestphp/pest-plugin-type-coverage/.temp';

if (! is_file($pest)) {
    fwrite(STDERR, "Pest not found: {$pest}\n");
    exit(1);
}

// 1) Patch vendor plugin (idempotent).
if (is_file($patcher)) {
    passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($patcher), $code);
    if ($code !== 0) {
        exit($code);
    }
}

// 2) Verify patch actually landed (hard fail on CI if not).
$analyserSource = is_file($analyser) ? (file_get_contents($analyser) ?: '') : '';
if ($analyserSource === '' || ! str_contains($analyserSource, 'MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC')) {
    // Accept alternate successful patch marker from useSync-only fallback.
    if (! str_contains($analyserSource, 'pokio()->useSync();') || preg_match('/\$maxProcesses\s*=\s*1\s*;/', $analyserSource) !== 1) {
        fwrite(STDERR, "FATAL: type-coverage plugin was not patched to single-process mode.\n");
        fwrite(STDERR, "Refusing to run multi-fork analysis that corrupts .temp/v3.php on CI.\n");
        exit(1);
    }
}

// 3) Wipe cache directory completely.
if (is_dir($tempDir)) {
    foreach (glob($tempDir.'/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
} else {
    @mkdir($tempDir, 0777, true);
}

// 4) Run pest with prepend (sets $_ENV even when variables_order=GPCS).
$cmd = [
    escapeshellarg(PHP_BINARY),
    '-d',
    'variables_order=EGPCS',
];

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
foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
    if (is_string($key) && is_scalar($value)) {
        $env[$key] = (string) $value;
    }
}
$env['__PEST_PLUGIN_ENV'] = '1';

fwrite(STDOUT, "+ {$command}\n");

$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root, $env);
if (! is_resource($process)) {
    fwrite(STDERR, "Failed to start type-coverage process\n");
    exit(1);
}

exit(proc_close($process));
