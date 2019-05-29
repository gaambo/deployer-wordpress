<?php
/**
 * Provides tasks for using WP CLI and pulling/pushing WordPress core
 */

namespace Deployer;

require_once 'utils/files.php';
require_once 'utils/localhost.php';
require_once 'utils/rsync.php';

/**
 * Installs WordPress core via WP CLI
 * Needs the following variables:
 *  - deploy_path or release_path: to build remote path
 *  - bin/wp: WP CLI binary/command to use (has a default)
 *  - wp/version: WordPress verstion to install
 */
task('wp:install', function () {
    $remotePath = Gaambo\DeployerWordpress\Utils\Files\getRemotePath();
    run("cd $remotePath && {{bin/wp}} core download --version={{wp/version}}");
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
        'options' => [],
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
        'options' => [],
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
    $remotePath = Gaambo\DeployerWordpress\Utils\Files\getRemotePath();
    run("cd $remotePath && {{bin/wp}} --info");
});
