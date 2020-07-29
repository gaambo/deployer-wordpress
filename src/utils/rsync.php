<?php
/**
 * Provides helper functions to build rsync options for rsync
 * Can be used with download & upload functions of Deployer too
 */

namespace Gaambo\DeployerWordpress\Utils\Rsync;

/**
 * Build excludes command-line arguments from array/file
 *
 * @param array $excludes Array of paths/files to exclude
 * @param string|null $excludeFile Path to a exclude file to pass to rsync
 * @return string Excludes as command-line arguments which can be passed to rsync
 */
function buildExcludes(array $excludes = [], ?string $excludeFile = null) : string
{
    $excludesString = '';
    foreach ($excludes as $exclude) {
        $excludesString .= ' --exclude=' . escapeshellarg($exclude);
    }

    if (!empty($excludeFile) && file_exists($excludeFile) && is_file($excludeFile) && is_readable($excludeFile)) {
        $excludesString .= ' --exclude-from=' . escapeshellarg($excludeFile);
    }

    return $excludesString;
}

/**
 * Build includes command-line arguments from array/file
 *
 * @param array $includes Array of paths/files to include
 * @param string|null $includeFile Path to a include file to pass to rsync
 * @return string Includes as command-line arguments which can be passed to rsync
 */
function buildIncludes(array $includes = [], ?string $includeFile = null) : string
{
    $includesString = '';
    foreach ($includes as $include) {
        $includesString .= ' --include=' . escapeshellarg($include);
    }

    if (!empty($includeFile) && file_exists($includeFile) && is_file($includeFile) && is_readable($includeFile)) {
        $includesString .= ' --include-from=' . escapeshellarg($includeFile);
    }

    return $includesString;
}

/**
 * Build filter command-line arguments from array/file/filePerDir
 *
 * @param array $filters Array of rsync filters
 * @param string|null $filterFile Path to a filterFile in rsync filter syntax
 * @param string|null $filterPerDir Filename to be used on a per directory basis for rsync filtering
 * @return string Filters as command-line arguments which can be passed to rsync
 */
function buildFilter(array $filters = [], ?string $filterFile = null, ?string $filterPerDir = null) : string
{
    $filtersString = '';
    foreach ($filters as $filter) {
        $filtersString.=" --filter='$filter'";
    }
    if (!empty($filterFile)) {
        $filtersString .= " --filter='merge $filterFile'";
    }
    if (!empty($filterPerDir)) {
        $filtersString .= " --filter='dir-merge $filterPerDir'";
    }
    return $filtersString;
}

/**
 * Build rsync options and flags
 *
 * @param array $options Array of options/flags to be passed to rsync - do not include dashes!
 * @return string Options as command-line arguments which can be passed to rsync
 */
function buildOptions(array $options) : string
{
    $optionsString = '';
    foreach ($options as $option) {
        $optionsString .= ' --' . $option;
    }

    return $optionsString;
}

/**
 * Build rsync options array
 * Takes a full config array and builds the different command-line arguments to be passed to rsync
 * Combines all config-build functions of this file
 *
 * @param array $config
 *  $config = [
 *      'excludes' => (array) Array of paths/files to exclude
 *      'exclude-file' => (string) Path to a exclude file to pass to rsync
 *      'includes' => (array) Array of paths/files to include
 *      'include-file' => (string) Path to a include file to pass to rsync
 *      'filters' => (array) Array of rsync filters
 *      'filters-file' => (string) Path to a filterFile in rsync filter syntax
 *      'filters-perdir' => (string) Filename to be used on a per directory basis for rsync filtering
 *      'options' => (array) Array of options/flags to be passed to rsync - do not include dashes!
 *  ]
 * @return array
 */
function buildOptionsArray(array $config = []) : array
{
    $defaultConfig = \Deployer\get('rsync');
    if (!$defaultConfig || !is_array($defaultConfig)) {
        $defaultConfig = [
            'excludes' => [
                '.git',
                'deploy.php',
            ],
            'exclude-file' => false,
            'includes' => [],
            'include-file' => false,
            'filters' => [],
            'filter-file' => false,
            'filter-perdir' => false,
            'options' => ['delete-after'], // needed so deployfilter files are send and delete is checked afterwards
        ];
    }


    $mergedConfig = array_merge($defaultConfig, $config);

    $options = [];
    $options[] = buildOptions($mergedConfig['options']);
    $options[] = buildIncludes($mergedConfig['includes'], $mergedConfig['include-file']);
    $options[] = buildExcludes($mergedConfig['excludes'], $mergedConfig['exclude-file']);
    $options[] = buildFilter($mergedConfig['filters'], $mergedConfig['filter-file'], $mergedConfig['filter-perdir']);

    return $options;
}
