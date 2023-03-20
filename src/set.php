<?php

/**
 * Sets some default configuration required for all tasks.
 * All of them can be overwritten.
 */

namespace Deployer;

use function Gaambo\DeployerWordpress\Utils\WPCLI\getWPCLIBinary;
use function \Gaambo\DeployerWordpress\Utils\WPCLI\installWPCLI;

require_once 'utils/localhost.php';
require_once 'utils/wp-cli.php';

// BINARIES
set('bin/npm', function () {
    return which('npm');
});

// can be overwritten if you eg. use wpcli in a docker container
set('bin/wp', function () {
    if ($path = getWPCLIBinary()) {
        return $path;
    }

    $installPath = '{{deploy_path}}/.dep';
    $binaryFile = 'wp-cli.phar';

    writeln("WP-CLI binary wasn't found. Installing latest wp-cli to \"$installPath/$binaryFile\".");

    installWPCLI($installPath, $binaryFile);
});

set('bin/composer', function () {
    return which('composer');
});

set('composer_options', 'install --no-dev');

// PATHS & FILES CONFIGURATION

// if you want to further define options for rsyncing files
// just look at the source in `files.php` and use the Rsync\buildConfig, Files\pushFiles and Files\pullFiles utils methods
set('wp/dir', ''); // relative to document root
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
set('uploads/path', '{{deploy_path}}'); // path in front of uploads directory
set('uploads/filter', []); // rsync filter syntax
set('mu-plugins/dir', 'wp-content/mu-plugins'); // relative to document root
set('mu-plugins/filter', []); // rsync filter syntax
set('plugins/dir', 'wp-content/plugins'); // relative to document root
set('plugins/filter', []); // rsync filter syntax
set('themes/dir', 'wp-content/themes'); // relative to document root
set('themes/filter', []); // rsync filter syntax
set('theme/build_script', 'build'); // custom theme npm build script

set('document_root', function () {
    // default to local document_root to be used if no host context is present
    $localPath = \Gaambo\DeployerWordpress\Utils\Localhost\getLocalhostConfig('document_root');
    return $localPath;
});

// options for zipping files for backups - passed to zip shell command
set('zip_options', '-x "_backup_*.zip" -x **/node_modules/**\* -x **/vendor/**\*');

// SHARED FILES
set('shared_files', ['wp-config-local.php']);
set('shared_dirs', [get('uploads/dir')]);
set('writable_dirs', [get('uploads/dir')]);

// URLS
set('remote_url', '{{public_url}}'); // public_url must be set on host

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

/**
 * Taken and adapted from deployer/recipe/common.php
 */
// Name of current user who is running deploy.
// If not set will try automatically get git user name,
// otherwise output of `whoami` command.
set('user', function () {
    if (getenv('CI') !== false) {
        return 'ci';
    }

    try {
        return runLocally('git config --get user.name');
    } catch (RunException $exception) {
        try {
            return runLocally('whoami');
        } catch (RunException $exception) {
            return 'no_user';
        }
    }
});

// Default timeout for `run()` and `runLocally()` functions.
//
// Set to `null` to disable timeout.
set('default_timeout', 300);

/**
 * Remote environment variables.
 * ```php
 * set('env', [
 *     'KEY' => 'something',
 * ]);
 * ```
 *
 * It is possible to override it per `run()` call.
 *
 * ```php
 * run('echo $KEY', env: ['KEY' => 'over']);
 * ```
 */
set('env', []);

/**
 * Path to `.env` file which will be used as environment variables for each command per `run()`.
 *
 * ```php
 * set('dotenv', '{{current_path}}/.env');
 * ```
 */
set('dotenv', false);

// Path to the `php` bin.
set('bin/php', function () {
    if (currentHost()->hasOwn('php_version')) {
        return '/usr/bin/php{{php_version}}';
    }
    return which('php');
});
