<?php
/**
 * A Deployer recipe to be used with vanilla WordPress installations (with a normal WP installation = not Bedrock/Cobblestone)
 * For more Information see README.md
 */

namespace Deployer;

require_once __DIR__ . '/vendor/autoload.php';

require_once 'recipe/common.php';

require_once 'set.php';
require_once 'tasks/themes.php';
require_once 'tasks/mu-plugins.php';
require_once 'tasks/database.php';
require_once 'tasks/files.php'; // required uplods, plugins & wp functions

// CONFIGURATION
// see README.md and src/set.php for other options to overwrite
// and https://deployer.org/docs/configuration.html for default configuration

set('keep_releases', 3);
set('release_name', function () {
    return date('YmdHis'); // you could also use the composer.json version here
});


// hosts
inventory('hosts.example.yml'); // !!!PLEASE EDIT!!!

// use localhost host to configure some local paths
localhost()
    ->stage('dev')
    ->set('public_url', 'http://example.local') // !!!PLEASE EDIT!!!
    ->set('dump_path', 'data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups')
    ->set('release_path', __DIR__)
    ->set('document_root', __DIR__);

// custom theme & mu-plugin options
set('theme/name', 'THEME'); // !!!PLEASE EDIT!!!
set('mu-plugin/name', 'core-functionality'); // !!!PLEASE EDIT!!!

// TASKS

// Overwrite deployment with rsync (instead of git)
task('deploy:update_code', ['wp:push', 'themes:push', 'mu-plugins:push', 'plugins:push']); // does not include uploads & database (see below)

// install theme vendors and run theme assets (npm) build script LOCAL
// set theme:assets tasks to run local
task('theme:assets:vendors')->local();
task('theme:assets:build')->local();
before('deploy:update_code', 'theme:assets');

// install theme vendors (composer) on server
after('deploy:update_code', 'theme:vendors'); // defined in tasks/theme.php

// install mu-plugin vendors after deploying (on remote host)
after('deploy:update_code', 'mu-plugin:vendors'); // defined in tasks/mu-plugin.php

// OPTIONAL: push all uploads to server
// after('deploy:update_code', 'uploads:push');

// OPTIONAL: push database to server, have to wait for wp-config-local.php to be symlinked from shared
// after('deploy:shared', 'db:push');

// MAIN TASK
// very similar to Deployer default deploy flow
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
])->desc('Deploy WordPress Site');
after('deploy', 'success');
