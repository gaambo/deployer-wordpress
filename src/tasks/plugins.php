<?php

/**
 * Provides tasks for pushing, pulling, syncing and backing up plugins
 */

namespace Gaambo\DeployerWordpress\Tasks;

require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;
use function Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function Gaambo\DeployerWordpress\Utils\Files\pushFiles;
use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

/**
 * Push plugins from local to remote
 * Needs the following variables:
 *  - plugins/filter: rsync filter syntax array of files to push (has a default)
 *  - plugins/dir: Path of plugins directory relative to release_path/current_path
 *  - deploy_path or release_path: to build remote path
 */
task('plugins:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("plugins/filter"),
    ]);
    pushFiles(getLocalhost()->get('plugins/dir'), '{{plugins/dir}}', $rsyncOptions);
})->desc('Push plugins from local to remote');

/**
 * Pull plugins from remote to local
 * Needs the following variables:
 *  - plugins/filter: rsync filter syntax array of files to pull (has a default)
 *  - plugins/dir: Path of plugins directory relative to release_path/current_path
 *  - deploy_path or release_path: to build remote path
 */
task('plugins:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("plugins/filter"),
    ]);
    pullFiles('{{plugins/dir}}', getLocalhost()->get('plugins/dir'), $rsyncOptions);
})->desc('Pull plugins from remote to local');

/**
 * Syncs plugins between remote and local
 * Runs plugins:push and plugins:pull tasks in series
 * See tasks definitions for required variables
 */
task("plugins:sync", ["plugins:push", "plugins:pull"])->desc("Sync plugins");

/**
 * Backup plugins on remote host and downloads zip to local backup path
 * Needs the following variables:
 *  - plugins/dir: Path of plugins directory relative to release_path/current_path
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('plugins:backup:remote', function () {
    $backupFile = zipFiles(
        "{{release_or_current_path}}/{{plugins/dir}}/",
        '{{backup_path}}',
        'backup_plugins'
    );
    $localBackupPath = getLocalhost()->get('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup plugins on remote host and download zip');

/**
 * Backup plugins on localhost
 * Needs the following variables:
 *  - plugins/dir: Path of plugins directory relative to release_path/current_path
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('plugins:backup:local', function () {
    $localPath = getLocalhost()->get('current_path');
    $localBackupPath = getLocalhost()->get('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{plugins/dir}}/",
        $localBackupPath,
        'backup_plugins'
    );
})->once()->desc('Backup local plugins as zip');
