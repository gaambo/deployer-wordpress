<?php

/**
 * WordPress Language Tasks
 *
 * This file provides tasks for managing WordPress language files, including:
 * - Pushing/pulling language files between environments
 * - Creating backups of language directories
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;
use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Rsync;

/**
 * Push languages from local to remote
 * 
 * Configuration:
 * - languages/dir: Path to languages directory relative to document root
 * - languages/filter: Rsync filter rules for language files (has defaults)
 * 
 * Example:
 *     dep languages:push prod
 */
task('languages:push', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("languages/filter"),
    ]);
    Files::pushFiles(Localhost::getConfig('languages/dir'), '{{languages/dir}}', $rsyncOptions);
})->desc('Push languages from local to remote');

/**
 * Pull languages from remote to local
 * 
 * Configuration:
 * - languages/dir: Path to languages directory relative to document root
 * - languages/filter: Rsync filter rules for language files (has defaults)
 * 
 * Example:
 *     dep languages:pull prod
 */
task('languages:pull', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("languages/filter"),
    ]);
    Files::pullFiles('{{languages/dir}}', Localhost::getConfig('languages/dir'), $rsyncOptions);
})->desc('Pull languages from remote to local');

/**
 * Sync languages between remote and local
 * 
 * Combines languages:push and languages:pull tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep languages:sync prod
 */
task('languages:sync', ['languages:push', 'languages:pull'])
    ->desc('Sync languages between environments');

/**
 * Backup languages on remote host
 * 
 * Creates a zip backup of remote languages and downloads it locally.
 * 
 * Configuration:
 * - languages/dir: Path to languages directory relative to document root
 * - backup_path: Path for storing backups (required on both local and remote)
 * 
 * Example:
 *     dep languages:backup:remote prod
 */
task('languages:backup:remote', function () {
    $backupFile = Files::zipFiles(
        '{{release_or_current_path}}/{{languages/dir}}/',
        '{{backup_path}}',
        'backup_languages'
    );
    $localBackupPath = Localhost::getConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup remote languages and download locally');

/**
 * Backup languages on local host
 * 
 * Creates a zip backup of local languages.
 * 
 * Configuration:
 * - languages/dir: Path to languages directory relative to document root
 * - backup_path: Path for storing backups (local)
 * 
 * Example:
 *     dep languages:backup:local prod
 */
task('languages:backup:local', function () {
    $localPath = Localhost::getConfig('current_path');
    $localBackupPath = Localhost::getConfig('backup_path');
    $backupFile = Files::zipFiles(
        "$localPath/{{languages/dir}}/",
        $localBackupPath,
        'backup_languages'
    );
})->once()->desc('Backup local languages');
