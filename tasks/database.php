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
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\testLocally;
use function Deployer\upload;

/**
 * Create backup of remote database and download locally
 *
 * Configuration:
 * - dbdump_path: Directory to store database dumps (required on both local and remote)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 *
 * Example:
 *     dep db:remote:backup prod
 */
task('db:remote:backup', function () {
    $localDumpPath = Localhost::getConfig('dbdump_path');
    $remoteDumpPath = get('dbdump_path');
    $now = date('Y-m-d_H-i', time());
    $dumpFile = "db_backup-$now.sql";
    set('dbdump/file', $dumpFile);

    run('mkdir -p ' . get('dbdump_path'));
    WPCLI::runCommand("db export $remoteDumpPath/$dumpFile --add-drop-table", "{{release_or_current_path}}");

    Localhost::run("mkdir -p $localDumpPath");
    download("$remoteDumpPath/$dumpFile", "$localDumpPath/$dumpFile");
})->desc('Create backup of remote database and download locally');

/**
 * Create backup of local database and upload to remote
 *
 * Configuration:
 * - dbdump_path: Directory to store database dumps (required on both local and remote)
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 *
 * Example:
 *     dep db:local:backup prod
 */
task('db:local:backup', function () {
    $localDumpPath = Localhost::getConfig('dbdump_path');
    $remoteDumpPath = get('dbdump_path');
    $now = date('Y-m-d_H-i', time());
    $dumpFile = "db_backup-$now.sql";
    set('dbdump/file', $dumpFile);

    Localhost::run("mkdir -p $localDumpPath");
    WPCLI::runCommandLocally("db export $localDumpPath/$dumpFile --add-drop-table");

    run('mkdir -p {{dbdump_path}}');
    upload(
        "$localDumpPath/$dumpFile",
        "$remoteDumpPath/$dumpFile"
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
    if (!has('dbdump/file') || !test('[ -f {{dbdump_path}}/{{dbdump/file}} ]')) {
        throw new \RuntimeException('Database dump file not found at {{dbdump_path}}/{{dbdump/file}}');
    }

    $localUrl = Localhost::getConfig('public_url');
    WPCLI::runCommand("db import {{dbdump_path}}/{{dbdump/file}}");
    WPCLI::runCommand("search-replace $localUrl {{public_url}}");

    // If the local uploads directory is different from the remote one
    // replace all references to the local uploads directory with the remote one
    $localUploadsDir = Localhost::getConfig('uploads/dir');
    if ($localUploadsDir !== get('uploads/dir')) {
        WPCLI::runCommand("search-replace $localUploadsDir {{uploads/dir}}");
    }

    run('rm -f {{dbdump_path}}/{{dbdump/file}}');
})->desc('Import database backup on remote host');

/**
 * Import database backup on local host
 *
 * Configuration:
 * - bin/wp: WP-CLI binary/command to use (automatically configured)
 * - public_url: Site URL for both local and remote (required for URL replacement)
 * - uploads/dir: Upload directory path (for path replacement if different between environments)
 * - dbdump_path: Directory containing database dumps
 *
 * Example:
 *     dep db:local:import prod
 */
task('db:local:import', function () {
    // Check if dump file exists
    $localDumpPath = Localhost::getConfig('dbdump_path');
    $dumpFile = get('dbdump/file');
    if (!has('dbdump/file') || !testLocally("[ -f $localDumpPath/$dumpFile ]")) {
        throw new \RuntimeException("Database dump file not found at $localDumpPath/$dumpFile");
    }
    $remoteUrl = get('public_url');
    WPCLI::runCommandLocally("db import $localDumpPath/$dumpFile");
    WPCLI::runCommandLocally("search-replace $remoteUrl {{public_url}}");

    // If the local uploads directory is different from the remote one
    // replace all references to the remotes uploads directory with the local one
    $remoteUploadsDir = get('uploads/dir');
    if ($remoteUploadsDir !== Localhost::getConfig('uploads/dir')) {
        WPCLI::runCommandLocally("search-replace $remoteUploadsDir {{uploads/dir}}");
    }

    Localhost::run("rm -f $localDumpPath/$dumpFile");
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
