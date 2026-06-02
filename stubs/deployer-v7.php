<?php

/**
 * PHPStan stubs for Deployer v7 symbols that were removed in v8.
 * PHPStan runs against the installed version (v8), so v7-only functions are
 * declared here so the v7 compatibility code paths still type-check.
 */

namespace Deployer\Support {
    if (!function_exists('Deployer\\Support\\escape_shell_argument')) {
        function escape_shell_argument(string $argument): string
        {
            return "'" . str_replace("'", "'\\''", $argument) . "'";
        }
    }
}

namespace Deployer {
    /**
     * Shell-quote a string (added in Deployer v8, not present in v7).
     * Declared here so v7 analysis resolves the symbol.
     * Guard against redeclaration when v8 is installed and already defines it.
     */
    if (!function_exists('Deployer\\quote')) {
        function quote(string $arg): string
        {
            return escapeshellarg($arg);
        }
    }
}
