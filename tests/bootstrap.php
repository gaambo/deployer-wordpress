<?php

require __DIR__ . '/../vendor/autoload.php';

// Somehow deployer removes its autoload after releasing.
// see https://github.com/deployphp/deployer/commit/76fadcd887eb22e37ffe92c5e05964ce43c9cfe5
// And also add Deployer PSR-4 autoload-dev to composer.json
require __DIR__ . '/../vendor/deployer/deployer/src/functions.php';
require __DIR__ . '/../vendor/deployer/deployer/src/Support/helpers.php';

set_include_path(__DIR__ . '/../vendor/deployer/deployer' . PATH_SEPARATOR . get_include_path());

// Set up test environment
putenv('DEPLOYER_LOCAL_WORKER=true');
define('__FIXTURES__', __DIR__ . '/fixtures');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer-wordpress');

// Create temp directory if it doesn't exist
if (!file_exists(__TEMP_DIR__)) {
    mkdir(__TEMP_DIR__, 0755, true);
}