<?php

/**
 * WordPress Plugin Tasks
 *
 * This file provides tasks for managing WordPress plugins, including:
 * - Pushing/pulling plugin files between environments
 * - Creating backups of plugin directories
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Rsync;

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;

/**
 * Push plugins from local to remote
 *
 * Configuration:
 * - plugins/dir: Path to plugins directory relative to document root
 * - plugins/filter: Rsync filter rules for plugin files (has defaults)
 *
 * Example:
 *     dep plugins:push prod
 */
task('plugins:push', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("plugins/filter"),
    ]);
    Files::pushFiles(Localhost::getConfig('plugins/dir'), '{{plugins/dir}}', $rsyncOptions);
})->desc('Push plugins from local to remote');

/**
 * Pull plugins from remote to local
 *
 * Configuration:
 * - plugins/dir: Path to plugins directory relative to document root
 * - plugins/filter: Rsync filter rules for plugin files (has defaults)
 *
 * Example:
 *     dep plugins:pull prod
 */
task('plugins:pull', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("plugins/filter"),
    ]);
    Files::pullFiles('{{plugins/dir}}', Localhost::getConfig('plugins/dir'), $rsyncOptions);
})->desc('Pull plugins from remote to local');

/**
 * Sync plugins between remote and local
 *
 * Combines plugins:push and plugins:pull tasks.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep plugins:sync prod
 */
task('plugins:sync', ['plugins:push', 'plugins:pull'])
    ->desc('Sync plugins between environments');

/**
 * Backup plugins on remote host
 *
 * Creates a zip backup of remote plugins and downloads it locally.
 *
 * Configuration:
 * - plugins/dir: Path to plugins directory relative to document root
 * - backup_path: Path for storing backups (required on both local and remote)
 *
 * Example:
 *     dep plugins:backup:remote prod
 */
task('plugins:backup:remote', function () {
    $backupFile = Files::zipFiles(
        '{{release_or_current_path}}/{{plugins/dir}}/',
        '{{backup_path}}',
        'backup_plugins'
    );
    $localBackupPath = Localhost::getConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup remote plugins and download locally');

/**
 * Backup plugins on local host
 *
 * Creates a zip backup of local plugins.
 *
 * Configuration:
 * - plugins/dir: Path to plugins directory relative to document root
 * - backup_path: Path for storing backups (local)
 *
 * Example:
 *     dep plugins:backup:local prod
 */
task('plugins:backup:local', function () {
    $localPath = Localhost::getConfig('current_path');
    $localBackupPath = Localhost::getConfig('backup_path');
    Files::zipFiles(
        "$localPath/{{plugins/dir}}/",
        $localBackupPath,
        'backup_plugins'
    );
})->once()->desc('Backup local plugins');
