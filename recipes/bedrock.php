<?php

/**
 * An Deployer recipe to be used with Roots Bedrock WordPress installations
 * Which pushes code into a hardcoded release_path (no directories per release and no symlinks).
 * For more Information see README.md
 */

namespace Gaambo\DeployerWordpress\Recipes\Bedrock;

use function Deployer\add;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;

require_once __DIR__ . '/common.php';

add('recipes', ['bedrock-wp']);

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

// Tasks
task('app:push',function() {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray();
    run("mkdir -p {{release_or_current_path}}");
    upload([
        'config',
        'scripts',
        'composer.json',
        'composer.lock',
        '.env.example',
        'wp-cli.yml',
    ], "{{release_or_current_path}}", ['options' => $rsyncOptions]);
    upload([
        // Keep prod .htaccess with webp redirects + redirection redirects + wprocket
        // 'web/.htaccess',
        'web/index.php',
        'web/wp-config.php',
    ], "{{release_or_current_path}}/web", ['options' => $rsyncOptions]);
    upload([
        'web/app/mu-plugins/bedrock-autoloader.php',
    ], "{{release_or_current_path}}/{{mu-plugins/dir}}", ['options' => $rsyncOptions]);
});

task('deploy:update_code', [
    'app:push',
    'packages:push',
])->desc('Pushes local bedrock app and packages to the remote hosts');

// install vendors after deploying (on remote host)
after(
	'deploy:update_code',
	function () {
		\Gaambo\DeployerWordpress\Utils\Composer\runDefault(
			'{{release_or_current_path}}'
		);
	}
);

task('deploy', [
    'deploy:prepare',
    'deploy:build_assets',
    'deploy:update_code',
    'deploy:publish'
])->desc('Deploy WordPress Site');
