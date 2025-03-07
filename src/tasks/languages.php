<?php

/**
 * Provides tasks for pushing, pulling, syncing and backing up language files
 */

namespace Gaambo\DeployerWordpress\Tasks;

require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;
use function \Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pushFiles;
use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

/**
 * Push languages from local to remote
 * Needs the following variables:
 *  - languages/filter: rsync filter syntax array of files to push (has a default)
 *  - languages/dir: Path of languages directory relative to release_path/current_path
 *  - deploy_path or release_path: to build remote path
 */
task('languages:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("languages/filter"),
    ]);
    pushFiles(getLocalhost()->get('languages/dir'), '{{languages/dir}}', $rsyncOptions);
})->desc('Push languages from local to remote');

/**
 * Pull languages from remote to local
 * Needs the following variables:
 *  - languages/filter: rsync filter syntax array of files to pull (has a default)
 *  - languages/dir: Path of languages directory relative to release_path/current_path
 *  - deploy_path or release_path: to build remote path
 */
task('languages:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("languages/filter"),
    ]);
    pullFiles('{{languages/dir}}', getLocalhost()->get('languages/dir'), $rsyncOptions);
})->desc('Pull languages from remote to local');

/**
 * Syncs languages between remote and local
 * Runs languages:push and languages:pull tasks in series
 * See tasks definitions for required variables
 */
task("languages:sync", ["languages:push", "languages:pull"])->desc("Sync languages");

/**
 * Backup languages on remote host and downloads zip to local backup path
 * Needs the following variables:
 *  - languages/dir: Path of languages directory relative to release_path/current_path
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('languages:backup:remote', function () {
    $backupFile = zipFiles(
        "{{release_or_current_path}}/{{languages/dir}}/",
        '{{backup_path}}',
        'backup_languages'
    );
    $localBackupPath = getLocalhost()->get('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup languages on remote host and download zip');

/**
 * Backup languages on localhost
 * Needs the following variables:
 *  - languages/dir: Path of languages directory relative to release_path/current_path
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('languages:backup:local', function () {
    $localPath = getLocalhost()->get('current_path');
    $localBackupPath = getLocalhost()->get('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{languages/dir}}/",
        $localBackupPath,
        'backup_languages'
    );
})->once()->desc('Backup local languages as zip');
