<?php

/**
 * Provides helper functions for handling files
 * Including up- & downloads and zipping
 */

namespace Gaambo\DeployerWordpress\Utils\Files;

use function \Deployer\upload;
use function \Deployer\download;
use function \Deployer\run;
use function Gaambo\DeployerWordpress\Utils\Localhost\getLocalhost;

require_once 'localhost.php';

/**
 * Push files to a remote host
 * Syncs from a directory in locals current_path to a directory in remotes release_path/current_path
 * Uses Deployers upload function
 *
 * @param string $source Source path relative to locals release_path/current_path
 * @param string $destination Destination path relative to release_path/current_path
 * @param array $rsyncOptions Array of command-line arguments for rsync to pass to Deployers upload
 * @return void
 */
function pushFiles(string $source, string $destination, array $rsyncOptions)
{
    $localPath = getLocalhost()->get('current_path');
    upload("$localPath/$source/", "{{release_or_current_path}}/$destination/", ['options' => $rsyncOptions]);
}

/**
 * Pull files from a remote host
 * Syncs from a directory in remotes release_path/current_path to locals current_path
 * Uses Deployers download function
 *
 * @param string $source Source path relative to release_path/current_path
 * @param string $destination Destination path relative to locals current_path
 * @param array $rsyncOptions Array of command-line arguments for rsync to pass to Deployers download
 * @return void
 */
function pullFiles(string $source, string $destination, array $rsyncOptions)
{
    $localPath = getLocalhost()->get('current_path');
    download("{{release_or_current_path}}/$source/", "$localPath/$destination/", ['options' => $rsyncOptions]);
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
function zipFiles(string $dir, string $backupDir, string $filename): string
{

    $backupFilename = $filename . '_' . date('Y-m-d_H-i-s') . '.zip';
    $backupPath = "$backupDir/$backupFilename";
    run("mkdir -p $backupDir");

    // dir can have a trailing slash (which means, backup only the content of the specified directory)
    if (substr($dir, -1) == '/') {
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
