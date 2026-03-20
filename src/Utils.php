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

    /**
     * Parses an array of mixed values and returns an array of strings.
     * @param array<mixed> $array
     * @return array<string>
     */
    public static function parseStringArray(array $array): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn($value) => is_string($value) ? $value : null,
                    $array
                ),
            )
        );
    }

    /**
     * Parses a mixed value and returns a string or null if the value is not a string.
     * @param mixed $string
     * @return string|null
     */
    public static function parseStringOrNull(mixed $string): ?string
    {
        return is_string($string) ? $string : null;
    }
}
