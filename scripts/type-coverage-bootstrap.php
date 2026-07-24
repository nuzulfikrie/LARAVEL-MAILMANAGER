<?php

declare(strict_types=1);

/**
 * Prepended into the Pest PHP process for type-coverage runs.
 *
 * pest-plugin-type-coverage only checks isset($_ENV['__PEST_PLUGIN_ENV']) to
 * avoid multi-process Pokio forks. putenv() alone is NOT enough when PHP is
 * built/configured with variables_order=GPCS (common on CI) because env vars
 * are never imported into $_ENV.
 */
$_ENV['__PEST_PLUGIN_ENV'] = '1';
$_SERVER['__PEST_PLUGIN_ENV'] = '1';
putenv('__PEST_PLUGIN_ENV=1');
