<?php

/**
 * Provides tasks for pushing, pulling, syncing and backing up uploads
 */

namespace Gaambo\DeployerWordpress\Tasks;

require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;
use function Deployer\upload;
use function Gaambo\DeployerWordpress\Utils\Files\zipFiles;

use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

/**
 * Push uploads from local to remote
 * Needs the following variables:
 *  - uploads/filter: rsync filter syntax array of files to push (has a default)
 *  - uploads/dir: Path of uploads directory relative to release_path/current_path
 *  - uploads/path: Path to directory which contains the uploads directory (eg shared directory, has a default)
 */
task('uploads:push', function () {
    $localUploadsPath = getLocalhost()->get('uploads/path');
    $localUploadsDir = getLocalhost()->get('uploads/dir');
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("uploads/filter"),
    ]);

    upload(
        "$localUploadsPath/$localUploadsDir/",
        '{{uploads/path}}/{{uploads/dir}}/',
        ['options' => $rsyncOptions]
    );
})->desc('Push uploads from local to remote');

/**
 * Pull uploads from remote to local
 * Needs the following variables:
 *  - uploads/filter: rsync filter syntax array of files to pull (has a default)
 *  - uploads/dir: Path of uploads directory relative to release_path/current_path
 *  - uploads/path: Path to directory which contains the uploads directory (eg shared directory, has a default)
 */
task('uploads:pull', function () {
    $localUploadsPath = getLocalhost()->get('uploads/path');
    $localUploadsDir = getLocalhost()->get('uploads/dir');
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("uploads/filter"),
    ]);
    download("{{uploads/path}}/{{uploads/dir}}/", "$localUploadsPath/$localUploadsDir/", ['options' => $rsyncOptions]);
})->desc('Pull uploads from remote to local');

/**
 * Syncs uploads between remote and local
 * Runs uploads:push and uploads:pull tasks in series
 * See tasks definitions for required variables
 */
task("uploads:sync", ["uploads:push", "uploads:pull"])->desc("Sync uploads");

/**
 * Backup uploads on remote host and downloads zip to local backup path
 * Needs the following variables:
 *  - uploads/dir: Path of uploads directory relative to release_path/current_path
 *  - uploads/path: Path to directory which contains the uploads directory (eg shared directory, has a default)
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('uploads:backup:remote', function () {
    $backupFile = zipFiles(
        '{{uploads/path}}/{{uploads/dir}}/',
        '{{backup_path}}',
        'backup_uploads'
    );
    $localBackupPath = getLocalhost()->get('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup uploads on remote host and download zip');

/**
 * Backup uploads on localhost
 * Needs the following variables:
 *  - uploads/dir: Path of uploads directory relative to release_path/current_path
 *  - uploads/path: Path to directory which contains the uploads directory (eg shared directory, has a default)
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('uploads:backup:local', function () {
    $localPath = getLocalhost()->get('current_path');
    $localBackupPath = getLocalhost()->get('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{uploads/dir}}/",
        $localBackupPath,
        'backup_uploads'
    );
})->once()->desc('Backup local uploads as zip');
