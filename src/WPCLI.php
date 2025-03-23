<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\test;

/**
 * WP CLI utility class
 * Provides methods to run WP CLI commands and install WP CLI
 */
class WPCLI
{
    private const INSTALLER_DOWNLOAD = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

    /**
     * Run a WP CLI command
     * @param string $command The command to run (without wp prefix)
     * @param string|null $path The path to run the command in (defaults to {{release_or_current_path}})
     * @param string $arguments Additional arguments to pass to WP-CLI
     * @return void
     */
    public static function runCommand(string $command, ?string $path = '{{release_or_current_path}}', string $arguments = ''): void
    {
        $cmd = "{{bin/wp}} $command $arguments";
        if ($path) {
            run("cd $path && $cmd");
        } else {
            run($cmd);
        }
    }

    /**
     * Run a WP CLI command locally
     * @param string $command The command to run (without wp prefix)
     * @param string|null $path The path to run the command in (defaults to {{release_or_current_path}})
     * @param string $arguments Additional arguments to pass to WP-CLI
     * @return void
     */
    public static function runCommandLocally(string $command, ?string $path = '{{release_or_current_path}}', string $arguments = ''): void
    {
        $localWp = Localhost::getConfig('bin/wp');
        if ($path) {
            runLocally("cd $path && $localWp $command $arguments");
        } else {
            runLocally("$localWp $command $arguments");
        }
    }

    /**
     * Install the WP-CLI binary
     * Uses Deployers run function
     *
     * @param string $installPath Path where to install the binary
     * @param string $binaryName Name for the binary file
     * @param bool $sudo Whether to use sudo for moving the binary
     * @return string Path to installed and moved binary file
     */
    public static function install(string $installPath, string $binaryName = 'wp-cli.phar', bool $sudo = false): string
    {
        $sudoPrefix = $sudo ? 'sudo ' : '';

        run("mkdir -p $installPath");
        run("cd $installPath && curl -sS -O " . self::INSTALLER_DOWNLOAD);
        
        if ($binaryName !== 'wp-cli.phar') {
            run("$sudoCommand mv $installPath/wp-cli.phar $installPath/$binaryName");
        }

        return "$installPath/$binaryName";
    }
} 