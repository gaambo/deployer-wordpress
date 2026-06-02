<?php

/**
 * PHPStan stubs for Deployer symbols that differ between v7 and v8.
 *
 * PHPStan analyses against the Deployer version installed in the current environment, so we
 * declare functions that may be missing depending on whether v7 or v8 is installed.
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
