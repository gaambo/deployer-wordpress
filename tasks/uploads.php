<?php

/**
 * WordPress Uploads Tasks
 *
 * This file provides tasks for managing WordPress uploads, including:
 * - Pushing/pulling upload files between environments
 * - Creating backups of upload directories
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;
use function Deployer\upload;
use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Rsync;

/**
 * Push uploads from local to remote
 * 
 * Configuration:
 * - uploads/dir: Path to uploads directory relative to document root
 * - uploads/path: Path to directory containing uploads (e.g., shared directory)
 * - uploads/filter: Rsync filter rules for upload files (has defaults)
 * 
 * Example:
 *     dep uploads:push prod
 */
task('uploads:push', function () {
    $localUploadsPath = Localhost::getConfig('uploads/path');
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    $rsyncOptions = Rsync::buildOptionsArray([
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
 * 
 * Configuration:
 * - uploads/dir: Path to uploads directory relative to document root
 * - uploads/path: Path to directory containing uploads (e.g., shared directory)
 * - uploads/filter: Rsync filter rules for upload files (has defaults)
 * 
 * Example:
 *     dep uploads:pull prod
 */
task('uploads:pull', function () {
    $localUploadsPath = Localhost::getConfig('uploads/path');
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("uploads/filter"),
    ]);
    download("{{uploads/path}}/{{uploads/dir}}/", "$localUploadsPath/$localUploadsDir/", ['options' => $rsyncOptions]);
})->desc('Pull uploads from remote to local');

/**
 * Sync uploads between remote and local
 * 
 * Combines uploads:push and uploads:pull tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep uploads:sync prod
 */
task('uploads:sync', ['uploads:push', 'uploads:pull'])
    ->desc('Sync uploads between environments');

/**
 * Backup uploads on remote host
 * 
 * Creates a zip backup of remote uploads and downloads it locally.
 * 
 * Configuration:
 * - uploads/dir: Path to uploads directory relative to document root
 * - uploads/path: Path to directory containing uploads (e.g., shared directory)
 * - backup_path: Path for storing backups (required on both local and remote)
 * 
 * Example:
 *     dep uploads:backup:remote prod
 */
task('uploads:backup:remote', function () {
    $backupFile = Files::zipFiles(
        '{{uploads/path}}/{{uploads/dir}}/',
        '{{backup_path}}',
        'backup_uploads'
    );
    $localBackupPath = Localhost::getConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup remote uploads and download locally');

/**
 * Backup uploads on local host
 * 
 * Creates a zip backup of local uploads.
 * 
 * Configuration:
 * - uploads/dir: Path to uploads directory relative to document root
 * - uploads/path: Path to directory containing uploads (e.g., shared directory)
 * - backup_path: Path for storing backups (local)
 * 
 * Example:
 *     dep uploads:backup:local prod
 */
task('uploads:backup:local', function () {
    $localUploadsPath = Localhost::getConfig('uploads/path');
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    $localBackupPath = Localhost::getConfig('backup_path');
    $backupFile = Files::zipFiles(
        "$localUploadsPath/$localUploadsDir/",
        $localBackupPath,
        'backup_uploads'
    );
})->once()->desc('Backup local uploads');
