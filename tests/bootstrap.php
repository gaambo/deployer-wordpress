<?php

require __DIR__ . '/../vendor/autoload.php';

// Somehow deployer removes its autoload after releasing.
// see https://github.com/deployphp/deployer/commit/76fadcd887eb22e37ffe92c5e05964ce43c9cfe5
// And also add Deployer PSR-4 autoload-dev to composer.json
// These are automatically loaded by Deployer when running the dep binary.
// In deployer v8 these are added as autoload-files, therefore load conditionally.
if(!function_exists('Deployer\Support\array_flatten')){
    require __DIR__ . '/../vendor/deployer/deployer/src/Support/helpers.php';
}
if(!function_exists('Deployer\host')) {
    require __DIR__ . '/../vendor/deployer/deployer/src/functions.php';
}

set_include_path(__DIR__ . '/../vendor/deployer/deployer' . PATH_SEPARATOR . get_include_path());

// Deployer v7/v8 compat: alias moved/renamed classes under their v7 names so test files
// can use a single import that works with both versions.
// ProcessRunner: v7 Deployer\Component\ProcessRunner\ProcessRunner → v8 Deployer\ProcessRunner\ProcessRunner
if (!class_exists('Deployer\Component\ProcessRunner\ProcessRunner') && class_exists('Deployer\ProcessRunner\ProcessRunner')) {
    class_alias('Deployer\ProcessRunner\ProcessRunner', 'Deployer\Component\ProcessRunner\ProcessRunner');
}
// Ssh Client: v7 Deployer\Component\Ssh\Client → v8 Deployer\Ssh\SshClient (renamed + moved)
if (!class_exists('Deployer\Component\Ssh\Client') && class_exists('Deployer\Ssh\SshClient')) {
    class_alias('Deployer\Ssh\SshClient', 'Deployer\Component\Ssh\Client');
}

// Set up test environment
putenv('DEPLOYER_LOCAL_WORKER=true');
define('__FIXTURES__', __DIR__ . '/Fixtures');
define('__TEMP_DIR__', sys_get_temp_dir() . '/deployer-wordpress');

// Create temp directory if it doesn't exist
if (!file_exists(__TEMP_DIR__)) {
    mkdir(__TEMP_DIR__, 0755, true);
}
