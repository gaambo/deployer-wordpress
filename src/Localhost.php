<?php

namespace Gaambo\DeployerWordpress;

use Deployer\Configuration\Configuration;
use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Task\Context;

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
        // Let's build a configuration object that has all the localhost values
        // But has the current context and global deployer context as parents to fallback.
        $config = new Configuration();
        $config->update(self::get()->config()->ownValues());
        if (Context::has()) {
            $config->bind(Context::get()->getConfig());
        } else {
            $config->bind(Deployer::get()->config);
        }
        $command = $config->parse($command);
        return runLocally($command, $options);
    }
}
