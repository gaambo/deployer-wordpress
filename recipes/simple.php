<?php

/**
 * An simple Deployer recipe to be used with vanilla WordPress installations
 * Which pushes code into a hardcoded release_path (no directories per release and no symlinks).
 * For more Information see README.md
 */

namespace Gaambo\DeployerWordpress\Recipes\Simple;

use function Deployer\add;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;

require_once __DIR__ . '/common.php';

add('recipes', ['simple-wp']);

// Use fixed release_path always
set('release_or_current_path', function () {
    return get('release_path');
});

// Use a dummy current_path because deployer checks if it's a symlink
set('current_path', function () {
    if (test('[ ! -f {{deploy_path}}/.dep/current ]')) {
        run('{{bin/symlink}} {{release_path}} {{deploy_path}}/.dep/current');
    }
    return '{{deploy_path}}/.dep/current';
});

// Do not use shared dirs
set('shared_files', []);
set('shared_dirs', []);

task('deploy:update_code', ['packages:push'])
    ->desc('Pushes local packages to the remote hosts');

task('deploy', [
    'deploy:prepare',
    'deploy:build_assets',
    'deploy:update_code',
    'deploy:publish'
])->desc('Deploy WordPress Site');
