<?php

/**
 * WordPress Must-Use Plugin Tasks
 *
 * This file provides tasks for managing WordPress must-use plugins, including:
 * - Installing plugin dependencies via composer
 * - Pushing/pulling mu-plugin files between environments
 * - Creating backups of mu-plugin directories
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use Gaambo\DeployerWordpress\Composer;
use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Rsync;

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;

/**
 * Install mu-plugin vendors via composer
 *
 * Configuration:
 * - mu-plugins/dir: Path to mu-plugins directory relative to document root
 * - mu-plugin/name: Name (directory) of your custom mu-plugin
 * - bin/composer: Composer binary/command to use (automatically configured)
 *
 * Example:
 *     dep mu-plugin:vendors prod
 */
task('mu-plugin:vendors', function () {
    Composer::runDefault('{{release_or_current_path}}/{{mu-plugins/dir}}/{{mu-plugin/name}}');
})->desc('Install mu-plugin vendors via composer');

/**
 * Install all mu-plugin dependencies
 *
 * Currently only runs mu-plugin:vendors task.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep mu-plugin prod
 */
task('mu-plugin', ['mu-plugin:vendors'])
    ->desc('Install all mu-plugin dependencies');

/**
 * Push mu-plugins from local to remote
 *
 * Configuration:
 * - mu-plugins/dir: Path to mu-plugins directory relative to document root
 * - mu-plugins/filter: Rsync filter rules for mu-plugin files (has defaults)
 *
 * Example:
 *     dep mu-plugins:push prod
 */
task('mu-plugins:push', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("mu-plugins/filter"),
    ]);
    Files::pushFiles(Localhost::getConfig('mu-plugins/dir'), '{{mu-plugins/dir}}', $rsyncOptions);
})->desc('Push mu-plugins from local to remote');

/**
 * Pull mu-plugins from remote to local
 *
 * Configuration:
 * - mu-plugins/dir: Path to mu-plugins directory relative to document root
 * - mu-plugins/filter: Rsync filter rules for mu-plugin files (has defaults)
 *
 * Example:
 *     dep mu-plugins:pull prod
 */
task('mu-plugins:pull', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("mu-plugins/filter"),
    ]);
    Files::pullFiles('{{mu-plugins/dir}}', Localhost::getConfig('mu-plugins/dir'), $rsyncOptions);
})->desc('Pull mu-plugins from remote to local');

/**
 * Sync mu-plugins between remote and local
 *
 * Combines mu-plugins:push and mu-plugins:pull tasks.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep mu-plugins:sync prod
 */
task('mu-plugins:sync', ['mu-plugins:push', 'mu-plugins:pull'])
    ->desc('Sync mu-plugins between environments');

/**
 * Backup mu-plugins on remote host
 *
 * Creates a zip backup of remote mu-plugins and downloads it locally.
 *
 * Configuration:
 * - mu-plugins/dir: Path to mu-plugins directory relative to document root
 * - backup_path: Path for storing backups (required on both local and remote)
 *
 * Example:
 *     dep mu-plugins:backup:remote prod
 */
task('mu-plugins:backup:remote', function () {
    $backupFile = Files::zipFiles(
        '{{release_or_current_path}}/{{mu-plugins/dir}}/',
        '{{backup_path}}',
        'backup_mu-plugins'
    );
    $localBackupPath = Localhost::getConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup remote mu-plugins and download locally');

/**
 * Backup mu-plugins on local host
 *
 * Creates a zip backup of local mu-plugins.
 *
 * Configuration:
 * - mu-plugins/dir: Path to mu-plugins directory relative to document root
 * - backup_path: Path for storing backups (local)
 *
 * Example:
 *     dep mu-plugins:backup:local prod
 */
task('mu-plugins:backup:local', function () {
    $localPath = Localhost::getConfig('current_path');
    $localBackupPath = Localhost::getConfig('backup_path');
    Files::zipFiles(
        "$localPath/{{mu-plugins/dir}}/",
        $localBackupPath,
        'backup_mu-plugins'
    );
})->once()->desc('Backup local mu-plugins');
