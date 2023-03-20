<?php

/**
 * Provides helper functions for running npm commands
 */

namespace Gaambo\DeployerWordpress\Utils\Npm;

require_once 'utils/helper.php';

use function Gaambo\DeployerWordpress\Utils\Composer\gerVerbosityArgument;

/**
 * Run a npm script
 *
 * @param string $path Path in which to run script
 * @param string $script Script-name defined in package.json to be run
 * @param string $arguments Command-line arguments to be passed to npm as a string
 * @return string Result/Returned output from CLI
 */
function runScript(string $path, string $script, string $arguments = ''): string
{
    return runCommand($path, "run-script $script", $arguments);
}

/**
 * Run npm install
 * Tries to copy node_modules from previous release if available
 *
 * @param string $path Path in which to run npm install
 * @param string $arguments Command-line arguments to be passed to npm as a string
 * @return string Result/Returned output from CLI
 */
function runInstall(string $path, string $arguments = ''): string
{
    if (\Deployer\has('previous_release')) {
        if (\Deployer\test('[ -d {{previous_release}}/node_modules ]')) {
            \Deployer\run("cp -R {{previous_release}}/node_modules $path");
        }
    }
    return runCommand($path, 'install', $arguments);
}

/**
 * Run any npm command
 * Passes on the verbosity flags passed to Deployer CLI
 *
 * @param string $path Path in which to run npm command
 * @param string $action NPM action to be run
 * @param string $arguments Command-line arguments to be passed to npm as a string
 * @return string Result/Returned output from CLI
 */
function runCommand(string $path, string $action, string $arguments = ''): string
{
    $verbosityArgument = gerVerbosityArgument();
    $verbosityArgument = str_replace('v', 'd', $verbosityArgument); // npm takes d for verbosity argument
    return \Deployer\run("cd $path && {{bin/npm}} $action $arguments $verbosityArgument");
}
