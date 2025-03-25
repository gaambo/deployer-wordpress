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

use Gaambo\DeployerWordpress\Localhost;
use Gaambo\DeployerWordpress\WPCLI;

use function Deployer\download;
use function Deployer\get;
use function Deployer\has;
use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\testLocally;
use function Deployer\upload;

/**
 * Create backup of remote database and download locally
 *
 * Configuration:
 * - dbdump/path: Directory to store database dumps (required on both local and remote)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 *
 * Example:
 *     dep db:remote:backup prod
 */
task('db:remote:backup', function () {
    $localDumpPath = Localhost::getConfig('dbdump/path');
    $remoteDumpPath = get('dbdump/path');
    $now = date('Y-m-d_H-i', time());
    set('dbdump/file', "db_backup-$now.sql");

    run('mkdir -p ' . get('dbdump/path'));
    WPCLI::runCommand("db export $remoteDumpPath/{{dbdump/file}} --add-drop-table", "{{release_or_current_path}}");

    runLocally("mkdir -p $localDumpPath");
    download("$remoteDumpPath/{{dbdump/file}}", "$localDumpPath/{{dbdump/file}}");
})->desc('Create backup of remote database and download locally');

/**
 * Create backup of local database and upload to remote
 *
 * Configuration:
 * - dbdump/path: Directory to store database dumps (required on both local and remote)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 *
 * Example:
 *     dep db:local:backup prod
 */
task('db:local:backup', function () {
    $localDumpPath = Localhost::getConfig('dbdump/path');
    $remoteDumpPath = get('dbdump/path');
    $now = date('Y-m-d_H-i', time());
    set('dbdump/file', "db_backup-$now.sql");

    runLocally("mkdir -p $localDumpPath");
    WPCLI::runCommandLocally("db export $localDumpPath/{{dbdump/file}} --add-drop-table");

    run('mkdir -p {{dbdump/path}}');
    upload(
        "$localDumpPath/{{dbdump/file}}",
        "$remoteDumpPath/{{dbdump/file}}"
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
    // Check if dump file exists
    if (!has('dbdump/file') || !test('[ -f {{dbdump/path}}/{{dbdump/file}} ]')) {
        throw new \RuntimeException('Database dump file not found at {{dbdump/path}}/{{dbdump/file}}');
    }

    $localUrl = Localhost::getConfig('public_url');
    WPCLI::runCommand("db import {{dbdump/path}}/{{dbdump/file}}", "{{release_or_current_path}}");
    WPCLI::runCommand("search-replace $localUrl {{public_url}}", "{{release_or_current_path}}");

    // If the local uploads directory is different from the remote one
    // replace all references to the local uploads directory with the remote one
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    if ($localUploadsDir !== get('uploads/dir')) {
        WPCLI::runCommand("search-replace $localUploadsDir {{uploads/dir}}", "{{release_or_current_path}}");
    }

    run('rm -f {{dbdump/path}}/{{dbdump/file}}');
})->desc('Import database backup on remote host');

/**
 * Import database backup on local host
 *
 * Configuration:
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * - public_url: Site URL for both local and remote (required for URL replacement)
 * - uploads/dir: Upload directory path (for path replacement if different between environments)
 * - dbdump/path: Directory containing database dumps
 *
 * Example:
 *     dep db:local:import prod
 */
task('db:local:import', function () {
    // Check if dump file exists
    $localDumpPath = Localhost::getConfig('dbdump/path');
    if (!has('dbdump/file') || !testLocally("[ -f $localDumpPath/{{dbdump/file}} ]")) {
        throw new \RuntimeException("Database dump file not found at $localDumpPath/{{dbdump/file}}");
    }
    $localUrl = Localhost::getConfig('public_url');
    WPCLI::runCommandLocally("db import $localDumpPath/{{dbdump/file}}");
    WPCLI::runCommandLocally("search-replace {{public_url}} $localUrl");

    // If the local uploads directory is different from the remote one
    // replace all references to the remotes uploads directory with the local one
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    if ($localUploadsDir !== get('uploads/dir')) {
        WPCLI::runCommandLocally("search-replace {{uploads/dir}} $localUploadsDir");
    }

    runLocally("rm -f $localDumpPath/{{dbdump/file}}");
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
