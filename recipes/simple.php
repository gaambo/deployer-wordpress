<?php

/**
 * A simple Deployer recipe to be used with vanilla WordPress installations
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

require __DIR__ . '/common.php';

add('recipes', ['simple-wp']);

task('deploy:update_code', ['packages:push'])
    ->desc('Pushes local packages to the remote hosts');

task('deploy', [
    'deploy:prepare',
    'deploy:build_assets',
    'deploy:update_code',
    'deploy:publish'
])->desc('Deploy WordPress Site');
