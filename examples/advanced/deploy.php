<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/gaambo/deployer-wordpress/recipes/advanced.php';

use function Deployer\after;
use function Deployer\import;
use function Deployer\localhost;
use function Deployer\task;
use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

// hosts & config
import('deploy.yml');

// OPTIONAL: overwrite localhost config'
localhost()
    ->set('public_url', "{{local_url}}")
    ->set('deploy_path', __DIR__)
    ->set('release_path', __DIR__ . '/public')
    // set current_path to hardcoded release_path on local so release_or_current_path works; {{release_path}} does not work here?
    ->set('current_path', function () {
        return getLocalhost()->get('release_path');
    })
    ->set('dump_path', __DIR__ . '/data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups');

/**
 * Example Deployment Configuration:
 */
// only push WordPress core files, themes, mu-plugins, plugins (not uploads, )
// task('deploy:update_code', ['wp:push', 'plugins:push', 'mu-plugins:push', 'themes:push'])
//     ->desc("Pushes updated code to target host");

// // install theme composer vendors composer on server
// after('deploy:update_code', 'theme:vendors');

// // install mu-plugin vendors after deploying (on remote host)
// after('deploy:update_code', 'mu-plugin:vendors');
