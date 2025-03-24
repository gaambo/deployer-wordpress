<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\has;
use function Deployer\run;
use function Deployer\test;

class NPM
{
    /**
     * Run a npm script
     * Uses Deployers run function
     *
     * @param string $path Path in which to run script
     * @param string $script Script-name defined in package.json to be run
     * @param string $arguments Command-line arguments to be passed to npm
     * @return string Output of the command
     */
    public static function runScript(string $path, string $script, string $arguments = ''): string
    {
        return self::runCommand($path, "run-script $script", $arguments);
    }

    /**
     * Run any npm command
     * Uses Deployers run function
     *
     * @param string $path Path in which to run npm command
     * @param string $action NPM action to be run
     * @param string $arguments Command-line arguments to be passed to npm
     * @return string Output of the command
     */
    public static function runCommand(string $path, string $action, string $arguments = ''): string
    {
        $verbosityArgument = Utils::getVerbosityArgument();
        $verbosityArgument = str_replace('v', 'd', $verbosityArgument); // npm takes d for verbosity argument

        $command = "cd $path && {{bin/npm}} $action";
        if ($arguments !== '') {
            $command .= " $arguments";
        }
        if ($verbosityArgument !== '') {
            $command .= " $verbosityArgument";
        }

        return run($command);
    }

    /**
     * Run npm install
     * Tries to copy node_modules from previous release if available
     *
     * @param string $path Path in which to run npm install
     * @param string $arguments Command-line arguments to be passed to npm
     * @return string Output of the command
     */
    public static function runInstall(string $path, string $arguments = ''): string
    {
        if (has('previous_release')) {
            if (test('[ -d {{previous_release}}/node_modules ]')) {
                run("cp -R {{previous_release}}/node_modules $path");
            }
        }
        return self::runCommand($path, 'install', $arguments);
    }
}
