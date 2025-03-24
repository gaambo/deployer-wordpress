<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\get;

class Rsync
{
    /**
     * Build rsync options array
     * Takes a full config array and builds the different command-line arguments to be passed to rsync
     * Combines all config-build functions of this file
     *
     * @param array $config
     *  $config = [
     *      'exclude' => (array) Array of paths/files to exclude
     *      'exclude-file' => (string) Path to a exclude file to pass to rsync
     *      'include' => (array) Array of paths/files to include
     *      'include-file' => (string) Path to a include file to pass to rsync
     *      'filter' => (array) Array of rsync filters
     *      'filter-file' => (string) Path to a filterFile in rsync filter syntax
     *      'filter-perdir' => (string) Filename to be used on a per directory basis for rsync filtering
     *      'options' => (array) Array of options/flags to be passed to rsync - do not include dashes!
     *  ]
     * @return array
     */
    public static function buildOptionsArray(array $config = []): array
    {
        $defaultConfig = get('rsync');
        if (!$defaultConfig || !is_array($defaultConfig)) {
            $defaultConfig = [
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
                'options' => ['delete-after'], // needed so deployfilter files are send and delete is checked afterwards
            ];
        }

        $mergedConfig = array_merge($defaultConfig, $config);

        // Filter out empty strings from arrays before building options
        $mergedConfig['options'] = array_filter($mergedConfig['options']);
        $mergedConfig['exclude'] = array_filter($mergedConfig['exclude']);
        $mergedConfig['include'] = array_filter($mergedConfig['include']);
        $mergedConfig['filter'] = array_filter($mergedConfig['filter']);

        $options = array_merge(
            self::buildOptions($mergedConfig['options']),
            self::buildIncludes($mergedConfig['include'], $mergedConfig['include-file']),
            self::buildExcludes($mergedConfig['exclude'], $mergedConfig['exclude-file']),
            self::buildFilter($mergedConfig['filter'], $mergedConfig['filter-file'], $mergedConfig['filter-perdir'])
        );

        // remove empty strings because they break rsync
        // because Rsync class uses escapeshellarg
        return array_filter($options);
    }

    /**
     * Build rsync options and flags
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     *
     * @param array $options Array of options/flags to be passed to rsync - do not include dashes!
     * @return array Options as command-line arguments which can be passed to rsync
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
     * Build includes command-line arguments from array/file
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     *
     * @param array $includes Array of paths/files to include
     * @param string|null $includeFile Path to a include file to pass to rsync
     * @return array Includes as command-line arguments which can be passed to rsync
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
     * Build excludes command-line arguments from array/file
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     *
     * @param array $excludes Array of paths/files to exclude
     * @param string|null $excludeFile Path to a exclude file to pass to rsync
     * @return array Excludes as command-line arguments which can be passed to rsync
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
     * Build filter command-line arguments from array/file/filePerDir
     * Does not use escapeshellarg, because Rsync class from deployer uses it and that would destroy the options
     *
     * @param array $filters Array of rsync filters
     * @param string|null $filterFile Path to a filterFile in rsync filter syntax
     * @param string|null $filterPerDir Filename to be used on a per directory basis for rsync filtering
     * @return array Filters as command-line arguments which can be passed to rsync
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
            $filtersStrings[] = "--filter=merge " . $filterFile . "";
        }
        if (!empty($filterPerDir)) {
            $filtersStrings[] = "--filter=dir-merge " . $filterPerDir . "";
        }
        return $filtersStrings;
    }
}
