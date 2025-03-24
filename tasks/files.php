<?php

/**
 * WordPress File Management Tasks
 *
 * This file provides tasks for managing all WordPress files, including:
 * - Combined tasks for pushing/pulling all WordPress components
 * - Coordinating file operations across different WordPress directories
 * - Managing file synchronization between environments
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;

use function Deployer\download;
use function Deployer\task;

require_once __DIR__ . '/mu-plugins.php';
require_once __DIR__ . '/packages.php';
require_once __DIR__ . '/plugins.php';
require_once __DIR__ . '/themes.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/wp.php';

/**
 * Push all files from local to remote
 *
 * Runs wp:push, uploads:push, plugins:push, mu-plugins:push, themes:push, packages:push in series.
 * See individual task definitions for required configuration options.
 *
 * Example:
 *     dep files:push prod
 */
task('files:push', ['wp:push', 'uploads:push', 'plugins:push', 'mu-plugins:push', 'themes:push', 'packages:push'])
    ->desc('Push all files from local to remote');

/**
 * Pull all files from remote to local
 *
 * Runs wp:pull, uploads:pull, plugins:pull, mu-plugins:pull, themes:pull, packages:pull in series.
 * See individual task definitions for required configuration options.
 *
 * Example:
 *     dep files:pull prod
 */
task('files:pull', ['wp:pull', 'uploads:pull', 'plugins:pull', 'mu-plugins:pull', 'themes:pull', 'packages:pull'])
    ->desc('Pull all files from remote to local');

/**
 * Sync all files between remote and local
 *
 * Combines files:push and files:pull tasks.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep files:sync prod
 */
task('files:sync', ['files:push', 'files:pull'])
    ->desc('Sync all files between environments');

/**
 * Backup all files on remote host
 *
 * Creates a zip backup of remote WordPress files and downloads it locally.
 *
 * Configuration:
 * - backup_path: Path for storing backups (required on both local and remote)
 * - release_path: Path to WordPress installation on remote
 *
 * Example:
 *     dep files:backup:remote prod
 */
task('files:backup:remote', function () {
    $backupFile = Files::zipFiles(
        '{{release_or_current_path}}/',
        '{{backup_path}}',
        'backup_files'
    );
    $localBackupPath = Localhost::getConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup remote files and download locally');

/**
 * Backup all files on local host
 *
 * Creates a zip backup of local WordPress files.
 *
 * Configuration:
 * - backup_path: Path for storing backups (local)
 * - current_path: Path to WordPress installation on local
 *
 * Example:
 *     dep files:backup:local prod
 */
task('files:backup:local', function () {
    $localPath = Localhost::getConfig('current_path');
    $localBackupPath = Localhost::getConfig('backup_path');
    Files::zipFiles(
        "$localPath/",
        $localBackupPath,
        'backup_files'
    );
})->once()->desc('Backup local files');
