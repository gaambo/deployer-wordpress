<?php

use Gaambo\DeployerWordpress\Localhost;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/gaambo/deployer-wordpress/recipes/bedrock.php';

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
    ->set('project_path', __DIR__)
    ->set('current_path', __DIR__)
    // Bedrock dirs
    ->set('uploads/path', '{{current_path}}') // Do not use shared directory for uploads.
    ->set('uploads/dir', 'web/app/uploads')
    ->set('mu-plugins/dir', 'web/app/mu-plugins')
    ->set('themes/dir', 'web/app/themes')
    ->set('plugins/dir', 'web/app/plugins')
    ->set('wp/dir', 'web/wp')
    ->set('dbdump_path', __DIR__ . '/data/db_dumps')
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
        'remote:path' => '{{mu-plugins/dir}}/core-functionality',
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
