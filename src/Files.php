<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\download;
use function Deployer\get;
use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\upload;

/**
 * @phpstan-import-type RsyncOptions from Rsync
 */
class Files
{
    /**
     * Resolve a path against a base path.
     *
     * Absolute paths (starting with "/") and home-relative paths (starting with "~")
     * are returned unchanged. Relative paths are resolved against the given base path.
     *
     * This maybe a naive check, but it should be good enough for our use cases.
     *
     * @param string $path Path to resolve.
     * @param string $basePath Base path for relative paths.
     * @return string Resolved path.
     */
    public static function resolvePath(string $path, string $basePath): string
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '~')) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . $path;
    }

    /**
     * Push files from local to remote.
     *
     * Relative paths are resolved against localhosts current_path (local) or the
     * remote hosts release_or_current_path (remote). Absolute and home-relative
     * paths are used unchanged.
     *
     * @param string $localPath Local path to push from.
     * @param string $remotePath Remote path to push to.
     * @param RsyncOptions $rsyncOptions Rsync options array
     * @return void
     */
    public static function pushFiles(string $localPath, string $remotePath, array $rsyncOptions = []): void
    {
        $localBasePath = Localhost::getConfig('current_path');
        $remoteBasePath = get('release_or_current_path');

        $localPath = self::resolvePath($localPath, $localBasePath);
        $remotePath = self::resolvePath($remotePath, $remoteBasePath);

        run("mkdir -p $remotePath"); // Always ensure remote directory exists.
        upload($localPath . '/', $remotePath . '/', ['options' => $rsyncOptions]);
    }

    /**
     * Pull files from remote to local.
     *
     * Relative paths are resolved against the remote hosts release_or_current_path
     * (remote) or localhosts current_path (local). Absolute and home-relative
     * paths are used unchanged.
     *
     * @param string $remotePath Remote path to pull from.
     * @param string $localPath Local path to pull to.
     * @param RsyncOptions $rsyncOptions Rsync options array
     * @return void
     */
    public static function pullFiles(string $remotePath, string $localPath, array $rsyncOptions = []): void
    {
        $localBasePath = Localhost::getConfig('current_path');
        $remoteBasePath = get('release_or_current_path');

        $localPath = self::resolvePath($localPath, $localBasePath);
        $remotePath = self::resolvePath($remotePath, $remoteBasePath);

        runLocally("mkdir -p $localPath"); // Always ensure local directory exists.
        download($remotePath . '/', $localPath . '/', ['options' => $rsyncOptions]);
    }

    /**
     * Push a single file from local to remote.
     *
     * Relative paths are resolved against localhosts current_path (local) or the
     * remote hosts release_or_current_path (remote). Absolute and home-relative
     * paths are used unchanged.
     *
     * @param string $localPath Local file path to push.
     * @param string $remotePath Remote file path to push to.
     * @param RsyncOptions $rsyncOptions Rsync options array
     * @return void
     */
    public static function pushFile(string $localPath, string $remotePath, array $rsyncOptions = []): void
    {
        $localBasePath = Localhost::getConfig('current_path');
        $remoteBasePath = get('release_or_current_path');

        $localPath = self::resolvePath($localPath, $localBasePath);
        $remotePath = self::resolvePath($remotePath, $remoteBasePath);

        run('mkdir -p ' . dirname($remotePath)); // Always ensure remote directory exists.
        upload($localPath, $remotePath, ['options' => $rsyncOptions]);
    }

    /**
     * Pull a single file from remote to local.
     *
     * Relative paths are resolved against the remote hosts release_or_current_path
     * (remote) or localhosts current_path (local). Absolute and home-relative
     * paths are used unchanged.
     *
     * @param string $remotePath Remote file path to pull.
     * @param string $localPath Local file path to pull to.
     * @param RsyncOptions $rsyncOptions Rsync options array
     * @return void
     */
    public static function pullFile(string $remotePath, string $localPath, array $rsyncOptions = []): void
    {
        $localBasePath = Localhost::getConfig('current_path');
        $remoteBasePath = get('release_or_current_path');

        $localPath = self::resolvePath($localPath, $localBasePath);
        $remotePath = self::resolvePath($remotePath, $remoteBasePath);

        runLocally('mkdir -p ' . dirname($localPath)); // Always ensure local directory exists.
        download($remotePath, $localPath, ['options' => $rsyncOptions]);
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
