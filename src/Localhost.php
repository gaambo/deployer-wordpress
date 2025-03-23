<?php

namespace Gaambo\DeployerWordpress;

use Deployer\Host\Host;

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
        return \Deployer\Deployer::get()->hosts->get('localhost');
    }
} 