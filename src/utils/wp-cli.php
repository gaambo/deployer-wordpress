<?php
/**
 * Provides helper functions for running composer commands
 */

namespace Gaambo\DeployerWordpress\Utils\WPCLI;

const INSTALLER_DOWNLOAD = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

function installWPCLI($installPath, $binaryName = 'wp-cli.phar')
{
    \Deployer\run("mkdir -p $installPath");
    \Deployer\run("cd $installPath && curl -sS -O " . INSTALLER_DOWNLOAD . " && chmod +x wp-cli.phar");
    if ($binaryName !== 'wp-cli.phar') {
        \Deployer\run("mv $installPath/wp-cli.phar $installPath/$binaryName");
    }
}

/**
 * Runs any WP-CLI command
 * Passes on the verbosity flags passed to Deployer CLI
 *
 * @param string $path Path in which to run WP-CLI command
 * @param string $command WP-CLI command to run
 * @param string $arguments Command-line arguments to be passed to WP-CLI as a string
 * @return string Result/Returned output from CLI
 */
function runCommand(string $command, string $path = '{{deploy_path}}', string $arguments = '') : string
{
    return \Deployer\run("cd $path && {{bin/wp}} $command $arguments");
}
