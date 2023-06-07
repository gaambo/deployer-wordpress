<?php

/**
 * Provides helper functions for running composer commands
 */

namespace Gaambo\DeployerWordpress\Utils\WPCLI;

const INSTALLER_DOWNLOAD = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

/**
 * Runs any WP-CLI command
 * Passes on the verbosity flags passed to Deployer CLI
 *
 * @param string $path Path in which to run WP-CLI command
 * @param string $command WP-CLI command to run
 * @param string $arguments Command-line arguments to be passed to WP-CLI as a string
 * @return string Result/Returned output from CLI
 */
function runCommand(string $command, string $path = '{{deploy_path}}', string $arguments = ''): string
{
    return \Deployer\run("cd $path && {{bin/wp}} $command $arguments");
}

/**
 * Installs the WP-CLI binary to a specified installPath
 * Allows passing a name for the binary file and using sudo (eg to move to /usr/local/bin)
 *
 * @param string $installPath
 * @param string $binaryName
 * @param boolean $sudo
 * @return string Path to installed and moved binary file
 */
function installWPCLI($installPath, $binaryName = 'wp-cli.phar', $sudo = false)
{
    $sudoCommand = $sudo ? 'sudo ' : '';

    \Deployer\run("mkdir -p $installPath");
    \Deployer\run("cd $installPath && curl -sS -O " . INSTALLER_DOWNLOAD);
    if ($binaryName !== 'wp-cli.phar') {
        \Deployer\run("$sudoCommand mv $installPath/wp-cli.phar $installPath/$binaryName");
    }

    return "$installPath/$binaryName";
}
