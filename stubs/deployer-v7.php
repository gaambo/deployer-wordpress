<?php

/**
 * PHPStan stubs for Deployer v7 symbols that were removed in v8.
 * PHPStan runs against the installed version (v8), so v7-only functions are
 * declared here so the v7 compatibility code paths still type-check.
 */

namespace Deployer\Support {
    /**
     * Escape a shell argument (replaced by Deployer\quote in v8).
     */
    function escape_shell_argument(string $argument): string
    {
        return "'" . str_replace("'", "'\\''", $argument) . "'";
    }
}
