<?php

/**
 * An simple Deployer recipe to be used with vanilla WordPress installations
 * Which pushes code into a hardcoded release_path (no directories per release and no symlinks).
 * For more Information see README.md
 */

namespace Gaambo\DeployerWordpress\Recipes\Advanced;

use function Deployer\add;
use function Deployer\task;

require_once __DIR__ . '/common.php';

add('recipes', ['simple-wp']);

task('deploy', [
    'deploy:prepare',
    'deploy:update_code',
    'deploy:symlink', // Add additional symlink task
    'deploy:publish'
])->desc('Deploy WordPress Site');

add('recipes', ['base']);
