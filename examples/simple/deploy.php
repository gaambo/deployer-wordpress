<?php

use Gaambo\DeployerWordpress\Localhost;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/gaambo/deployer-wordpress/recipes/simple.php';

use function Deployer\has;
use function Deployer\import;
use function Deployer\invoke;
use function Deployer\localhost;
use function Deployer\on;
use function Deployer\set;
use function Deployer\task;

// hosts & config
import('deploy.yml');

// OPTIONAL: overwrite localhost config.
localhost()
    ->set('public_url', "{{local_url}}")
    ->set('deploy_path', __DIR__)
    ->set('release_path', __DIR__ . '/public')
    // set current_path to hardcoded release_path on local so release_or_current_path works;
    // {{release_path}} does not work here?
    ->set('current_path', function () {
        return Localhost::getConfig('release_path');
    })
    ->set('dump_path', __DIR__ . '/data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups');

set('packages', [
    'theme' => [
        'path' => '{{themes/dir}}/custom-theme',
        'remote:path' => '{{themes/dir}}/custom-theme',
        'assets' => true,
        'assets:build_script' => 'build'
    ],
    'core-functionality' => [
        'path' => '{{mu-plugins/dir}}/core-functionality',
        'remote:path' => '{{mu-plugins/dir}}/core-functionality'
    ],
]);

// Build package assets via npm locally
task('deploy:build_assets', function () {
    on(Localhost::get(), function () {
        if (has('packages')) {
            // Do not install vendors on each deployment.
            // invoke('packages:assets:vendors');
            invoke('packages:assets:build');
        }
    });
})->once();
