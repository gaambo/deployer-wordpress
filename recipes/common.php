<?php

/**
 * All common tasks used for deployment
 * Based on deployer/recipe/common.php
 */

namespace Gaambo\DeployerWordpress\Recipes\Common;

use Deployer\Deployer;
use Deployer\Host\Host;
use Gaambo\DeployerWordpress\Composer;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\WPCLI;

use function Deployer\after;
use function Deployer\commandExist;
use function Deployer\currentHost;
use function Deployer\get;
use function Deployer\has;
use function Deployer\info;
use function Deployer\invoke;
use function Deployer\on;
use function Deployer\output;
use function Deployer\run;
use function Deployer\selectedHosts;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\warning;
use function Deployer\which;

// Deployer binary sets the include path, so this should work.

$commonRecipePaths = [
    __DIR__ . '/../vendor/deployer/deployer/recipe/common.php', // Development/testing
    __DIR__ . '/../deployer/deployer/recipe/common.php' // Installed via composer
];

foreach ($commonRecipePaths as $recipePath) {
    if (file_exists($recipePath)) {
        require $recipePath;
        break;
    }
}

// Include task definitions
require __DIR__ . '/../tasks/database.php';
require __DIR__ . '/../tasks/files.php';
require __DIR__ . '/../tasks/languages.php';
require __DIR__ . '/../tasks/mu-plugins.php';
require __DIR__ . '/../tasks/packages.php';
require __DIR__ . '/../tasks/plugins.php';
require __DIR__ . '/../tasks/themes.php';
require __DIR__ . '/../tasks/uploads.php';
require __DIR__ . '/../tasks/wp.php';

// BINARIES
set('bin/npm', function () {
    return which('npm');
});

// Path to the `php` bin.
set('bin/php', function () {
    if (currentHost()->hasOwn('php_version')) {
        return '/usr/bin/php{{php_version}}';
    }
    return which('php');
});

// can be overwritten if you eg. use wpcli in a docker container
set('bin/wp', function () {
    $installPath = '{{deploy_path}}/.dep';
    $binaryFile = 'wp-cli.phar';

    if (test("[ -f $installPath/$binaryFile ]")) {
        return "{{bin/php}} $installPath/$binaryFile";
    }

    if (commandExist('wp')) {
        return '{{bin/php}} ' . which('wp');
    }

    warning("WP-CLI binary wasn't found. Installing latest WP-CLI to $installPath/$binaryFile.");
    WPCLI::install($installPath, $binaryFile);
    return "{{bin/php}} $installPath/$binaryFile";
});

set('composer_action', 'install');
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

// Returns Composer binary path in found. Otherwise, try to install latest
// composer version to `.dep/composer.phar`. To use specific composer version
// download desired phar and place it at `.dep/composer.phar`.
set('bin/composer', function () {
    $installPath = '{{deploy_path}}/.dep';
    $binaryFile = 'composer.phar';

    if (test("[ -f $installPath/$binaryFile ]")) {
        return "{{bin/php}} $installPath/$binaryFile";
    }

    if (commandExist('composer')) {
        return '{{bin/php}} ' . which('composer');
    }

    warning("Composer binary wasn't found. Installing latest composer to $installPath/$binaryFile.");
    Composer::install($installPath, $binaryFile);
    return "{{bin/php}} $installPath/$binaryFile";
});

// PATHS & FILES CONFIGURATION

// Use fixed release_path always
set('release_or_current_path', function () {
    return '{{release_path}}'; // Do not use get() to stay in same context.
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

// if you want to further define options for rsyncing files
// just look at the source in `Files.php` and `Rsync.php`
// and use the Rsync::buildOptionsArray and Files::push/pull methods
set('wp/dir', ''); // relative to document root
// config files which should be protected - add to shared_files as well
set('wp/configFiles', ['wp-config.php', 'wp-config-local.php']);
// set all wp-config files to 600 - which means plugins/WordPress can modify it
// alternative set it to 400 to disallow edits via WordPress
set('wp/configFiles/permissions', '600');
set('wp/filter', [ // Contains all WordPress core files excluding uploads, themes, plugins, mu-plugins, languages.
    '+ /wp-content/',
    '- /wp-content/mu-plugins/*',
    '- /wp-content/plugins/*',
    '- /wp-content/themes/*',
    '- /wp-content/uploads/*',
    '- /wp-content/languages/*',
    '- /wp-content/upgrade',
    '- /wp-content/cache',
    '+ /wp-content/**', // all other files in wp-content
    '+ /wp-admin/',
    '+ /wp-admin/**',
    '+ /wp-includes/',
    '+ /wp-includes/**',
    '+ wp-activate.php',
    '+ wp-blog-header.php',
    '+ wp-comments-post.php',
    '+ wp-config-sample.php',
    '+ wp-config.php',
    '- wp-config-local.php', // should be required in wp-config.php
    '+ wp-cron.php',
    '+ wp-links-opml.php',
    '+ wp-load.php',
    '+ wp-login.php',
    '+ wp-mail.php',
    '+ wp-settings.php',
    '+ wp-signup.php',
    '+ wp-trackback.php',
    '+ xmlrpc.php',
    '+ index.php',
    '- *'
]);
set('uploads/dir', 'wp-content/uploads'); // relative to document root
set('uploads/path', '{{release_or_current_path}}'); // path in front of uploads directory
set('uploads/filter', []); // rsync filter syntax
set('mu-plugins/dir', 'wp-content/mu-plugins'); // relative to document root
set('mu-plugins/filter', []); // rsync filter syntax
set('plugins/dir', 'wp-content/plugins'); // relative to document root
set('plugins/filter', []); // rsync filter syntax
set('themes/dir', 'wp-content/themes'); // relative to document root
set('themes/filter', []); // rsync filter syntax
set('theme/build_script', 'build'); // custom theme npm build script
set('languages/dir', 'wp-content/languages'); // relative to document root
set('languages/filter', []); // rsync filter syntax

// options for zipping files for backups - passed to zip shell command
set('zip_options', '-x "_backup_*.zip" -x **/node_modules/**\* -x **/vendor/**\*');

// SHARED FILES
set('shared_files', ['wp-config.php', 'wp-config-local.php']);
set('shared_dirs', ['{{uploads/dir}}']);
set('writable_dirs', ['{{uploads/dir}}']);

// The default rsync config
// used by all *:push/*:pull tasks and in `src/utils/rsync.php:buildOptionsArray`
set('rsync', function () {
    $config = [
        'exclude'      => [], // do NOT exclude .deployfilter files - remote should be aware of them
        'exclude-file' => false,
        'include'      => [],
        'include-file' => false,
        'filter'       => [],
        'filter-file'  => false,
        // Allows specifying (=excluding/including/filtering) files to sync per directory in a `.deployfilter` file
        // See README directory for examples
        'filter-perdir' => '.deployfilter',
        'flags'        => 'rz', // Recursive, with compress
        'options'      => ['delete-after'], // needed so deployfilter files are send and delete is checked afterward
        'timeout'      => 60,
        'progress_bar' => true,
    ];

    if (output()->isVerbose()) {
        $config['options'][] = 'verbose';
    }
    if (output()->isVeryVerbose()) {
        $config['options'][] = 'verbose';
    }
    if (output()->isDebug()) {
        $config['options'][] = 'verbose';
    }

    return $config;
});
// https://github.com/deployphp/deployer/issues/3139
set('rsync_src', __DIR__);

set('release_name', function () {
    return date('YmdHis'); // you could also use the composer.json version here
});

set('writable_mode', 'chown');

// Overwrite deploy:info task to show host instead of branch
task('deploy:info', function () {
    $selectedHosts = selectedHosts();
    $hosts =  implode(',', array_map(function (Host $host) {
        return $host->getAlias();
    }, $selectedHosts));
    info("deploying to <fg=magenta;options=bold>$hosts</>");
});

// Overwrite deploy:prepare to extract updating/pushing code to extra task
task('deploy:prepare', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release'
])->desc('Prepares a new release');

// Build package assets via npm locally
task('deploy:build_assets', function () {
    on(Localhost::get(), function () {
        if (has('packages')) {
            invoke('packages:assets:vendors');
            invoke('packages:assets:build');
        }
    });
})->once();

// Overwrite deployment with rsync (instead of git)
Deployer::get()->tasks->remove('deploy:check_remote');
Deployer::get()->tasks->remove('deploy:update_code');
// Push all files (incl 'wp:push', 'uploads:push', 'plugins:push', 'mu-plugins:push', 'themes:push', 'packages:push')
task('deploy:update_code', ['files:push'])
    ->desc('Pushes local code to the remote hosts');

// Overwrite deploy:publish task to include writing shared dirs and writeable dirs and not include symlink by default
task('deploy:publish', [
    'deploy:shared',
    'deploy:writable',
    'cache:clear',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:success',
])->desc('Publishes the release');

// Complete deploy task which includes preparation, pushing code and publishing
task('deploy', [
    'deploy:prepare',
    'deploy:build_assets',
    'deploy:update_code',
    'deploy:publish',
])->desc('Deploy WordPress project');

// If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

/**
 * Clears cache via cli
 * eg via WP Rocket WP CLI command
 * @todo overwrite in your deploy file
 */
task('cache:clear', function () {
    // TODO: overwrite, maybe clear cache via wpcli
    // WPCLI::runCommand("rocket clean --confirm", "{{release_or_current_path}}");
    // WPCLI::runCommand("cache flush", "{{release_or_current_path}}");
});

/**
 * Overwrite deploy:writable to use chmod always and
 * CHOWN files to http_user and set file permissions according to WP best practices
 *
 * Does not support writable_mode configuration - always uses this
 */
task('deploy:writable', function () {
    if (has('http_user') && get('writable_mode') === 'chown') {
        run("cd {{release_or_current_path}} && chown -R {{http_user}} .");
    }
    // set all directories to 755
    run("cd {{release_or_current_path}} && find . -type d -exec chmod 755 {} \;");
    run("cd {{deploy_path}}/shared && find . -type d -exec chmod 755 {} \;"); // also do for shared files
    // set all files to 644
    run("cd {{release_or_current_path}} && find . -type f -exec chmod 644 {} \;");
    run("cd {{deploy_path}}/shared && find . -type f -exec chmod 644 {} \;"); // also do for shared files

    // set all files to 600 (so they can be modified by you/wordpress)
    $configFiles = get('wp/configFiles');
    foreach ((array)$configFiles as $configFile) {
        if (test("[ -f {{release_or_current_path}}/$configFile ]")) {
            run("chmod {{wp/configFiles/permissions}} {{release_or_current_path}}/$configFile");
        }
        // wpconfig files could also be in shared folder
        if (test("[ -f {{deploy_path}}/shared/$configFile ]")) {
            run("chmod {{wp/configFiles/permissions}} {{deploy_path}}/shared/$configFile");
        }
    }
});
