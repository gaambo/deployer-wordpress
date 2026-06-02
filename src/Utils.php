<?php

namespace Gaambo\DeployerWordpress;

use Composer\InstalledVersions;

use function Deployer\output;
use function Deployer\quote as deployerQuote; // Deployer v8
use function Deployer\Support\escape_shell_argument;

// Deployer v7

class Utils
{
    /** @var array<string,bool> Cached results keyed by "$operator$version". */
    private static array $versionCache = [];

    /**
     * Check the installed deployer/deployer version against a given constraint.
     *
     * Example: Utils::isDeployerVersion('>=', '8.0.0')
     *
     * @param string $operator A version_compare operator: '>=', '>', '==', '<', '<=', '!='
     * @param string $version  Version string to compare against, e.g. '8.0.0'
     */
    public static function isDeployerVersion(string $operator, string $version): bool
    {
        $key = $operator . $version;
        if (!array_key_exists($key, self::$versionCache)) {
            self::$versionCache[$key] = version_compare(
                InstalledVersions::getVersion('deployer/deployer') ?? '0.0.0',
                $version,
                $operator
            );
        }
        return self::$versionCache[$key];
    }

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

    /**
     * Deployer v7/v8 compat: v7 uses Deployer\Support\escape_shell_argument, v8 uses Deployer\quote.
     */
    public static function quote(string $arg): string
    {
        if (self::isDeployerVersion('>=', '8.0.0')) {
            return deployerQuote($arg);
        }
        return escape_shell_argument($arg);
    }
}
