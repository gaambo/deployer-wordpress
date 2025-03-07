<?php

/**
 * Provides tasks for pushing, pulling, syncing and backing up files
 * Includes tasks for uploads, plugins, mu-plugins, themes
 */

namespace Gaambo\DeployerWordpress\Tasks;

use function Deployer\task;

require_once 'mu-plugins.php';
require_once 'packages.php';
require_once 'plugins.php';
require_once 'themes.php';
require_once 'uploads.php';
require_once 'wp.php';

// Pushes all files from local to remote host
// Runs wp:push, uploads:push, plugins:push, mu-plugins:push, themes:push, packages:push in series
// see tasks definitions for details and required variables
task('files:push', ['wp:push', 'uploads:push', 'plugins:push', 'mu-plugins:push', 'themes:push', 'packages:push'])
    // phpcs:ignore Generic.Files.LineLength.TooLong
    ->desc("Pushes all files from local to remote host (combines push for wp, uploads, plugins, mu-plugins, themes, packages`wp:push`, `uploads:push`, `plugins:push`, `mu-plugins:push`, `themes:push`, `packages:push`)");

// Pulls all files from remote to local host
// Runs wp:pull, uploads:pull, plugins:pull, mu-plugins:pull, themes:pull, packages:pull in series
// see tasks definitions for details and required variables
task('files:pull', ['wp:pull', 'uploads:pull', 'plugins:pull', 'mu-plugins:pull', 'themes:pull', 'packages:pull'])
    // phpcs:ignore Generic.Files.LineLength.TooLong
    ->desc("Pulls all files from remote to local host (combines `wp:pull`, `uploads:pull`, `plugins:pull`, `mu-plugins:pull`, `themes:pull`, `packages:pull`)");
