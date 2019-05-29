<?php
/**
 * Provides tasks for pushing, pulling, syncing and backing up files
 * Includes tasks for uploads, plugins, mu-plugins, themes
 */

namespace Deployer;

require_once 'mu-plugins.php';
require_once 'plugins.php';
require_once 'themes.php';
require_once 'uploads.php';
require_once 'wp.php';

// Pushes all files from local to remote host
// Runs wp:push, uploads:push, plugins:push, mu-plugins:push, themes:push in series
// see tasks definitions for details and required variables
task('files:push', ['wp:push', 'uploads:push', 'plugins:push', 'mu-plugins:push', 'themes:push']);

// Pulls all files from remote to local host
// Runs wp:pull, uploads:pull, plugins:pull, mu-plugins:pull, themes:pull in series
// see tasks definitions for details and required variables
task('files:pull', ['wp:pull', 'uploads:pull', 'plugins:pull', 'mu-plugins:pull', 'themes:pull']);
