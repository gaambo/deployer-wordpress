<?php
/**
 * Provides tasks for pushing, pulling, syncing and backing up plugins
 */

namespace Deployer;

require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';

use function \Gaambo\DeployerWordpress\Utils\Localhost\getLocalhostConfig;
use function \Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\getRemotePath;
use function \Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pushFiles;

/**
 * Push plugins from local to remote
 * Needs the following variables:
 *  - plugins/filters: rsync filter syntax array of files to push (has a default)
 *  - plugins/dir: Path of plugins directory relative to document_root/release_path (has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 *  - deploy_path or release_path: to build remote path
 */
task('plugins:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("plugins/filters"),
        'flags' => 'rz',
        'filter-perdir'=> '.deployfilter', // allows excluding files on a per-dir basis in a .deployfilter file
        'options' => [],
    ]);
    pushFiles('{{plugins/dir}}', '{{plugins/dir}}', $rsyncOptions);
})->desc('Push plugins from local to remote');

/**
 * Pull plugins from remote to local
 * Needs the following variables:
 *  - plugins/filters: rsync filter syntax array of files to pull (has a default)
 *  - plugins/dir: Path of plugins directory relative to document_root/release_path (has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 *  - deploy_path or release_path: to build remote path
 */
task('plugins:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("plugins/filters"),
        'flags' => 'rz',
        'filter-perdir'=> '.deployfilter', // allows excluding files on a per-dir basis in a .deployfilter file
        'options' => [],
    ]);
    pullFiles('{{plugins/dir}}', '{{plugins/dir}}', $rsyncOptions);
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
 *  - plugins/dir: Path of plugins directory relative to document_root/release_path (has a default)
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - deploy_path or release_path: to build remote path
 */
task('plugins:backup:remote', function () {
    $remotePath = getRemotePath();
    $backupFile = zipFiles(
        "$remotePath/{{plugins/dir}}/",
        '{{backup_path}}',
        'backup_plugins'
    );
    $localBackupPath = getLocalhostConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup plugins on remote host and download zip');

/**
 * Backup plugins on localhost
 * Needs the following variables:
 *  - plugins/dir: Path of plugins directory relative to document_root/release_path (has a default)
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - document_root on localhost: Path to directory which contains the public document_root
 */
task('plugins:backup:local', function () {
    $localPath = getLocalhostConfig('document_root');
    $localBackupPath = getLocalhostConfig('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{plugins/dir}}/",
        $localBackupPath,
        'backup_plugins'
    );
})->local()->desc('Backup local plugins as zip');
