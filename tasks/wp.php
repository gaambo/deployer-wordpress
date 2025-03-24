<?php

/**
 * WordPress Core Tasks
 *
 * This file provides tasks for managing WordPress core, including:
 * - Downloading WordPress core files
 * - Pushing/pulling core files between environments
 * - Running WP-CLI commands
 * - Installing WP-CLI
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\get;
use function Deployer\task;
use Gaambo\DeployerWordpress\Files;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\Rsync;
use Gaambo\DeployerWordpress\WPCLI;

/**
 * Download WordPress core files
 * 
 * Configuration:
 * - wp/version: WordPress version to download (optional, defaults to latest)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * 
 * Example:
 *     dep wp:download-core production
 *     dep wp:download-core production -o wp/version=6.4.3
 */
task('wp:download-core', function () {
    WPCLI::runCommand("core download --skip-content --version=" . get('wp/version', 'latest'));
})->desc('Download WordPress core files');

/**
 * Push WordPress core files from local to remote
 * 
 * Configuration:
 * - wp/dir: Path to WordPress directory relative to document root
 * - wp/filter: Rsync filter rules for WordPress core files (has defaults)
 * 
 * Example:
 *     dep wp:push prod
 */
task('wp:push', function () {
    $localWpDir = Localhost::getConfig('wp/dir');
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("wp/filter"),
    ]);
    Files::pushFiles($localWpDir, '{{wp/dir}}', $rsyncOptions);
})->desc('Push WordPress core files from local to remote');

/**
 * Pull WordPress core files from remote to local
 * 
 * Configuration:
 * - wp/dir: Path to WordPress directory relative to document root
 * - wp/filter: Rsync filter rules for WordPress core files (has defaults)
 * 
 * Example:
 *     dep wp:pull prod
 */
task('wp:pull', function () {
    $localWpDir = Localhost::getConfig('wp/dir');
    $rsyncOptions = Rsync::buildOptionsArray([
        'filter' => get("wp/filter"),
    ]);
    Files::pullFiles( '{{wp/dir}}', $localWpDir, $rsyncOptions);
})->desc('Pull WordPress core files from remote to local');

/**
 * Display WP-CLI information
 * 
 * Useful for debugging WP-CLI setup and configuration.
 * Shows version, PHP info, and config paths.
 * 
 * Configuration:
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * 
 * Example:
 *     dep wp:info prod
 */
task('wp:info', function () {
    WPCLI::runCommand("--info");
})->desc('Display WP-CLI information and configuration');

/**
 * Install WP-CLI on remote host
 * 
 * Configuration (via CLI options):
 * - installPath: Directory to install WP-CLI in (required)
 * - binaryFile: Name of the WP-CLI binary (default: wp-cli.phar)
 * - sudo: Whether to use sudo for installation (default: false)
 * 
 * Example:
 *     dep wp:install-wpcli prod -o installPath=/usr/local/bin -o binaryFile=wp
 *     dep wp:install-wpcli prod -o installPath=/usr/local/bin -o sudo=true
 */
task('wp:install-wpcli', function () {
    $installPath = get('installPath');
    if (empty($installPath)) {
        throw new \RuntimeException(
            'You have to set an installPath for WordPress via a config variable or in cli via `-o installPath=$path`.'
        );
    }
    $binaryFile = get('binaryFile', 'wp-cli.phar');
    $sudo = get('sudo', false);

    WPCLI::install($installPath, $binaryFile, $sudo);
})->desc('Install WP-CLI on remote host');
