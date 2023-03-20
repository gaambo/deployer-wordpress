<?php

/**
 * Provides tasks for managing mu-plugins
 * Including pushing, pulling, syncing and backing up mu-plugins
 * and installing vendors (composer) for custom mu-pulgins
 */

namespace Deployer;

require_once 'utils/composer.php';
require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';

use function \Gaambo\DeployerWordpress\Utils\Localhost\getLocalhostConfig;
use function \Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\getRemotePath;
use function \Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pushFiles;

/**
 * Install mu-plugin vendors (composer)
 * Can be run locally or remote
 * Needs the following variables:
 *  - document_root: Directory from which to search for mu-plugin directory - can be set to release_path on remote hosts (defaults to local config)
 *  - mu-plugins/dir: Path to directory which contains all mu-plugins (has a default)
 *  - mu-plugin/name: Name (= directory) of your custom mu-plugin
 */
task('mu-plugin:vendors', function () {
    \Gaambo\DeployerWordpress\Utils\Composer\runDefault('{{document_root}}/{{mu-plugins/dir}}/{{mu-plugin/name}}');
})->desc("Install mu-plugin vendors (composer)");

/**
 * Install mu-plugin vendors (composer)
 * At the moment only runs mu-plugin:vendors task
 * See task definition for required variables
 */
task('mu-plugin', ['mu-plugin:vendors'])
    ->desc("A combined tasks to prepare the theme");

/**
 * Push mu-plugins from local to remote
 * Needs the following variables:
 *  - mu-plugins/filter: rsync filter syntax array of files to push (has a default)
 *  - mu-plugins/dir: Path of mu-plugins directory relative to document_root/release_path (has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 *  - deploy_path or release_path: to build remote path
 */
task('mu-plugins:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("mu-plugins/filter"),
    ]);
    pushFiles('{{mu-plugins/dir}}', '{{mu-plugins/dir}}', $rsyncOptions);
})->desc('Push mu-plugins from local to remote');

/**
 * Pull mu-plugins from remote to local
 * Needs the following variables:
 *  - mu-plugins/filter: rsync filter syntax array of files to pull (has a default)
 *  - mu-plugins/dir: Path of mu-plugins directory relative to document_root/release_path (has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 *  - deploy_path or release_path: to build remote path
 */
task('mu-plugins:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("mu-plugins/filter"),
    ]);
    pullFiles('{{mu-plugins/dir}}', '{{mu-plugins/dir}}', $rsyncOptions);
})->desc('Pull mu-plugins from remote to local');

/**
 * Syncs mu-plugins between remote and local
 * Runs mu-plugins:push and mu-plugins:pull tasks in series
 * See tasks definitions for required variables
 */
task("mu-plugins:sync", ["mu-plugins:push", "mu-plugins:pull"])->desc("Sync mu-plugins");

/**
 * Backup mu-plugins on remote host and download zip to local backup path
 * Needs the following variables:
 *  - mu-plugins/dir: Path of mu-plugins directory relative to document_root/release_path (has a default)
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - deploy_path or release_path: to build remote path
 */
task('mu-plugins:backup:remote', function () {
    $remotePath = getRemotePath();
    $backupFile = zipFiles(
        "$remotePath/{{mu-plugins/dir}}/",
        '{{backup_path}}',
        'backup_mu-plugins'
    );
    $localBackupPath = getLocalhostConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup mu-plugins on remote host and download zip');

/**
 * Backup mu-plugins on localhost
 * Needs the following variables:
 *  - mu-plugins/dir: Path of mu-plugins directory relative to document_root/release_path (has a default)
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - document_root on localhost: Path to directory which contains the public document_root
 */
task('mu-plugins:backup:local', function () {
    $localPath = getLocalhostConfig('document_root');
    $localBackupPath = getLocalhostConfig('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{mu-plugins/dir}}/",
        $localBackupPath,
        'backup_mu-plugins'
    );
})->once()->desc('Backup local mu-plugins as zip');
