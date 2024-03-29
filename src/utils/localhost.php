<?php

/**
 * Provides helper functions to get configuration from localhost host
 * Localhost host configuration is used to define some local paths for local tasks
 * But local tasks always create a new "anonymous" localhost object, so this method helps to get local paths
 */

namespace Gaambo\DeployerWordpress\Utils\Localhost;

/**
 * Get the (single) defined localhost host
 *
 * @return \Deployer\Host\Host Localhost host
 */
function getLocalhost(): \Deployer\Host\Host
{
    return \Deployer\Deployer::get()->hosts->get('localhost');
}
