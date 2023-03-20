<?php

/**
 * A Deployer recipe to be used with vanilla WordPress installations
 * (with a normal WP installation = not Bedrock/Cobblestone)
 * For more Information see README.md
 */

namespace Deployer;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/gaambo/deployer-wordpress/recipes/base.php';

/**
 * CONFIGURATION
 * see README.md and src/set.php for other options to overwrite
 * and https://deployer.org/docs/configuration.html for default configuration
 */

// hosts & config
import('util/deploy.yml');

// OPTIONAL: overwrite localhost config'
localhost()
    ->set('dump_path', 'data/db_dumps')
    ->set('public_url', "{{local_url}}")
    ->set('backup_path', __DIR__ . '/data/backups')
    ->set('release_path', __DIR__ . '/public')
    ->set('deploy_path', __DIR__ . '/public')
    ->set('document_root', __DIR__ . '/public');

/**
 * TASKS
 */

// only push themes and mu-plugins
task('deploy:push_code', ['themes:push', 'mu-plugins:push'])
    ->desc("Pushes updated code to target host");

// build theme assets via npm locally
before('deploy:push_code', function () {
    on(localhost(), function () {
        invoke('theme:assets:vendors');
        invoke('theme:assets:build');
    });
});

// install theme vendors (composer) on server
// after('deploy:push_code', 'theme:vendors'); // defined in tasks/theme.php

// install mu-plugin vendors after deploying (on remote host)
after('deploy:push_code', 'mu-plugin:vendors'); // defined in tasks/mu-plugin.php


// MAIN TASK
// very similar to Deployer default deploy flow
task('deploy', [
    'deploy:prepare',
    'deploy:push_code',
    'deploy:publish'
])->desc('Deploy WordPress Site');
