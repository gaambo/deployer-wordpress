<?php
/**
 * Sets some default configuration required for all tasks.
 * All of them can be overwritten.
 */

namespace Deployer;

use function \Gaambo\DeployerWordpress\Utils\WPCLI\installWPCLI;

require_once 'utils/localhost.php';
require_once 'utils/wp-cli.php';

// BINARIES
set('bin/npm', function () {
    return locateBinaryPath('npm');
});

// can be overwritten if you eg. use wpcli in a docker container
set('bin/wp', function () {
    if (commandExist('wp')) {
        return locateBinaryPath('wp');
    }

    $installPath = '{{deploy_path}}/.dep';
    $binaryFile = 'wp-cli.phar';

    if (test("[ -f $installPath/$binaryFile ]")) {
        return "{{bin/php}} $installPath/$binaryFile";
    }


    writeln("WP-CLI binary wasn't found. Installing latest wp-cli to \"$installPath/$binaryFile\".");
    installWPCLI($installPath, $binaryFile);
    return "$installPath/$binaryFile";
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
set('uploads/path', '{{deploy_path}}/shared'); // path in front of uploads directory
set('uploads/filters', []); // rsync filter syntax
set('mu-plugins/dir', 'wp-content/mu-plugins'); // relative to document root
set('mu-plugins/filters', []); // rsync filter syntax
set('plugins/dir', 'wp-content/plugins'); // relative to document root
set('plugins/filters', []); // rsync filter syntax
set('themes/dir', 'wp-content/themes'); // relative to document root
set('themes/filters', []); // rsync filter syntax
set('theme/build_script', 'build'); // custom theme npm build script

set('document_root', function () {
    // default to local document_root to be used if no host context is presetn
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
        'excludes'      => [], // do NOT exclude .deployfilter files - remote should be aware of them
        'exclude-file' => false,
        'includes'      => [],
        'include-file' => false,
        'filters'       => [],
        'filter-file'  => false,
        // Allows specifying (=excluding/including/filtering) files to sync per directory in a `.deployfilter` file - See README directory for examples
        'filter-perdir'=> '.deployfilter',
        'flags'        => 'rz', // Recursive, with compress
        'options'      => ['delete-after'], // needed so deployfilter files are send and delete is checked afterwards
        'timeout'      => 60,
    ];

    if (isVerbose()) {
        $config['options'][] = 'verbose';
    }
    if (isVeryVerbose()) {
        $config['options'][] = 'verbose';
    }
    if (isDebug()) {
        $config['options'][] = 'verbose';
    }

    return $config;
});
