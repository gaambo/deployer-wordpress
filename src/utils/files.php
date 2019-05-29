<?php
/**
 * Provides helper functions for handling files
 * Including up- & downloads and zipping
 */

namespace Gaambo\DeployerWordpress\Utils\Files;

require_once 'localhost.php';

use function \Deployer\get;
use function \Deployer\upload;
use function \Deployer\download;
use function \Deployer\run;
use function \Gaambo\DeployerWordpress\Utils\Localhost\getLocalhostConfig;

/**
 * Gets the release path to sync files to
 * If called outside of a deployment context release_path may not be set
 * Therefore deploy_path/current is used
 *
 * @return string Path to current release
 */
function getRemotePath() : string
{
    $remotePath = '{{deploy_path}}/current';
    $releasePath = get('release_path');
    if ($releasePath) { // if a release is currently running we deploy into the release path
        $remotePath = $releasePath;
    }
    return $remotePath;
}

/**
 * Push files to a remote host
 * Syncs from a directory in locals document_root to a directory in remotes remotePath (@see getRemotePath above)
 * Uses Deployers upload function
 *
 * @param string $source Source path relative to locals document_root
 * @param string $destination Destination path relative to remote path (current release, @see getRemotePath above)
 * @param array $rsyncOptions Array of command-line arguments for rsync to pass to Deployers upload
 * @return void
 */
function pushFiles(string $source, string $destination, array $rsyncOptions)
{
    $localPath = getLocalhostConfig('document_root');
    $remotePath = getRemotePath();
    upload("$localPath/$source/", "$remotePath/$destination/", ['options' => $rsyncOptions]);
}

/**
 * Pull files from a remote host
 * Syncs from a directory in remotes remote path (@see getRemotePath above) to locals document_root
 * Uses Deployers download function
 *
 * @param string $source Source path relative to remote path (current release, @see getRemotePath above)
 * @param string $destination Destination path relative to locals document_root
 * @param array $rsyncOptions Array of command-line arguments for rsync to pass to Deployers download
 * @return void
 */
function pullFiles(string $source, string $destination, array $rsyncOption)
{
    $localPath = getLocalhostConfig('document_root');
    $remotePath = getRemotePath();
    download("$remotePath/$source/", "$localPath/$destination/", ['options' => $rsyncOptions]);
}

/**
 * Zip files into a backup zip
 *
 * @param string $dir Directory to zip
 *  Can have a trailing slash, which backups the contents of the directory, if not it backups the directory into the zip
 * @param string $backupDir Directory in which to store the zip
 * @param string $filename Filename of the zip file - gets prefixed to a datetime
 * @return string The full path ($backupDir + full filename) to the created zip
 */
function zipFiles(string $dir, string $backupDir, string $filename) : string
{
    
    $backupFilename = $filename . '_' . date('Y-m-d_H-i-s') . '.zip';
    $backupPath = "$backupDir/$backupFilename";
    run("mkdir -p $backupDir");

    // dir can have a trailing slash (which means, backup only the content of the specified directory)
    if (substr($dir, - 1) == '/') {
        // Add everything from directory to zip, but exclude previous backups
        run("cd {$dir} && zip -r {$backupFilename} . {{zip_options}} && mv $backupFilename $backupPath");
    } else {
        $parentDir = dirname($dir);
        $dir       = basename($dir);
        // Add dir itself to zip, but exclude previous backups
        run("cd {$parentDir} && zip -r {$backupFilename} {$dir} {{zip_options}} && mv $backupFilename $backupPath");
    }

    return $backupPath;
}
