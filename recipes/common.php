<?php

/**
 * All common tasks used for deployment
 * Based on deployer/recipe/common.php
 */

namespace Gaambo\DeployerWordpress\Recipes\Common;

use Deployer\Deployer;

use function Deployer\after;
use function Deployer\before;
use function Deployer\get;
use function Deployer\has;
use function Deployer\info;
use function Deployer\invoke;
use function Deployer\localhost;
use function Deployer\on;
use function Deployer\run;
use function Deployer\selectedHosts;
use function Deployer\task;

$deployerPath = 'vendor/deployer/deployer/';
require_once $deployerPath . 'recipe/common.php';

require_once 'set.php';
require_once 'tasks/database.php';
require_once 'tasks/files.php';
require_once 'tasks/mu-plugins.php';
require_once 'tasks/plugins.php';
require_once 'tasks/themes.php';
require_once 'tasks/uploads.php';
require_once 'tasks/wp.php';

// Overwrite deploy:info task to show host instead of branch
task('deploy:info', function () {
    $selectedHosts = selectedHosts();
    $hosts =  implode(',', array_map(function (\Deployer\Host\Host $host) {
        return $host->getAlias();
    }, $selectedHosts));
    info("deploying to <fg=magenta;options=bold>$hosts</>");
});

// Overwrite deploy:prepare to extract updating/pushing code to extra task
task('deploy:prepare', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release'
])->desc('Prepares a new release');

// Overwrite deployment with rsync (instead of git)
Deployer::get()->tasks->remove('deploy:check_remote');
Deployer::get()->tasks->remove('deploy:update_code');
task('deploy:update_code', ['files:push'])
    ->desc('Pushes local code to the remote hosts');

// Overwrite deploy:publish task to include writing shared dirs and writeable dirs and not include symlink by default
task('deploy:publish', [
    'deploy:shared',
    'deploy:writable',
    'cache:clear',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
])->desc('Publishes the release');

// Complete deploy task which includes preparation, pushing code and publishing
task('deploy', [
    'deploy:prepare',
    'deploy:update_code',
    'deploy:publish',
])->desc('Deploy WordPress project');

// If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// build theme assets via npm locally
before('deploy:update_code', function () {
    on(localhost(), function () {
        if (!has('theme/name')) {
            invoke('theme:assets:vendors');
            invoke('theme:assets:build');
        }
    });
});

/**
 * Clears cache via cli
 * eg via WP Rocket WP CLI command
 * @todo overwrite in your deploy file
 */
task('cache:clear', function () {
    // TODO: overwrite, maybe clear cache via wpcli
    // run("cd {{release_or_current_path}} && {{bin/wp}} rocket clean --confirm");
});

/**
 * CHOWN files to http_user and set file permissions according to WP best practices
 */
after('deploy:writable', function () {
    if (has('http_user')) {
        run("cd {{release_or_current_path}} && chown -R {{http_user}} .");
    }
    run("cd {{release_or_current_path}} && find . -type d -exec chmod 755 {} \;");
    run("cd {{release_or_current_path}} && find . -type f -exec chmod 644 {} \;");

    $configFiles = get('wp/configFiles');
    foreach ((array)$configFiles as $configFile) {
        run("cd {{release_or_current_path}} && chmod 600 $configFile");
    }
});
