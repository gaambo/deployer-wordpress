<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\get;

/**
 * @phpstan-type RsyncConfig array{
 *      exclude?: array<int, string>,
 *      'exclude-file'?: string|false,
 *      include?: array<int, string>,
 *      'include-file'?: string|false,
 *      filter?: array<int, string>,
 *      'filter-file'?: string|false,
 *      'filter-perdir'?: string|false,
 *      options?: array<int, string>
 * }
 *
 * @phpstan-type RsyncOptions list<string>
 *
 */
class Rsync
{
    public const DEFAULT_CONFIG = [
        'exclude' => [
            '.git',
            'deploy.php',
        ],
        'exclude-file' => false,
        'include' => [],
        'include-file' => false,
        'filter' => [],
        'filter-file' => false,
        'filter-perdir' => false,
        'options' => ['delete-after'], // needed so deployfilter files are send and delete is checked afterward
    ];

    /**
     * Builds a comprehensive array of rsync command options
     * by merging default (set in `rsync` config) and custom configurations.
     * Takes a full config array and builds the different command-line arguments to be passed to rsync
     * Combines all config-build functions of this file.
     *
     * @example
     * // Using default configuration
     * $options = buildRsyncOptions();
     * // Returns ['--delete-after', '--exclude=.git', '--exclude=deploy.php']
     *
     * @example
     * // With custom configuration
     * $options = buildRsyncOptions([
     *     'exclude' => ['.git', 'node_modules'],
     *     'include' => ['dist'],
     *     'options' => ['archive', 'verbose']
     * ]);
     * // Returns ['--archive', '--verbose', '--include=dist', '--exclude=.git', '--exclude=node_modules']
     *
     * @param RsyncConfig $config Custom rsync configuration.
     * @return RsyncOptions Array of formatted rsync command options with empty values filtered out
     */
    public static function buildOptionsArray(array $config = []): array
    {
        // Get default config or use fallback
        $defaultConfig = get('rsync');
        if (!$defaultConfig || !is_array($defaultConfig)) {
            $defaultConfig = [...self::DEFAULT_CONFIG];
        }

        $options = array_key_exists('options', $config) ? $config['options'] : ($defaultConfig['options'] ?? []);
        $options = Utils::parseStringArray((array) $options);
        $exclude = array_key_exists('exclude', $config) ? $config['exclude'] : ($defaultConfig['exclude'] ?? []);
        $exclude = Utils::parseStringArray((array) $exclude);
        $excludeFile = array_key_exists('exclude-file', $config) ?
            $config['exclude-file'] : ($defaultConfig['exclude-file'] ?? null);
        $excludeFile = Utils::parseStringOrNull($excludeFile);
        $include = array_key_exists('include', $config) ?
            $config['include'] : ($defaultConfig['include'] ?? []);
        $include = Utils::parseStringArray((array) $include);
        $includeFile = array_key_exists('include-file', $config) ?
            $config['include-file'] : ($defaultConfig['include-file'] ?? null);
        $includeFile = Utils::parseStringOrNull($includeFile);
        $filter = array_key_exists('filter', $config) ? $config['filter'] : ($defaultConfig['filter'] ?? []);
        $filter = Utils::parseStringArray((array) $filter);
        $filterFile = array_key_exists('filter-file', $config) ?
            $config['filter-file'] : ($defaultConfig['filter-file'] ?? null);
        $filterFile = Utils::parseStringOrNull($filterFile);
        $filterPerDir = array_key_exists('filter-perdir', $config) ?
            $config['filter-perdir'] : ($defaultConfig['filter-perdir'] ?? null);
        $filterPerDir = Utils::parseStringOrNull($filterPerDir);

        // Build options
        $options = self::buildOptions($options);
        $includes = self::buildIncludes($include, $includeFile);
        $excludes = self::buildExcludes($exclude, $excludeFile);
        $filters = self::buildFilter($filter, $filterFile, $filterPerDir);

        // remove empty strings because they break rsync
        // because Rsync class uses escapeshellarg
        return array_filter([...$options, ...$includes, ...$excludes, ...$filters]);
    }

    /**
     * Builds an array of rsync option arguments by prefixing each option with '--'.
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options.
     *
     * @example
     * // Returns ['--archive', '--verbose', '--compress']
     * buildOptions(['archive', 'verbose', 'compress']);
     *
     * @example
     * // Returns ['--dry-run', '--itemize-changes']
     * buildOptions(['dry-run', 'itemize-changes']);
     *
     * @param list<string> $options Array of rsync option names without the '--' prefix
     *                             (e.g. ['archive', 'verbose', 'compress'])
     *
     * @return list<string> Array of formatted option arguments where each element
     *                     has the format '--{option_name}'
     */
    public static function buildOptions(array $options): array
    {
        $optionsStrings = [];
        foreach ($options as $option) {
            $optionsStrings[] = '--' . $option;
        }

        return $optionsStrings;
    }

    /**
     * Builds an array of rsync include arguments based on provided include patterns and include file.
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     * @example
     * // Returns ['--include=*.php', '--include=/config/']
     * buildIncludes(['*.php', '/config/']);
     *
     * @example
     * // Returns ['--include=*.php', '--include-from=/path/to/include-list.txt']
     * buildIncludes(['*.php'], '/path/to/include-list.txt');
     *
     * @param list<string> $includes Array of include patterns to be applied (e.g. ["*.php", "/config/"])
     * @param string|null $includeFile Path to a file containing include patterns (e.g. "/path/to/include-list.txt")
     *
     * @return list<string> Array where each element is one of:
     *                     - '--include={include_pattern}' for each entry in $includes
     *                     - '--include-from={include_file_path}' if $includeFile is valid
     */
    public static function buildIncludes(array $includes = [], ?string $includeFile = null): array
    {
        $includesStrings = [];
        foreach ($includes as $include) {
            $includesStrings[] = '--include=' . $include;
        }

        if (!empty($includeFile) && file_exists($includeFile) && is_file($includeFile) && is_readable($includeFile)) {
            $includesStrings[] = '--include-from=' . $includeFile;
        }

        return $includesStrings;
    }

    /**
     * Builds an array of rsync exclude arguments based on provided exclude patterns and exclude file.
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     *
     * @example
     * // Returns ['--exclude=*.tmp', '--exclude=/cache/']
     * buildExcludes(['*.tmp', '/cache/']);
     *
     * @example
     * // Returns ['--exclude=*.tmp', '--exclude-from=/path/to/exclude-list.txt']
     * buildExcludes(['*.tmp'], '/path/to/exclude-list.txt');
     *
     * @param list<string> $excludes Array of exclude patterns to be applied (e.g. ["*.tmp", "/cache/"])
     * @param string|null $excludeFile Path to a file containing exclude patterns (e.g. "/path/to/exclude-list.txt")
     *
     * @return list<string> Array where each element is one of:
     *                     - '--exclude={exclude_pattern}' for each entry in $excludes
     *                     - '--exclude-from={exclude_file_path}' if $excludeFile is valid
     */
    public static function buildExcludes(array $excludes = [], ?string $excludeFile = null): array
    {
        $excludesStrings = [];
        foreach ($excludes as $exclude) {
            $excludesStrings[] = '--exclude=' . $exclude;
        }

        if (!empty($excludeFile) && file_exists($excludeFile) && is_file($excludeFile) && is_readable($excludeFile)) {
            $excludesStrings[] = '--exclude-from=' . $excludeFile;
        }

        return $excludesStrings;
    }

    /**
     * Builds an array of rsync filter arguments based on provided filters and filter files.
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     *
     * @example
     * // Returns ['--filter=exclude=/tmp', '--filter=include=/var']
     * buildFilter(['exclude=/tmp', 'include=/var']);
     *
     * @example
     * // Returns ['--filter=exclude=/tmp', '--filter=merge /path/to/rules.txt', '--filter=dir-merge .rsync-filter']
     * buildFilter(['exclude=/tmp'], '/path/to/rules.txt', '.rsync-filter');
     *
     * @param list<string> $filters Array of filter patterns to be applied (e.g. ["exclude=/path", "include=/other"])
     * @param string|null $filterFile Path to a filter file to be merged (e.g. "/path/to/filter-rules.txt")
     * @param string|null $filterPerDir Name of per-directory filter files to be merged (e.g. ".rsync-filter")
     *
     * @return list<string> Array where each element is one of:
     *                     - '--filter={filter_pattern}' for each entry in $filters
     *                     - '--filter=merge {filter_file_path}' if $filterFile is valid
     *                     - '--filter=dir-merge {filter_per_dir}' if $filterPerDir is provided
     */
    public static function buildFilter(
        array $filters = [],
        ?string $filterFile = null,
        ?string $filterPerDir = null
    ): array {
        $filtersStrings = [];
        foreach ($filters as $filter) {
            $filtersStrings[] = '--filter=' . $filter;
        }
        if (!empty($filterFile) && file_exists($filterFile) && is_file($filterFile) && is_readable($filterFile)) {
            $filtersStrings[] = "--filter=merge " . $filterFile;
        }
        if (!empty($filterPerDir)) {
            $filtersStrings[] = "--filter=dir-merge " . $filterPerDir;
        }
        return $filtersStrings;
    }
}
