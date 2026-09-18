<?php

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('Deployer\Support\array_flatten')) {
    require __DIR__ . '/../vendor/deployer/deployer/src/Support/helpers.php';
}
if (!function_exists('Deployer\host')) {
    require __DIR__ . '/../vendor/deployer/deployer/src/functions.php';
}

set_include_path(__DIR__ . '/../vendor/deployer/deployer' . PATH_SEPARATOR . get_include_path());

putenv('DEPLOYER_LOCAL_WORKER=true');
define('__FIXTURES__', __DIR__ . '/Fixtures');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer-wordpress');

if (!file_exists(__TEMP_DIR__)) {
    mkdir(__TEMP_DIR__, 0755, true);
}
