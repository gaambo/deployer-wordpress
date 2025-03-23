<?php

/**
 * WordPress Package Tasks
 *
 * This file provides tasks for managing WordPress packages, including:
 * - Pushing/pulling package files between environments
 * - Creating backups of package directories
 * - Managing package dependencies and configurations
 * 
 * Packages can be custom themes, plugins, or mu-plugins that need special handling.
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\download;
use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use Gaambo\DeployerWordpress\Composer;
use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\NPM;
use Gaambo\DeployerWordpress\Rsync;

/**
 * Install package assets dependencies via npm
 * 
 * Configuration per package:
 * - path: Path of package relative to release_path/current_path
 * - assets: Whether the package has assets to install
 * 
 * Example:
 *     dep packages:assets:vendors prod
 */
task('packages:assets:vendors', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        if (empty($package['assets'])) {
            continue;
        }
        NPM::runInstall("{{release_or_current_path}}/$packagePath");
    }
})->desc('Install package assets dependencies via npm');

/**
 * Build package assets via npm script
 * 
 * Configuration per package:
 * - path: Path of package relative to release_path/current_path
 * - assets: Whether the package has assets to build
 * - assets:build_script: NPM script to run (optional, default: "build")
 * 
 * Example:
 *     dep packages:assets:build prod
 */
task('packages:assets:build', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        if (empty($package['assets'])) {
            continue;
        }
        NPM::runScript(
            "{{release_or_current_path}}/$packagePath",
            $package['assets:build_script'] ?? 'build'
        );
    }
})->desc('Build package assets via npm script');

/**
 * Install and build package assets
 * 
 * Combines packages:assets:vendors and packages:assets:build tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep packages:assets prod
 */
task('packages:assets', ['packages:assets:vendors', 'packages:assets:build'])
    ->desc('Install and build package assets');

/**
 * Install package dependencies via composer
 * 
 * Configuration per package:
 * - path: Path of package relative to release_path/current_path
 * 
 * Example:
 *     dep packages:vendors prod
 */
task('packages:vendors', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        Composer::runDefault(
            "{{release_or_current_path}}/$packagePath"
        );
    }
})->desc('Install package dependencies via composer');

/**
 * Install all package dependencies
 * 
 * Combines packages:assets and packages:vendors tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep packages prod
 */
task('packages', ['packages:assets', 'packages:vendors'])
    ->desc('Install all package dependencies');

/**
 * Push packages from local to remote
 * 
 * Configuration per package:
 * - path: Path of package relative to release_path/current_path
 * - remote:path: Path of package on remote host
 * - rsync:filter: (optional) Rsync filter rules for package files
 * 
 * Example:
 *     dep packages:push prod
 */
task('packages:push', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        $remotePath = $package['remote:path'];
        $rsyncOptions = Rsync::buildOptionsArray([
            'filter' => $package['rsync:filter'] ?? [],
        ]);
        run("mkdir -p {{release_or_current_path}}/$remotePath");
        Files::pushFiles($packagePath, $remotePath, $rsyncOptions);
    }
})->desc('Push packages from local to remote');

/**
 * Pull packages from remote to local
 * 
 * Configuration per package:
 * - path: Path of package relative to release_path/current_path
 * - remote:path: Path of package on remote host
 * - rsync:filter: (optional) Rsync filter rules for package files
 * 
 * Example:
 *     dep packages:pull prod
 */
task('packages:pull', function () {
    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        $remotePath = $package['remote:path'];
        $rsyncOptions = Rsync::buildOptionsArray([
            'filter' => $package['rsync:filter'] ?? [],
        ]);
        Files::pullFiles($remotePath, $packagePath, $rsyncOptions);
    }
})->desc('Pull packages from remote to local');

/**
 * Sync packages between remote and local
 * 
 * Combines packages:push and packages:pull tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep packages:sync prod
 */
task('packages:sync', ['packages:push', 'packages:pull'])
    ->desc('Sync packages between environments');

/**
 * Backup packages on remote host
 * 
 * Creates a zip backup of remote packages and downloads it locally.
 * 
 * Configuration per package:
 * - remote:path: Path of package on remote host
 * - backup_path: Path for storing backups (required on both local and remote)
 * 
 * Example:
 *     dep packages:backup:remote prod
 */
task('packages:backup:remote', function () {
    foreach (get('packages', []) as $package) {
        $remotePath = $package['remote:path'];
        $backupFile = Files::zipFiles(
            "{{release_or_current_path}}/$remotePath/",
            '{{backup_path}}',
            'backup_packages'
        );
        $localBackupPath = Localhost::getConfig('backup_path');
        download($backupFile, "$localBackupPath/");
    }
})->desc('Backup remote packages and download locally');

/**
 * Backup packages on local host
 * 
 * Creates a zip backup of local packages.
 * 
 * Configuration per package:
 * - path: Path of package relative to release_path/current_path
 * - backup_path: Path for storing backups (local)
 * 
 * Example:
 *     dep packages:backup:local prod
 */
task('packages:backup:local', function () {
    $localPath = Localhost::getConfig('current_path');
    $localBackupPath = Localhost::getConfig('backup_path');

    foreach (get('packages', []) as $package) {
        $packagePath = $package['path'];
        $backupFile = Files::zipFiles(
            "$localPath/$packagePath/",
            $localBackupPath,
            'backup_packages'
        );
    }
})->once()->desc('Backup local packages');
