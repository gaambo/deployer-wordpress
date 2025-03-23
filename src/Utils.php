<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\output;

class Utils
{
    /**
     * Get the verbosity argument based on Deployer's output verbosity
     *
     * @return string
     */
    public static function getVerbosityArgument(): string
    {
        $outputInterface = output();
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
} 