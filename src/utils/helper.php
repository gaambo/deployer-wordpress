<?php

/**
 * Provides helper functions for running composer commands
 */

namespace Gaambo\DeployerWordpress\Utils\Composer;

function gerVerbosityArgument()
{
    $outputInterface = \Deployer\output();
    $verbosityArgument = '';
    if ($outputInterface->isVerbose()) {
        $verbosityArgument = '-v';
    }
    if ($outputInterface->isVeryVerbose()) {
        $verbosityArgument = '-vv';
    }
    if ($outputInterface->isDebug()) {
        $verbosityArgument = '-vvv';
    }
    return $verbosityArgument;
}
