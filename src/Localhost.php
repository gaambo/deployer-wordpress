<?php

namespace Gaambo\DeployerWordpress;

use Deployer\Configuration\Configuration;
use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Task\Context;

use function Deployer\on;
use function Deployer\runLocally;

/**
 * Localhost utility class
 * Provides methods to get localhost configuration values
 */
class Localhost
{
    /**
     * Get a localhost configuration value
     * @param string $key The configuration key to get
     * @return mixed The configuration value
     */
    public static function getConfig(string $key): mixed
    {
        return self::get()->get($key);
    }

    /**
     * Get the (single) defined localhost host
     *
     * @return Host Localhost host
     */
    public static function get(): Host
    {
        $localhost = Deployer::get()->hosts->get('localhost');
        return $localhost;
    }

    /**
     * A wrapper around runLocally() which parses variables from the local host.
     * The problem with runLocally() is that it parses values from the global context which
     * does not use our Localhost instance.
     *
     * @param string $command Command to run on localhost.
     * @param string[]|null $options Array of options will override passed named arguments.
     * @return string
     */
    public static function run(string $command, ?array $options = []): string
    {
        $result = null;
        on(self::get(), function () use ($command, $options, &$result) {
            $result = runLocally($command, $options);
        });
        return $result;
    }
}
