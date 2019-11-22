<?php
/**
 * Provides tasks for pushing, pulling, syncing and backing up uploads
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
 * Push uploads from local to remote
 * Needs the following variables:
 *  - uploads/filters: rsync filter syntax array of files to push (has a default)
 *  - uploads/dir: Path of uploads directory relative to document_root/release_path (has a default)
 *  - uploads/path: Path to directory which contains the uploads directory on remote (eg shared directory, has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 */
task('uploads:push', function () {
    $localPath = getLocalhostConfig('document_root');
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("uploads/filters"),
        'flags' => 'rz',
        'filter-perdir'=> '.deployfilter', // allows excluding files on a per-dir basis in a .deployfilter file
    ]);
    upload("$localPath/{{uploads/dir}}/", '{{uploads/path}}/{{uploads/dir}}/', ['options' => $rsyncOptions]);
})->desc('Push uploads from local to remote');

/**
 * Pull uploads from remote to local
 * Needs the following variables:
 *  - uploads/filters: rsync filter syntax array of files to pull (has a default)
 *  - uploads/dir: Path of uploads directory relative to document_root/release_path (has a default)
 *  - uploads/path: Path to directory which contains the uploads directory on remote (eg shared directory, has a default)
 *  - document_root on localhost: Path to directory which contains the public document_root
 */
task('uploads:pull', function () {
    $localPath = getLocalhostConfig('document_root');
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("uploads/filters"),
        'flags' => 'rz',
        'filter-perdir'=> '.deployfilter', // allows excluding files on a per-dir basis in a .deployfilter file
    ]);
    download('{{uploads/path}}/{{uploads/dir}}/', "$localPath/{{uploads/dir}}/", ['options' => $rsyncOptions]);
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
 *  - uploads/dir: Path of uploads directory relative to document_root/release_path (has a default)
 *  - uploads/path: Path to directory which contains the uploads directory on remote (eg shared directory, has a default)
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('uploads:backup:remote', function () {
    $backupFile = zipFiles(
        '{{uploads/path}}/{{uploads/dir}}/',
        '{{backup_path}}',
        'backup_uploads'
    );
    $localBackupPath = getLocalhostConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup uploads on remote host and download zip');

/**
 * Backup uploads on localhost
 * Needs the following variables:
 *  - uploads/dir: Path of uploads directory relative to document_root/release_path (has a default)
 *  - uploads/path: Path to directory which contains the uploads directory on remote (eg shared directory, has a default)
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - document_root on localhost: Path to directory which contains the public document_root
 */
task('uploads:backup:local', function () {
    $localPath = getLocalhostConfig('document_root');
    $localBackupPath = getLocalhostConfig('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{uploads/dir}}/",
        $localBackupPath,
        'backup_uploads'
    );
})->local()->desc('Backup local uploads as zip');
