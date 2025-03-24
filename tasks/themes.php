<?php

/**
 * WordPress Theme Tasks
 *
 * This file provides tasks for managing WordPress themes, including:
 * - Installing theme dependencies via composer and npm
 * - Building theme assets via npm scripts
 * - Pushing/pulling theme files between environments
 * - Creating backups of theme directories
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use Gaambo\DeployerWordpress\Composer;
use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\NPM;
use Gaambo\DeployerWordpress\Rsync;

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;

/**
 * Theme Tasks
 *
 * Provides tasks for managing WordPress themes:
 * - Installing theme dependencies (npm, composer)
 * - Building theme assets
 * - Pushing/pulling theme files
 * - Creating backups
 */

/**
 * Install theme assets dependencies via npm
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - theme/name: Name (directory) of your custom theme
 * - bin/npm: NPM binary/command to use (automatically configured)
 *
 * Example:
 *     dep theme:assets:vendors prod
 */
task('theme:assets:vendors', function () {
    NPM::runInstall('{{release_or_current_path}}/{{themes/dir}}/{{theme/name}}');
})->desc('Install theme assets dependencies via npm');

/**
 * Build theme assets via npm script
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - theme/name: Name (directory) of your custom theme
 * - theme/build_script: NPM script to run (default: 'build')
 * - bin/npm: NPM binary/command to use (automatically configured)
 *
 * Example:
 *     dep theme:assets:build prod
 *     dep theme:assets:build prod -o theme/build_script=dev
 */
task('theme:assets:build', function () {
    NPM::runScript(
        '{{release_or_current_path}}/{{themes/dir}}/{{theme/name}}',
        '{{theme/build_script}}'
    );
})->desc('Build theme assets via npm script');

/**
 * Install theme assets and run build script
 *
 * Combines theme:assets:vendors and theme:assets:build tasks.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep theme:assets prod
 */
task('theme:assets', ['theme:assets:vendors', 'theme:assets:build'])
    ->desc('Install theme assets and run build script');

/**
 * Install theme vendors via composer
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - theme/name: Name (directory) of your custom theme
 * - bin/composer: Composer binary/command to use (automatically configured)
 *
 * Example:
 *     dep theme:vendors prod
 */
task('theme:vendors', function () {
    Composer::runDefault('{{release_or_current_path}}/{{themes/dir}}/{{theme/name}}');
})->desc('Install theme vendors via composer');

/**
 * Install all theme dependencies and build assets
 *
 * Combines theme:assets and theme:vendors tasks.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep theme prod
 */
task('theme', ['theme:assets', 'theme:vendors'])
    ->desc('Install all theme dependencies and build assets');

/**
 * Push themes from local to remote
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - themes/filter: Rsync filter rules for theme files (has defaults)
 *
 * Example:
 *     dep themes:push prod
 */
task('themes:push', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("themes/filter"),
    ]);
    Files::pushFiles(Localhost::getConfig('themes/dir'), '{{themes/dir}}', $rsyncOptions);
})->desc('Push themes from local to remote');

/**
 * Pull themes from remote to local
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - themes/filter: Rsync filter rules for theme files (has defaults)
 *
 * Example:
 *     dep themes:pull prod
 */
task('themes:pull', function () {
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("themes/filter"),
    ]);
    Files::pullFiles('{{themes/dir}}', Localhost::getConfig('themes/dir'), $rsyncOptions);
})->desc('Pull themes from remote to local');

/**
 * Sync themes between remote and local
 *
 * Combines themes:push and themes:pull tasks.
 * See individual tasks for configuration options.
 *
 * Example:
 *     dep themes:sync prod
 */
task('themes:sync', ['themes:push', 'themes:pull'])
    ->desc('Sync themes between environments');

/**
 * Backup themes on remote host
 *
 * Creates a zip backup of remote themes and downloads it locally.
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - backup_path: Path for storing backups (required on both local and remote)
 *
 * Example:
 *     dep themes:backup:remote prod
 */
task('themes:backup:remote', function () {
    $backupFile = Files::zipFiles(
        '{{release_or_current_path}}/{{themes/dir}}/',
        '{{backup_path}}',
        'backup_themes'
    );
    $localBackupPath = Localhost::getConfig('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup remote themes and download locally');

/**
 * Backup themes on local host
 *
 * Creates a zip backup of local themes.
 *
 * Configuration:
 * - themes/dir: Path to themes directory relative to document root
 * - backup_path: Path for storing backups (local)
 *
 * Example:
 *     dep themes:backup:local prod
 */
task('themes:backup:local', function () {
    $localPath = Localhost::getConfig('current_path');
    $localBackupPath = Localhost::getConfig('backup_path');
    Files::zipFiles(
        "$localPath/{{themes/dir}}/",
        $localBackupPath,
        'backup_themes'
    );
})->once()->desc('Backup local themes');
