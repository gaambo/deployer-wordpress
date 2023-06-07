<?php

/**
 * Provides helper functions for running composer commands
 */

namespace Gaambo\DeployerWordpress\Utils\Composer;

use function Deployer\run;

require_once 'utils/helper.php';

const INSTALLER_DOWNLOAD = 'https://getcomposer.org/installer';


/**
 * Runs the default composer command defined in {{composer_action}} with {{composer_options}}
 *
 * @param string $path Path in which to run composer command
 * @return string Result/Returned output from CLI
 */
function runDefault(string $path): string
{
    return runCommand($path, '{{composer_action}}', '{{composer_options}}');
}

/**
 * Run a composer script
 *
 * @param string $path Path in which to run script
 * @param string $script Script-name defined in composer.json to be run
 * @param string $arguments Command-line arguments to be passed to composer as a string
 * @return string Result/Returned output from CLI
 */
function runScript(string $path, string $script, string $arguments = ''): string
{
    return runCommand($path, "run-script $script", $arguments);
}

/**
 * Runs any composer command
 * Passes on the verbosity flags passed to Deployer CLI
 *
 * @param string $path Path in which to run composer command
 * @param string $command Composer command to run
 * @param string $arguments Command-line arguments to be passed to composer as a string
 * @return string Result/Returned output from CLI
 */
function runCommand(string $path, string $command, string $arguments = ''): string
{
    $verbosityArgument = gerVerbosityArgument();
    return \Deployer\run("cd $path && {{bin/composer}} $command $arguments $verbosityArgument");
}

/**
 * Installs the composer binary to a specified installPath
 * Allows passing a name for the binary file and using sudo (eg to move to /usr/local/bin)
 *
 * @param string $installPath
 * @param string $binaryName
 * @param boolean $sudo
 * @return string Path to installed and moved binary file
 */
function installComposer($installPath, $binaryName = 'composer.phar', $sudo = false)
{
    $sudoCommand = $sudo ? 'sudo ' : '';

    run("mkdir -p $installPath");
    run("cd $installPath && curl -sS " . INSTALLER_DOWNLOAD . " | {{bin/php}}");
    run('mv {{deploy_path}}/composer.phar {{deploy_path}}/.dep/composer.phar');
    if ($binaryName !== 'composer.phar') {
        run("$sudoCommand mv $installPath/composer.phar $installPath/$binaryName");
    }

    return "$installPath/$binaryName";
}
