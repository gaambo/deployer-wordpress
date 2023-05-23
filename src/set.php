<?php

/**
 * Sets some default configuration required for all tasks.
 * All of them can be overwritten.
 */

namespace Deployer;

use function Gaambo\DeployerWordpress\Utils\Composer\installComposer;
use function \Gaambo\DeployerWordpress\Utils\WPCLI\installWPCLI;

require_once 'utils/wp-cli.php';

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
    installWPCLI($installPath, $binaryFile);
    return "{{bin/php}} $installPath/$binaryFile";
});

set('composer_action', 'install');
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

// Returns Composer binary path in found. Otherwise try to install latest
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
    installComposer($installPath, $binaryFile);
    return "{{bin/php}} $installPath/$binaryFile";
});

// PATHS & FILES CONFIGURATION

// if you want to further define options for rsyncing files
// just look at the source in `files.php` and use the Rsync\buildConfig, Files\pushFiles and Files\pullFiles utils methods
set('wp/dir', ''); // relative to document root
set('wp/configFiles', ['wp-config.php', 'wp-config-local.php']); // config files which should be protected
set('wp/filter', [ // contains all wordpress core files excluding uploads, themes, plugins, mu-plugins
    '+ /wp-content/',
    '- /wp-content/mu-plugins/*',
    '- /wp-content/plugins/*',
    '- /wp-content/themes/*',
    '- /wp-content/uploads/*',
    '- /wp-content/upgrade',
    '- /wp-content/cache',
    '+ /wp-content/**', // all other files in wp-content eg. languages
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

// options for zipping files for backups - passed to zip shell command
set('zip_options', '-x "_backup_*.zip" -x **/node_modules/**\* -x **/vendor/**\*');

// SHARED FILES
set('shared_files', ['wp-config-local.php']);
set('shared_dirs', [get('uploads/dir')]);
set('writable_dirs', [get('uploads/dir')]);

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
        // Allows specifying (=excluding/including/filtering) files to sync per directory in a `.deployfilter` file - See README directory for examples
        'filter-perdir' => '.deployfilter',
        'flags'        => 'rz', // Recursive, with compress
        'options'      => ['delete-after'], // needed so deployfilter files are send and delete is checked afterwards
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
