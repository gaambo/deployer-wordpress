<?php

/**
 * WordPress File Management Tasks
 *
 * This file provides tasks for managing all WordPress files, including:
 * - Combined tasks for pushing/pulling all WordPress components
 * - Coordinating file operations across different WordPress directories
 * - Managing file synchronization between environments
 *
 * @package Gaambo\DeployerWordpress\Tasks
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\task;

require __DIR__ . '/../vendor/gaambo/deployer-utils/tasks/files.php';

require_once __DIR__ . '/mu-plugins.php';
require_once __DIR__ . '/packages.php';
require_once __DIR__ . '/plugins.php';
require_once __DIR__ . '/themes.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/wp.php';

/**
 * Push all files from local to remote
 *
 * Runs wp:push, uploads:push, plugins:push, mu-plugins:push, themes:push, packages:push in series.
 * See individual task definitions for required configuration options.
 *
 * Example:
 *     dep files:push prod
 */
task('files:push', ['wp:push', 'uploads:push', 'plugins:push', 'mu-plugins:push', 'themes:push', 'packages:push'])
    ->desc('Push all files from local to remote');

/**
 * Pull all files from remote to local
 *
 * Runs wp:pull, uploads:pull, plugins:pull, mu-plugins:pull, themes:pull, packages:pull in series.
 * See individual task definitions for required configuration options.
 *
 * Example:
 *     dep files:pull prod
 */
task('files:pull', ['wp:pull', 'uploads:pull', 'plugins:pull', 'mu-plugins:pull', 'themes:pull', 'packages:pull'])
    ->desc('Pull all files from remote to local');
