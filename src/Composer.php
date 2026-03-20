<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\run;

class Composer
{
    private const INSTALLER_DOWNLOAD = 'https://getcomposer.org/installer';

    /**
     * Run default composer command
     * Uses Deployers run function
     *
     * @param string $path Path in which to run composer command
     * @return string Output of the command
     */
    public static function runDefault(string $path): string
    {
        return self::runCommand($path, '{{composer_action}}', '{{composer_options}}');
    }

    /**
     * Run any composer command with verbosity flags
     * Uses Deployers run function
     *
     * @param string $path Path in which to run composer command
     * @param string $command Composer command to run
     * @param string $arguments Command-line arguments to be passed to composer
     * @return string Output of the command
     */
    public static function runCommand(string $path, string $command, string $arguments = ''): string
    {
        $verbosityArgument = Utils::getVerbosityArgument();

        $runCommand = "cd $path && {{bin/composer}} $command";
        if ($arguments !== '') {
            $runCommand .= " $arguments";
        }
        if ($verbosityArgument !== '') {
            $runCommand .= " $verbosityArgument";
        }

        return run($runCommand);
    }

    /**
     * Run a composer script
     * Uses Deployers run function
     *
     * @param string $path Path in which to run script
     * @param string $script Script-name defined in composer.json to be run
     * @param string $arguments Command-line arguments to be passed to composer
     * @return string Output of the command
     */
    public static function runScript(string $path, string $script, string $arguments = ''): string
    {
        return self::runCommand($path, "run-script $script", $arguments);
    }

    /**
     * Install composer binary
     * @param string $installPath Path where to install the binary
     * @param string $binaryName Name for the binary file
     * @param bool $sudo Whether to use sudo for moving the binary
     * @return string Path to installed and moved binary file
     */
    public static function install(
        string $installPath,
        string $binaryName = 'composer.phar',
        bool $sudo = false
    ): string {
        $sudoPrefix = $sudo ? 'sudo ' : '';

        run($sudoPrefix . "mkdir -p $installPath");
        run($sudoPrefix . "cd $installPath && curl -sS " . self::INSTALLER_DOWNLOAD . " | {{bin/php}}");

        if ($binaryName !== 'composer.phar') {
            run($sudoPrefix . "mv $installPath/composer.phar $installPath/$binaryName");
        }

        return "$installPath/$binaryName";
    }
}
