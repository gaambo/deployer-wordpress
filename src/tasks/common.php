<?php

/**
 * Contains overwrites of Deployer core tasks to handle deployment with simple recipe (--> no release directories,...)
 * See README.MD for more information
 */

namespace Deployer;

$deployerPath = 'vendor/deployer/deployer/';
require $deployerPath . 'recipe/deploy/cleanup.php';
require $deployerPath . 'recipe/deploy/lock.php';
require $deployerPath . 'recipe/deploy/shared.php';

/**
 * Overwrite deploy:prepare to not create release and other directories
 */
task('deploy:prepare', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:log'
])->desc('Prepares a new release');

/**
 * Overwrite deploy:publish after pushing code
 */
task('deploy:publish', [
    'deploy:shared',
    'deploy:writable',
    'deploy:unlock',
    'deploy:cleanup',
    'cache:clear',
    'deploy:success'
])->desc("Publishes the release");

task('deploy:info', function () {
    info("deploying to <fg=magenta;options=bold>{{alias}} ({{hostname}})</>");
})->desc('Displays info about deployment');

task('deploy:setup', function () {
    run('if [ ! -d {{deploy_path}} ]; then mkdir -p {{deploy_path}}; fi');

    // Create metadata .dep dir.
    run("cd {{deploy_path}} && if [ ! -d .dep ]; then mkdir .dep; fi");
})->desc("Prepares host for deploy");

/**
 * Logs the release
 */
task('deploy:log', function () {
    cd('{{log_path}}');

    $releaseName = get('release_name');

    // Save release_name.
    if (is_numeric($releaseName) && is_integer(intval($releaseName))) {
        run("echo $releaseName > latest_release");
    }

    // Metainfo.
    $timestamp = timestamp();
    $metainfo = [
        'created_at' => $timestamp,
        'release_name' => $releaseName,
        'user' => get('user'),
    ];

    // Save metainfo about release.
    $json = json_encode($metainfo);
    run("echo '$json' >> releases");
})->desc('Prepare release');

// CHOWN files with http_user and set file permissions according to WP best practices
task('deploy:writable', function () {
    if (has('http_user')) {
        run("cd {{release_path}} && chown -R {{http_user}} .");
    }
    run("cd {{release_path}} && find . -type d -exec chmod 755 {} \;");
    run("cd {{release_path}} && find . -type f -exec chmod 644 {} \;");
});

/**
 * Overwrite deploy:cleanup task
 * @todo maybe add things to cleanup
 */
task('deploy:cleanup', function () {
})->desc('Cleaning up old releases');

/**
 * Clears cache via cli
 * eg via WP Rocket WP CLI command
 * @todo overwrite in your deploy file
 */
task('cache:clear', function () {
    // TODO: overwrite, maybe clear cache via wpcli
    // $remotePath = getRemotePath();
    // run("cd $remotePath && {{bin/wp}} rocket clean --confirm");
});

/**
 * Prints success message
 */
task('deploy:success', function () {
    info('successfully deployed!');
})->hidden();

/**
 * Hook on deploy failure.
 */
task('deploy:failed', function () {
})->hidden();
