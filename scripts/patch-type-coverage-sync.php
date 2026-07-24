<?php

declare(strict_types=1);

/**
 * Make pest-plugin-type-coverage CI-safe against Pokio multi-fork cache races.
 *
 * Symptom without patch:
 *   ParseError in vendor/pestphp/pest-plugin-type-coverage/.temp/v3.php
 *   (");rray (" from concurrent writers)
 */

$root = dirname(__DIR__);
$analyserPath = $root.'/vendor/pestphp/pest-plugin-type-coverage/src/Analyser.php';
$cachePath = $root.'/vendor/pestphp/pest-plugin-type-coverage/src/Support/Cache.php';
$marker = 'MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC';

if (! is_file($analyserPath)) {
    fwrite(STDOUT, "type-coverage patch: plugin not installed, skip\n");
    exit(0);
}

$analyser = file_get_contents($analyserPath);
if ($analyser === false) {
    fwrite(STDERR, "type-coverage patch: cannot read Analyser.php\n");
    exit(1);
}

if (! str_contains($analyser, $marker)) {
    $toMax = <<<'PHP'
        // MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC
        $maxProcesses = 1;
PHP;

    // pest-plugin-type-coverage shapes:
    // - v4.0.0–v4.0.1: always divides maxProcesses by 3
    // - v4.0.2+: gates on supportsFork / __PEST_PLUGIN_ENV
    $fromMaxCandidates = [
        <<<'PHP'
        $maxProcesses = (Environment::supportsFork() && ! isset($_ENV['__PEST_PLUGIN_ENV']))
            ? (Environment::maxProcesses() / 3)
            : 1;
PHP,
        <<<'PHP'
        $maxProcesses = Environment::maxProcesses() / 3;
PHP,
    ];

    $fromAsync = <<<'PHP'
        if ($useAsync === false) {
            pokio()->useSync();
        } else {
            if (Environment::supportsFork() && ! isset($_ENV['__PEST_PLUGIN_ENV'])) {
                pokio()->useFork();
            }
        }
PHP;

    $toAsync = <<<'PHP'
        // MAILMANAGER_FORCE_TYPE_COVERAGE_SYNC
        pokio()->useSync();
PHP;

    $matchedMax = null;
    foreach ($fromMaxCandidates as $fromMax) {
        if (str_contains($analyser, $fromMax)) {
            $matchedMax = $fromMax;
            break;
        }
    }

    if ($matchedMax === null || ! str_contains($analyser, $fromAsync)) {
        fwrite(STDERR, "type-coverage patch: Analyser.php upstream shape changed; cannot patch\n");
        exit(1);
    }

    $analyser = str_replace($matchedMax, $toMax, $analyser);
    $analyser = str_replace($fromAsync, $toAsync, $analyser);

    if (file_put_contents($analyserPath, $analyser) === false) {
        fwrite(STDERR, "type-coverage patch: failed writing Analyser.php\n");
        exit(1);
    }

    fwrite(STDOUT, "type-coverage patch: Analyser forced to sync/single-process\n");
} else {
    fwrite(STDOUT, "type-coverage patch: Analyser already patched\n");
}

if (is_file($cachePath)) {
    $cache = file_get_contents($cachePath);
    if ($cache !== false && ! str_contains($cache, 'MAILMANAGER_SAFE_TYPE_COVERAGE_CACHE')) {
        $fromInclude = <<<'PHP'
            $cache = include $this->file();

            return is_array($cache) ? $cache : [];
PHP;

        $toInclude = <<<'PHP'
            // MAILMANAGER_SAFE_TYPE_COVERAGE_CACHE
            try {
                $cache = include $this->file();
            } catch (\Throwable) {
                @unlink($this->file());

                return [];
            }

            return is_array($cache) ? $cache : [];
PHP;

        // Only the all() method's include (first occurrence) needs the try/catch.
        // persist() also includes — patch both via replace_all for safety.
        if (str_contains($cache, $fromInclude)) {
            $cache = str_replace($fromInclude, $toInclude, $cache);
        }

        $fromPersistInclude = <<<'PHP'
            if (is_file($filePath)) {
                $existingCache = include $filePath;
                if (is_array($existingCache)) {
                    $cache = $existingCache;
                }
            }
PHP;

        $toPersistInclude = <<<'PHP'
            if (is_file($filePath)) {
                // MAILMANAGER_SAFE_TYPE_COVERAGE_CACHE
                try {
                    $existingCache = include $filePath;
                } catch (\Throwable) {
                    @unlink($filePath);
                    $existingCache = null;
                }
                if (is_array($existingCache)) {
                    $cache = $existingCache;
                }
            }
PHP;

        if (str_contains($cache, $fromPersistInclude)) {
            $cache = str_replace($fromPersistInclude, $toPersistInclude, $cache);
        }

        $fromWrite = <<<'PHP'
            $content = '<?php return '.var_export($cache, true).';';

            if (file_put_contents($filePath, $content) !== false) {
                chmod($filePath, 0666);
            }
PHP;

        $toWrite = <<<'PHP'
            // MAILMANAGER_ATOMIC_TYPE_COVERAGE_CACHE
            $content = '<?php return '.var_export($cache, true).';';
            $tempPath = $filePath.'.'.getmypid().'.tmp';

            if (file_put_contents($tempPath, $content) !== false) {
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

        if (str_contains($cache, $fromWrite)) {
            $cache = str_replace($fromWrite, $toWrite, $cache);
        }

        if (str_contains($cache, 'MAILMANAGER_SAFE_TYPE_COVERAGE_CACHE') || str_contains($cache, 'MAILMANAGER_ATOMIC_TYPE_COVERAGE_CACHE')) {
            if (file_put_contents($cachePath, $cache) === false) {
                fwrite(STDERR, "type-coverage patch: failed writing Cache.php\n");
                exit(1);
            }
            fwrite(STDOUT, "type-coverage patch: Cache hardened\n");
        } else {
            fwrite(STDOUT, "type-coverage patch: Cache.php unchanged (shape mismatch)\n");
        }
    } else {
        fwrite(STDOUT, "type-coverage patch: Cache already hardened\n");
    }
}

// Wipe cache artifacts.
$tempDir = $root.'/vendor/pestphp/pest-plugin-type-coverage/.temp';
if (is_dir($tempDir)) {
    foreach (glob($tempDir.'/*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

$verify = file_get_contents($analyserPath) ?: '';
if (! str_contains($verify, $marker)) {
    fwrite(STDERR, "type-coverage patch: verification failed\n");
    exit(1);
}

// Ensure useFork is not still invoked.
if (preg_match('/pokio\(\)->useFork\s*\(/', $verify) === 1) {
    fwrite(STDERR, "type-coverage patch: useFork() still present after patch\n");
    exit(1);
}

php_lint:
foreach ([$analyserPath, $cachePath] as $file) {
    if (! is_file($file)) {
        continue;
    }
    $out = [];
    $code = 0;
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "type-coverage patch: php -l failed for {$file}\n".implode("\n", $out)."\n");
        exit(1);
    }
}

exit(0);
