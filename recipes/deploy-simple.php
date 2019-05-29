<?php
/**
 * A Deployer recipe to be deploy WordPress sites with rsync (without atomic deploy/release_paths)
 * For more Information see README.md
 */

namespace Deployer;

require_once __DIR__ . '/vendor/autoload.php';

require_once 'recipe/common.php';
require_once 'tasks/simple.php'; // overwrite some default deployer tasks for simple usage

require_once 'set.php';
require_once 'tasks/themes.php';
require_once 'tasks/mu-plugins.php';
require_once 'tasks/database.php';
require_once 'tasks/files.php'; // required uplods, plugins & wp functions


// CONFIGURATION
// see src/set.php for other options to overwrite
// and https://deployer.org/docs/configuration.html for default configuration

set('keep_releases', 3);
set('release_name', function () {
    return date('YmdHis'); // you could also use the composer.json version here
});


// hosts
inventory('hosts-simple.example.yml');

// use localhost host to configure some local paths
localhost()
    ->stage('dev')
    ->set('public_url', 'http://example.local')
    ->set('dump_path', 'data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups')
    ->set('release_path', __DIR__) // simulate release path as main directory
    ->set('document_root', __DIR__);

// overwrite path to uploads dir which normally sits in shared folder
set('uploads/path', '{{release_path}}');

// custom theme & mu-plugin options
set('theme/name', 'THEME');
set('mu-plugin/name', 'core-functionality');

// TASKS

// Overwrite deployment with rsync (instead of git)
// Just set release_path to document_root in hosts.yml/hosts config - so it will just pusth these files
task('deploy:update_code', ['wp:push', 'themes:push', 'mu-plugins:push', 'plugins:push']); // does not include uploads & database (see below)

// install theme vendors and run theme assets (npm) build script LOCAL
task('theme:assets:vendors')->local();
task('theme:assets:build')->local();
before('deploy:update_code', 'theme:assets');

// install theme vendors (composer) on server
after('deploy:update_code', 'theme:vendors'); // defined in tasks/theme.php

// install mu-plugin vendors after deploying (on remote host)
after('deploy:update_code', 'mu-plugin:vendors'); // defined in tasks/mu-plugin.php

// OPTIONAL: push all uploads to server
// after('deploy:update_code', 'files:uploads:push');

// OPTIONAL: push database to server
// after('deploy:update_code', 'db:push');

// MAIN TASK
// very similar to Deployer default deploy flow
// but without symlinks/shared/releases and some tasks are overwritten in tasks/simple.php
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:writable',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy WordPress Site');
after('deploy', 'success');
