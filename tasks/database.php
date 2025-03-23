<?php

/**
 * WordPress Database Tasks
 *
 * This file provides tasks for managing WordPress databases, including:
 * - Creating and importing database backups
 * - Pushing/pulling databases between environments
 * - Performing URL/path replacements during sync
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\download;
use function Deployer\get;
use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;
use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\WPCLI;

/**
 * Create backup of remote database and download locally
 * 
 * Configuration:
 * - dump_path: Directory to store database dumps (required on both local and remote)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * 
 * Example:
 *     dep db:remote:backup prod
 */
task('db:remote:backup', function () {
    $localDumpPath = Localhost::getConfig('dump_path');
    $now = date('Y-m-d_H-i', time());
    set('dump_file', "db_backup-$now.sql");
    set('dump_filepath', get('dump_path') . '/' . get('dump_file'));

    run('mkdir -p ' . get('dump_path'));
    WPCLI::runCommand("db export {{dump_filepath}} --add-drop-table", "{{release_or_current_path}}");

    runLocally("mkdir -p $localDumpPath");
    download('{{dump_filepath}}', "$localDumpPath/{{dump_file}}");
})->desc('Create backup of remote database and download locally');

/**
 * Create backup of local database and upload to remote
 * 
 * Configuration:
 * - dump_path: Directory to store database dumps (required on both local and remote)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * 
 * Example:
 *     dep db:local:backup prod
 */
task('db:local:backup', function () {
    $localDumpPath = Localhost::getConfig('dump_path');
    $now = date('Y-m-d_H-i', time());
    set('dump_file', "db_backup-$now.sql");
    set('dump_filepath', '{{dump_path}}/{{dump_file}}');

    runLocally("mkdir -p $localDumpPath");
    WPCLI::runCommandLocally("db export $localDumpPath/{{dump_file}} --add-drop-table");

    run('mkdir -p {{dump_path}}');
    upload(
        "$localDumpPath/{{dump_file}}",
        '{{dump_filepath}}'
    );
})->desc('Create backup of local database and upload to remote');

/**
 * Import database backup on remote host
 * 
 * Configuration:
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * - public_url: Site URL for both local and remote (required for URL replacement)
 * - uploads/dir: Upload directory path (for path replacement if different between environments)
 * 
 * Example:
 *     dep db:remote:import prod
 */
task('db:remote:import', function () {
    $localUrl = Localhost::getConfig('public_url');
    WPCLI::runCommand("db import {{dump_filepath}}", "{{release_or_current_path}}");
    WPCLI::runCommand("search-replace $localUrl {{public_url}}", "{{release_or_current_path}}");

    // If the local uploads directory is different than the remote one
    // replace all references to the local uploads directory with the remote one
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    if ($localUploadsDir !== get('uploads/dir')) {
        WPCLI::runCommand("search-replace $localUploadsDir {{uploads/dir}}", "{{release_or_current_path}}");
    }

    run('rm -f {{dump_filepath}}');
})->desc('Import database backup on remote host');

/**
 * Import database backup on local host
 * 
 * Configuration:
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * - public_url: Site URL for both local and remote (required for URL replacement)
 * - uploads/dir: Upload directory path (for path replacement if different between environments)
 * - dump_path: Directory containing database dumps
 * 
 * Example:
 *     dep db:local:import prod
 */
task('db:local:import', function () {
    $localUrl = Localhost::getConfig('public_url');
    $localDumpPath = Localhost::getConfig('dump_path');
    WPCLI::runCommandLocally("db import $localDumpPath/{{dump_file}}");
    WPCLI::runCommandLocally("search-replace {{public_url}} $localUrl");

    // If the local uploads directory is different than the remote one
    // replace all references to the remotes uploads directory with the local one
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    if ($localUploadsDir !== get('uploads/dir')) {
        WPCLI::runCommandLocally("search-replace {{uploads/dir}} $localUploadsDir");
    }

    runLocally("rm -f $localDumpPath/{{dump_file}}");
})->desc('Import database backup on local host');

/**
 * Push database from local to remote
 * 
 * Combines db:local:backup and db:remote:import tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep db:push prod
 */
task('db:push', ['db:local:backup', 'db:remote:import'])
    ->desc('Push database from local to remote');

/**
 * Pull database from remote to local
 * 
 * Combines db:remote:backup and db:local:import tasks.
 * See individual tasks for configuration options.
 * 
 * Example:
 *     dep db:pull prod
 */
task('db:pull', ['db:remote:backup', 'db:local:import'])
    ->desc('Pull database from remote to local');
