<?php

/**
 * Provides tasks for managing packages
 * Packages can be a custom theme, custom plugin or custom mu-plugin.
 * Including pushing, pulling, syncing and backing up packages
 * and installing vendors (npm, composer) and building assets via npm script
 */

namespace Gaambo\DeployerWordpress\Tasks;

require_once 'utils/composer.php';
require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/npm.php';
require_once 'utils/rsync.php';

use function Deployer\download;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function \Gaambo\DeployerWordpress\Utils\Files\zipFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pullFiles;
use function \Gaambo\DeployerWordpress\Utils\Files\pushFiles;
use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

/**
 * Install package assets vendors/dependencies (npm)
 *
 * Needs the following config per package:
 *  - 'path' (string): Path of package relative to release_path/current_path
 */
task('packages:assets:vendors', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        if (empty($package['assets'])) {
            continue;
        }
        \Gaambo\DeployerWordpress\Utils\Npm\runInstall("{{release_or_current_path}}/$packagePath");
    }
})->desc("Install package assets vendors/dependencies (npm)");

/**
 * Run package assets (npm) build script
 *
 * Needs the following config per package:
 *  - 'path' (string): Path of package relative to release_path/current_path
 *  - (optional) assets:build_script: NPM script to be run (must be defined in package.json, has a default of "build")
 */
task('packages:assets:build', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        if (empty($package['assets'])) {
            continue;
        }
        \Gaambo\DeployerWordpress\Utils\Npm\runScript(
            "{{release_or_current_path}}/$packagePath",
            $package['assets:build_script'] ?? 'build'
        );
    }
})->desc("Run package assets (npm) build script");

/**
 * Install package assets vendors/dependencies (npm) and run build script
 * Runs package:assets:vendors and package:assets:build tasks in series
 */
task('packages:assets', ['packages:assets:vendors', 'packages:assets:build'])
    ->desc("A combined task to prepare the packages assets - combines `packages:assets` and `packages:vendors`");

/**
 * Install packages vendors (composer)
 *
 * Needs the following config per package:
 *  - 'path' (string): Path of package relative to release_path/current_path
 */
task('packages:vendors', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        \Gaambo\DeployerWordpress\Utils\Composer\runDefault(
            "{{release_or_current_path}}/$packagePath"
        );
    }
})->desc("Install packages vendors (composer)");

/**
 * Install packages vendors (composer + npm) and build assets (npm)
 *
 * Runs packages:assets and packages:vendors tasks in series
 * See tasks definitions for required variables
 */
task('packages', ['packages:assets', 'packages:vendors'])
    ->desc("A combined task to prepare the packages  - combines `packages:assets` and `packages:vendors`");

/**
 * Push packages from local to remote
 *
 * Needs the following config per package:
 *  - 'path' (string): Path of package relative to release_path/current_path
 *  - 'remote:path' (string): Path of package on remote host
 *  - (optional) 'rsync:filter' (array): rsync filter syntax array of files to push
 */
task('packages:push', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        $remotePath = $package['remote:path'];
        $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
            'filter' => $package['rsync:filter'] ?? [],
        ]);
        run("mkdir -p {{release_or_current_path}}/$remotePath");
        pushFiles($packagePath, $remotePath, $rsyncOptions);
    }
})->desc('Push packages from local to remote');

/**
 * Pull packages from remote to local
 *
 * Needs the following config per package:
 *  - 'path' (string): Path of package relative to release_path/current_path
 *  - 'remote:path' (string): Path of package on remote host
 *  - (optional) 'rsync:filter' (array): rsync filter syntax array of files to push
 */
task('packages:pull', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        $remotePath = $package['remote:path'];
        $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
            'filter' => $package['rsync:filter'] ?? [],
        ]);
        pullFiles($remotePath, $packagePath, $rsyncOptions);
    }
})->desc('Pull packages from remote to local');

/**
 * Syncs packages between remote and local
 *
 * Runs packages:push and packages:pull tasks in series
 * See tasks definitions for required variables
 */
task("packages:sync", ["packages:push", "packages:pull"])->desc("Sync packages");

/**
 * Backup packages on remote host and downloads zip to local backup path
 *
 * Needs the following config per package:
 *  - 'remote:path' (string): Path of package on remote host
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('packages:backup:remote', function () {
    foreach (get('packages', []) as $package) {
        $remotePath = $package['remote:path'];
        $backupFile = zipFiles(
            "{{release_or_current_path}}/$remotePath/",
            '{{backup_path}}',
            'backup_packages'
        );
        $localBackupPath = getLocalhost()->get('backup_path');
        download($backupFile, "$localBackupPath/");
    }
})->desc('Backup packages on remote host and download zip');

/**
 * Backup packages on local host
 *
 * Needs the following config per package:
 *  - 'remote:path' (string): Path of package on remote host
 *  - backup_path (on remote host): Path to directory in which to store all backups
 *  - backup_path (on localhost): Path to directory in which to store all backups
 */
task('packages:backup:local', function () {
    $localPath = getLocalhost()->get('current_path');
    $localBackupPath = getLocalhost()->get('backup_path');

    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        $backupFile = zipFiles(
            "{{release_or_current_path}}/$packagePath/",
            $localBackupPath,
            'backup_packages'
        );
    }
})->once()->desc('Backup local packages as zip');
