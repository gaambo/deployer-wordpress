<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\download;
use function Deployer\run;
use function Deployer\upload;

/**
 * @phpstan-import-type RsyncOptions from Rsync
 */
class Files
{
    /**
     * Push files from local to remote. Always works from the localhosts current_path directory.
     * If you want to upload files outside of current_path, use upload() directly.
     *
     * @param string $localPath Local path to push from, relative to localhosts current_path.
     * @param string $remotePath Remote path to push to, relative to remote hosts release_or_current_path.
     * @param RsyncOptions $rsyncOptions Rsync options array
     * @return void
     */
    public static function pushFiles(string $localPath, string $remotePath, array $rsyncOptions = []): void
    {
        $localPath = Localhost::getConfig('current_path') . '/' . $localPath;
        run("mkdir -p {{release_or_current_path}}/$remotePath"); // Always ensure remote directory exists.
        upload($localPath . '/', '{{release_or_current_path}}/' . $remotePath . '/', ['options' => $rsyncOptions]);
    }

    /**
     * Pull files from remote to local. Always works from the localhosts current_path directory.
     * If you want to download files outside of current_path, use download() directly.
     * @param string $remotePath Remote path to pull from, relative to remote hosts release_or_current_path.
     * @param string $localPath Local path to pull to, relative to localhosts current_path.
     * @param RsyncOptions $rsyncOptions Rsync options array
     * @return void
     */
    public static function pullFiles(string $remotePath, string $localPath, array $rsyncOptions = []): void
    {
        $localPath = Localhost::getConfig('current_path') . '/' . $localPath;
        Localhost::run("mkdir -p $localPath"); // Always ensure directory exists.
        download('{{release_or_current_path}}/' . $remotePath . '/', $localPath . '/', ['options' => $rsyncOptions]);
    }

    /**
     * Zip files into a backup zip
     *
     * @param string $dir Directory to zip
     *  Can have a trailing slash, which backups the contents of the directory,
     *  if not it backups the directory into the zip
     * @param string $backupDir Directory in which to store the zip
     * @param string $filename Filename of the zip file - gets prefixed to a datetime
     * @return string The full path ($backupDir + full filename) to the created zip
     */
    public static function zipFiles(string $dir, string $backupDir, string $filename): string
    {
        $backupFilename = $filename . '_' . date('Y-m-d_H-i-s') . '.zip';
        $backupPath = "$backupDir/$backupFilename";
        run("mkdir -p $backupDir");

        // dir can have a trailing slash (which means, backup only the content of the specified directory)
        if (str_ends_with($dir, '/')) {
            // Add everything from directory to zip, but exclude previous backups
            run("cd $dir && zip -r $backupFilename . {{zip_options}} && mv $backupFilename $backupPath");
        } else {
            $parentDir = dirname($dir);
            $dir = basename($dir);
            // Add dir itself to zip, but exclude previous backups
            run("cd $parentDir && zip -r $backupFilename $dir {{zip_options}} && mv $backupFilename $backupPath");
        }

        return $backupPath;
    }
}
