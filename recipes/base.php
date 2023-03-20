<?php

/**
 * A Deployer recipe to be used with vanilla WordPress installations (with a normal WP installation = not Bedrock/Cobblestone)
 * For more Information see README.md
 */

namespace Deployer;

require_once 'set.php';
require_once 'tasks/common.php';
require_once 'tasks/themes.php';
require_once 'tasks/mu-plugins.php';
require_once 'tasks/database.php';
require_once 'tasks/files.php'; // required uplods, plugins & wp functions

/**
 * CONFIGURATION
 * see README.md and src/set.php for other options to overwrite
 * and https://deployer.org/docs/configuration.html for default configuration
 */

// use localhost host to configure some local paths
localhost()
    ->set('labels', ['stage' => 'dev'])
    ->set('public_url', "{{local_url}}")
    ->set('dump_path', 'data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups')
    ->set('release_path', __DIR__)
    ->set('deploy_path', __DIR__)
    ->set('document_root', __DIR__);

/**
 * TASKS
 */

// Overwrite deployment with rsync (instead of git)
task('deploy:push_code', [
    'wp:push',
    'themes:push',
    'mu-plugins:push',
    'plugins:push',
    // 'uploads:push', // OPTIONAL: push all uploads to server
    // 'db:push', // OPTIONAL: push database to server
])->desc("Pushes updated code to target host");

// MAIN TASK
// very similar to Deployer default deploy flow
fail('deploy', 'deploy:failed');

task('deploy', [
    'deploy:prepare',
    'deploy:push_code',
    'deploy:publish'
])->desc('Deploy WordPress Site');
