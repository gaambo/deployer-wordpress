<?php
/**
 * Provides tasks for using WP CLI and pulling/pushing WordPress core
 */

namespace Deployer;

use function Gaambo\DeployerWordpress\Utils\WPCLI\installWPCLI;
use function Gaambo\DeployerWordpress\Utils\WPCLI\runCommand;

require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';
require_once 'utils/wp-cli.php';

/**
 * Downloads WordPress core via WP CLI
 * Needs the following variables:
 *  - deploy_path or release_path: to build remote path
 *  - bin/wp: WP CLI binary/command to use (has a default)
 *  - wp/version: WordPress verstion to install
 */
task('wp:download-core', function () {
    $wpVersion = get('wp/version', 'latest');
    runCommand("core download --version=$wpVersion");
})->desc('Installs a WordPress version via WP CLI');

/**
 * Pushes WordPress core files via rsync
 * Needs the following variables:
 *  - deploy_path or release_path: to build remote path
 *  - wp/filter: rsync filter syntax array of files to push (has a default)
 *  - wp/dir: Path of WordPress directory relative to document_root/release_path (has a default)
 */
task('wp:push', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("wp/filter"),
        'flags' => 'rz',
    ]);
    \Gaambo\DeployerWordpress\Utils\Files\pushFiles('{{wp/dir}}', '{{wp/dir}}', $rsyncOptions);
})->desc('Push WordPress core files from local to remote');

/**
 * Pulls WordPress core files via rsync
 * Needs the following variables:
 *  - deploy_path or release_path: to build remote path
 *  - wp/filter: rsync filter syntax array of files to push (has a default)
 *  - wp/dir: Path of WordPress directory relative to document_root/release_path (has a default)
 */
task('wp:pull', function () {
    $rsyncOptions = \Gaambo\DeployerWordpress\Utils\Rsync\buildOptionsArray([
        'filters' => get("wp/filter"),
        'flags' => 'rz',
    ]);
    \Gaambo\DeployerWordpress\Utils\Files\pullFiles('{{wp/dir}}', '{{wp/dir}}', $rsyncOptions);
})->desc('Pull WordPress core files from remote to local');

/**
 * Runs the --info command via WP CLI
 * Just a helper/test task
 * Needs the following variables:
 *  - deploy_path or release_path: to build remote path
 *  - bin/wp: WP CLI binary/command to use (has a default)
 */
task('wp:info', function () {
    runCommand("--info");
});

/**
 * Installs the WP-CLI binary - for usage via CLI
 * Pass installPath, binaryFile and sudo via CLI like so:
 * `dep wp:install-wpcli production -o installPath=/usr/local/bin -o binaryFile=wp -o sudo=true`
 */
task('wp:install-wpcli', function () {
    $installPath = get('installPath');
    $binaryFile = get('binaryFile', 'wp-cli.phar');
    $sudo = get('sudo', false);

    installWPCLI($installPath, $binaryFile, $sudo);
});
