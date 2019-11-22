<?php
/**
 * Provides tasks for managing themes
 * Including pushing, pulling, syncing and backing up themes
 * and installing vendors (npm, composer) and building assets via npm script for custom themes
 */

namespace Deployer;

require_once 'utils/composer.php';
require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/npm.php';
require_once 'utils/rsync.php';

use function \Gaambo\DeployerWordpress\Utils\Localhost\getLocalhostConfig;
use function \Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\getRemotePath;
use function \Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pushFiles;

/**
 * Install theme assets vendors/dependencies (npm)
 * Can be run locally or remote
 * Needs the following variables:
 *  - document_root: Directory from which to search for theme directory - can be set to release_path on remote hosts (defaults to local config)
 *  - themes/dir: Path to directory which contains all themes (has a default)
 *  - theme/name: Name (= directory) of your custom theme
 */
task('theme:assets:vendors', function () {
    \Gaambo\DeployerWordpress\Utils\Npm\runInstall('{{document_root}}/{{themes/dir}}/{{theme/name}}', 'install');
});

/**
 * Run theme assets (npm) build script
 * Can be run locally or remote
 * Needs the following variables:
 *  - document_root: Directory from which to search for theme directory - can be set to release_path on remote hosts (defaults to local config)
 *  - themes/dir: Path to directory which contains all themes (has a default)
 *  - theme/name: Name (= directory) of your custom theme
 *  - theme/build_script: NPM script to be run (must be defined in package.json, has a default)
 */
task('theme:assets:build', function () {
    \Gaambo\DeployerWordpress\Utils\Npm\runScript('{{document_root}}/{{themes/dir}}/{{theme/name}}', '{{theme/build_script}}');
});

/**
 * Install theme assets vendors/dependencies (npm) and run build script
 * Runs theme:assets:vendors and theme:assets:build tasks in series
 */
task('theme:assets', ['theme:assets:vendors', 'theme:assets:build']);

/**
 * Install theme vendors (composer)
 * Can be run locally or remote
 * Needs the following variables:
 *  - document_root: Directory from which to search for theme directory - can be set to release_path on remote hosts (defaults to local config)
 *  - themes/dir: Path to directory which contains all themes (has a default)
 *  - theme/name: Name (= directory) of your custom theme
 */
task('theme:vendors', function () {
    \Gaambo\DeployerWordpress\Utils\Composer\runDefault('{{document_root}}/{{themes/dir}}/{{theme/name}}');
});

/**
 * Install theme vendors (composer + npm) and build assets (npm)
 * Runs theme:assets and theme:vendors tasks in series
 * See tasks definitions for required variables
 */
task('theme', ['theme:assets', 'theme:vendors']);

/**
 * Push themes from local to remote
 * Needs the following variables:
 *  - themes/filters: rsync filter syntax array of files to push (has a default)
 *  - themes/dir: Path of themes directory relative to document_root/release_path (has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 *  - deploy_path or release_path: to build remote path
 */
task('themes:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("themes/filters"),
        'flags' => 'rz',
        'filter-perdir'=> '.deployfilter', // allows excluding files on a per-dir basis in a .deployfilter file
    ]);
    pushFiles('{{themes/dir}}', '{{themes/dir}}', $rsyncOptions);
})->desc('Push themes from local to remote');

/**
 * Pull themes from remote to local
 * Needs the following variables:
 *  - themes/filters: rsync filter syntax array of files to pull (has a default)
 *  - themes/dir: Path of themes directory relative to document_root/release_path (has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 *  - deploy_path or release_path: to build remote path
 */
task('themes:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("themes/filters"),
        'flags' => 'rz',
        'filter-perdir'=> '.deployfilter', // allows excluding files on a per-dir basis in a .deployfilter file
    ]);
    pullFiles('{{themes/dir}}', '{{themes/dir}}', $rsyncOptions);
})->desc('Pull themes from remote to local');

/**
 * Syncs themes between remote and local
 * Runs themes:push and themes:pull tasks in series
 * See tasks definitions for required variables
 */
task("themes:sync", ["themes:push", "themes:pull"])->desc("Sync themes");

/**
 * Backup themes on remote host and downloads zip to local backup path
 * Needs the following variables:
 *  - themes/dir: Path of themes directory relative to document_root/release_path (has a default)
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - deploy_path or release_path: to build remote path
 */
task('themes:backup:remote', function () {
    $remotePath = getRemotePath();
    $backupFile = zipFiles(
        "$remotePath/{{themes/dir}}/",
        '{{backup_path}}',
        'backup_themes'
    );
    $localBackupPath = getLocalhostConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup themes on remote host and download zip');

/**
 * Backup themes on localhost
 * Needs the following variables:
 *  - themes/dir: Path of themes directory relative to document_root/release_path (has a default)
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - document_root on localhost: Path to directory which contains the public document_root
 */
task('themes:backup:local', function () {
    $localPath = getLocalhostConfig('document_root');
    $localBackupPath = getLocalhostConfig('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{themes/dir}}/",
        $localBackupPath,
        'backup_themes'
    );
})->local()->desc('Backup local themes as zip');
