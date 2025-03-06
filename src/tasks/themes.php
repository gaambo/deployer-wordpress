<?php

/**
 * Provides tasks for managing themes
 * Including pushing, pulling, syncing and backing up themes
 * and installing vendors (npm, composer) and building assets via npm script for custom themes
 */

namespace Gaambo\DeployerWordpress\Tasks;

require_once 'utils/composer.php';
require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/npm.php';
require_once 'utils/rsync.php';

use function Deployer\download;
use function Deployer\get;
use function Deployer\task;
use function \Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pushFiles;
use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

/**
 * Install theme assets vendors/dependencies (npm)
 * Can be run locally or remote
 * Needs the following variables:
 *  - themes/dir: Path to directory which contains all themes relative to release_path/current_path
 *  - theme/name: Name (= directory) of your custom theme
 */
task('theme:assets:vendors', function () {
    \Gaambo\DeployerWordpress\Utils\Npm\runInstall('{{release_or_current_path}}/{{themes/dir}}/{{theme/name}}', 'install');
})->desc("Install theme assets vendors/dependencies (npm)");

/**
 * Run theme assets (npm) build script
 * Can be run locally or remote
 * Needs the following variables:
 *  - themes/dir: Path to directory which contains all themes relative to release_path/current_path
 *  - theme/name: Name (= directory) of your custom theme
 *  - theme/build_script: NPM script to be run (must be defined in package.json, has a default)
 */
task('theme:assets:build', function () {
    \Gaambo\DeployerWordpress\Utils\Npm\runScript(
        '{{release_or_current_path}}/{{themes/dir}}/{{theme/name}}',
        '{{theme/build_script}}'
    );
})->desc("Run theme assets (npm) build script");

/**
 * Install theme assets vendors/dependencies (npm) and run build script
 * Runs theme:assets:vendors and theme:assets:build tasks in series
 */
task('theme:assets', ['theme:assets:vendors', 'theme:assets:build'])
    ->desc("A combined task to prepare the theme  - combines `theme:assets` and `theme:vendors`");

/**
 * Install theme vendors (composer)
 * Can be run locally or remote
 * Needs the following variables:
 *  - themes/dir: Path to directory which contains all themes relative to release_path/current_path
 *  - theme/name: Name (= directory) of your custom theme
 */
task('theme:vendors', function () {
    \Gaambo\DeployerWordpress\Utils\Composer\runDefault('{{release_or_current_path}}/{{themes/dir}}/{{theme/name}}');
})->desc("Install theme vendors (composer), can be run locally or remote");

/**
 * Install theme vendors (composer + npm) and build assets (npm)
 * Runs theme:assets and theme:vendors tasks in series
 * See tasks definitions for required variables
 */
task('theme', ['theme:assets', 'theme:vendors'])
    ->desc("A combined task to prepare the theme  - combines `theme:assets` and `theme:vendors`");

/**
 * Push themes from local to remote
 * Needs the following variables:
 *  - themes/filter: rsync filter syntax array of files to push (has a default)
 *  - themes/dir: Path of themes directory relative to release_path/current_path
 *  - deploy_path or release_path: to build remote path
 */
task('themes:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("themes/filter"),
    ]);
    pushFiles(getLocalhost()->get('themes/dir'), '{{themes/dir}}', $rsyncOptions);
})->desc('Push themes from local to remote');

/**
 * Pull themes from remote to local
 * Needs the following variables:
 *  - themes/filter: rsync filter syntax array of files to pull (has a default)
 *  - themes/dir: Path of themes directory relative to release_path/current_path
 *  - deploy_path or release_path: to build remote path
 */
task('themes:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filter' => get("themes/filter"),
    ]);
    pullFiles('{{themes/dir}}', getLocalhost()->get('themes/dir'), $rsyncOptions);
})->desc('Pull themes from remote to local');

/**
 * Syncs themes between remote and local
 * Runs themes:push and themes:pull tasks in series
 * See tasks definitions for required variables
 */
task("themes:sync", ["themes:push", "themes:pull"])->desc("Sync themes");

/**
 * Backup themes on remote host and downloads zip to local backup path
 * Needs the following variables:
 *  - themes/dir: Path of themes directory relative to release_path/current_path
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 *  - deploy_path or release_path: to build remote path
 */
task('themes:backup:remote', function () {
    $backupFile = zipFiles(
        "{{release_or_current_path}}/{{themes/dir}}/",
        '{{backup_path}}',
        'backup_themes'
    );
    $localBackupPath = getLocalhost()->get('backup_path');
    download($backupFile, "$localBackupPath/");
})->desc('Backup themes on remote host and download zip');

/**
 * Backup themes on localhost
 * Needs the following variables:
 *  - themes/dir: Path of themes directory relative to release_path/current_path
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('themes:backup:local', function () {
    $localPath = getLocalhost()->get('current_path');
    $localBackupPath = getLocalhost()->get('backup_path');
    $backupFile = zipFiles(
        "$localPath/{{themes/dir}}/",
        $localBackupPath,
        'backup_themes'
    );
})->once()->desc('Backup local themes as zip');
