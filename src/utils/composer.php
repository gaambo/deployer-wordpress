<?php
/**
 * Provides helper functions for running composer commands
 */

namespace Gaambo\DeployerWordpress\Utils\Composer;

/**
 * Runs the default composer command defined in {{composer_options}}
 *
 * @param string $path Path in which to run composer command
 * @return string Result/Returned output from CLI
 */
function runDefault(string $path) : string
{
    return runCommand($path, '', '{{composer_options}}');
}

/**
 * Run a composer script
 *
 * @param string $path Path in which to run script
 * @param string $script Script-name defined in composer.json to be run
 * @param string $arguments Command-line arguments to be passed to composer as a string
 * @return string Result/Returned output from CLI
 */
function runScript(string $path, string $script, string $arguments = '') : string
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
function runCommand(string $path, string $command, string $arguments = '') : string
{
    $verbosityArgument = \Deployer\isVerbose() ? '-v' : '';
    return \Deployer\run("cd $path && {{bin/composer}} $command $arguments $verbosityArgument");
}
