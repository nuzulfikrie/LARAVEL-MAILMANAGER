<?php

declare(strict_types=1);

/**
 * Force pest-plugin-type-coverage to never multi-fork.
 *
 * Upstream races on vendor/pestphp/pest-plugin-type-coverage/.temp/v3.php when
 * Pokio forks concurrent workers (ParseError: ");rray ("). Env-based guards are
 * unreliable because many CI PHP builds use variables_order=GPCS so $_ENV is empty.
 *
 * This patch is re-applied on every composer dump-autoload.
 */

$root = dirname(__DIR__);
$analyser = $root.'/vendor/pestphp/pest-plugin-type-coverage/src/Analyser.php';
$cache = $root.'/vendor/pestphp/pest-plugin-type-coverage/src/Support/Cache.php';

if (! is_file($analyser)) {
    fwrite(STDOUT, "type-coverage patch: Analyser.php not installed, skip\n");
    exit(0);
}

$analyserSource = file_get_contents($analyser);
if ($analyserSource === false) {
    fwrite(STDERR, "type-coverage patch: cannot read Analyser.php\n");
    exit(1);
}

// Already patched?
if (str_contains($analyserSource, 'MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC')) {
    fwrite(STDOUT, "type-coverage patch: Analyser already patched\n");
} else {
    $originalMax = <<<'PHP'
        $maxProcesses = (Environment::supportsFork() && ! isset($_ENV['__PEST_PLUGIN_ENV']))
            ? (Environment::maxProcesses() / 3)
            : 1;
PHP;

    $patchedMax = <<<'PHP'
        // MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC
        // Always single-process: concurrent Pokio forks corrupt .temp/v3.php on CI.
        $maxProcesses = 1;
PHP;

    $originalAsync = <<<'PHP'
        if ($useAsync === false) {
            pokio()->useSync();
        } else {
            if (Environment::supportsFork() && ! isset($_ENV['__PEST_PLUGIN_ENV'])) {
                pokio()->useFork();
            }
        }
PHP;

    $patchedAsync = <<<'PHP'
        // MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC
        // Never fork: default Pokio runtime is still ForkRuntime when useFork is omitted.
        pokio()->useSync();
PHP;

    if (! str_contains($analyserSource, $originalMax) || ! str_contains($analyserSource, $originalAsync)) {
        fwrite(STDERR, "type-coverage patch: Analyser.php shape changed; cannot patch safely\n");
        exit(1);
    }

    $analyserSource = str_replace($originalMax, $patchedMax, $analyserSource);
    $analyserSource = str_replace($originalAsync, $patchedAsync, $analyserSource);

    if (file_put_contents($analyser, $analyserSource) === false) {
        fwrite(STDERR, "type-coverage patch: failed writing Analyser.php\n");
        exit(1);
    }

    fwrite(STDOUT, "type-coverage patch: Analyser forced to sync/single-process\n");
}

// Atomic cache writes reduce residual risk if anything else writes concurrently.
if (is_file($cache)) {
    $cacheSource = file_get_contents($cache);
    if ($cacheSource !== false && ! str_contains($cacheSource, 'MAILMANAGER_ATOMIC_TYPE_COVERAGE_CACHE')) {
        $originalWrite = <<<'PHP'
            $content = '<?php return '.var_export($cache, true).';';

            if (file_put_contents($filePath, $content) !== false) {
                chmod($filePath, 0666);
            }
PHP;

        $patchedWrite = <<<'PHP'
            // MAILMANAGER_ATOMIC_TYPE_COVERAGE_CACHE
            $content = '<?php return '.var_export($cache, true).';';
            $tempPath = $filePath.'.'.getmypid().'.tmp';

            if (file_put_contents($tempPath, $content) !== false) {
                // rename is atomic on the same filesystem; avoids partial includes.
                if (! @rename($tempPath, $filePath)) {
                    @unlink($filePath);
                    @rename($tempPath, $filePath);
                }
                if (is_file($filePath)) {
                    chmod($filePath, 0666);
                }
                @unlink($tempPath);
            }
PHP;

        if (str_contains($cacheSource, $originalWrite)) {
            $cacheSource = str_replace($originalWrite, $patchedWrite, $cacheSource);
            file_put_contents($cache, $cacheSource);
            fwrite(STDOUT, "type-coverage patch: Cache write made atomic\n");
        } else {
            fwrite(STDOUT, "type-coverage patch: Cache.php shape changed, skip atomic write patch\n");
        }
    }
}

// Always clear potentially corrupt cache after patching / installs.
$tempDir = $root.'/vendor/pestphp/pest-plugin-type-coverage/.temp';
if (is_dir($tempDir)) {
    foreach (glob($tempDir.'/v3.php*') ?: [] as $file) {
        @unlink($file);
    }
}

exit(0);
