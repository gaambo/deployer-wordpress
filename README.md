# Deployer WordPress Recipes

[Deployer](https://deployer.org) tasks and recipes for deploying WordPress sites. Supports simple rsync deployments,
custom theme/plugin builds, and complex setups including Bedrock and multisite.

## Table Of Contents

- [Deployer WordPress Recipes](#deployer-wordpress-recipes)
  - [Table Of Contents](#table-of-contents)
  - [Installation](#installation)
  - [Requirements](#requirements)
  - [Configuration](#configuration)
    - [Default Directory Structure](#default-directory-structure)
    - [Localhost context](#localhost-context)
    - [wp-config.php](#wp-configphp)
    - [Rsync filters/excludes/includes](#rsync-filtersexcludesincludes)
  - [Tasks](#tasks)
    - [Database Tasks (`tasks/database.php`)](#database-tasks-tasksdatabasephp)
      - [Multisite support](#multisite-support)
    - [Packages system (`tasks/packages.php`)](#packages-system-taskspackagesphp)
    - [File Tasks (`tasks/files.php`)](#file-tasks-tasksfilesphp)
    - [WordPress Tasks (`tasks/wp.php`)](#wordpress-tasks-taskswpphp)
    - [Language Tasks (`tasks/languages.php`)](#language-tasks-taskslanguagesphp)
    - [Uploads Tasks (`tasks/uploads.php`)](#uploads-tasks-tasksuploadsphp)
    - [Legacy Tasks (Themes, Plugins, MU-Plugins)](#legacy-tasks-themes-plugins-mu-plugins)
    - [WP-CLI](#wp-cli)
    - [Recipes](#recipes)
      - [Simple](#simple)
      - [Bedrock](#bedrock)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
    - [Testing](#testing)
  - [Built by](#built-by)

## Installation

1. `composer require gaambo/deployer-wordpress --dev`
2. Copy example files from `examples/simple/` or `examples/bedrock/` to your project root
3. Customize `deploy.php` and `deploy.yml`:

- Set `current_path` for all hosts (remote + localhost)
- Configure custom code as [packages](#packages-system-taskspackagesphp)
- Adjust deployment flow if needed

4. Test on staging first, then deploy to production 🚀

Until `gaambo/deployer-utils` v1.0.0 is published, this package uses its Git repository and `dev-main`. The lock file
pins utils to `d4bcf8f432eb7879db6f816815077ea476972161`. Root projects installing this development version must also add:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/gaambo/deployer-utils"
    }
  ]
}
```

After utils v1.0.0 is released, replace the temporary VCS repository and `dev-main` constraint with
`gaambo/deployer-utils:^1.0`.

## Requirements

- PHP 8.3 or newer and [Composer](https://getcomposer.org)
- [Deployer 8](https://deployer.org) (automatically installed)
- WordPress installation
- *nix OS (Linux/macOS or [WSL](https://docs.microsoft.com/en-us/windows/wsl/install-win10) on Windows)
- `rsync` installed

Optional (auto-installed on remote if missing):

- [WP-CLI](https://wp-cli.org/) for database tasks
- [Composer](https://getcomposer.org) for package vendors (depending on your custom theme/plugins)
- [Node.js/npm](https://nodejs.org/) for building assets (depending on your custom theme/plugins)

## Configuration

Example recipes (in `examples/`) provide configuration starting points. The library works with any WordPress setup (
vanilla, Composer, subdirectory, Bedrock, multisite) by making all paths and directories configurable. Check the example
recipes and task source files for available options.

### Localhost Context

Configure localhost in `deploy.php`:

```php
localhost()
    ->set('public_url', 'http://wp-boilerplate.test')
    ->set('deploy_path', __DIR__)
    ->set('current_path', '{{deploy_path}}/public') // WordPress root
    ->set('dbdump_path', 'data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups');
```

`deploy_path` is the project root and is used for private data such as relative database dump paths. `current_path` is
the WordPress root. The previous `project_path` example setting was unused and has been removed.

Local and remote WP-CLI database commands honor runtimes configured on their source hosts and map database dump paths
into them. See the
[Deployer Utils README](https://github.com/gaambo/deployer-utils#readme) for generic localhost, runtime, Composer, npm,
file, and rsync APIs. The old `Gaambo\DeployerWordpress` helper names remain as deprecated wrappers for migration.

### wp-config.php (Recommendation)

Keep `wp-config.php` in git and deploy it. Extract environment-specific config (database credentials, `WP_DEBUG`) into
`wp-config-local.php`, which should be gitignored and created manually on each host. Require it from `wp-config.php`:

```php
if (file_exists(__DIR__ . '/wp-config-local.php')) {
    require_once __DIR__ . '/wp-config-local.php';
}
```

### Rsync filters

The default rsync config uses `.deployfilter` files for per-directory filtering. Place a `.deployfilter` file in your
theme/plugin to exclude development files:

```
- phpcs.xml
- README.md
- .babelrc
- node_modules
- .eslintignore
- .eslintrc.json
- .stylelintignore
- .stylelintrc.json
- gulp.config.js
- gulpfile.babel.js
- package.json
- package-lock.json
- .babelrc
- phpcs.xml
```

This prevents any development files/development tools from syncing. I strongly recommend you put something like this in
your custom theme and mu-plugins or overwrite any of the `themes/filter` or `mu-plugins/filter` configurations.

## Tasks

Tasks are in the `tasks/` directory. Run `dep list` to see all available tasks. See task source files for configuration
options.

### Database Tasks (`tasks/database.php`)

- `db:remote:backup`: Backup remote database and download to localhost
- `db:local:backup`: Backup local database and upload to remote host
- `db:remote:import`: Import current database backup (from localhost) on remote host
- `db:local:import`: Import current database backup (from remote host) on local host
- `db:push`: Pushes local database to remote host (combines `db:local:backup` and `db:remote:import`)
- `db:pull`: Pulls remote database to localhost (combines `db:remote:backup` and `db:local:import`)

#### Multisite support

For multisite installations, set `wp/multisite` to `true` to enable network-wide search-replace during database sync:

```php
set('wp/multisite', true);
```

### Packages system (`tasks/packages.php`)

Manage custom themes, plugins, and mu-plugins with individual build configs:

```php
set('packages', [
    'custom-theme' => [
        'path' => '{{themes/dir}}/custom-theme',
        'remote:path' => '{{themes/dir}}/custom-theme', // optional
        'assets' => true,
        'assets:build_script' => 'build',
        'vendors' => true,              // Run composer install
    ],
    // Add more packages as needed
]);
```

**Tasks:**

- `packages:assets:vendors` - Install npm dependencies
- `packages:assets:build` - Run build scripts
- `packages:vendors` - Install composer dependencies
- `packages:push` / `packages:pull` - Sync packages

### Other Task Categories

**File Tasks** (`tasks/files.php`)

- `files:push` / `files:pull` - Sync all files (combines wp, uploads, plugins, themes, packages)
- `files:backup:remote` / `files:backup:local` - Back up all files through the shared utils tasks

**WordPress Core** (`tasks/wp.php`)

- `wp:download-core`, `wp:push`, `wp:pull`, `wp:info`

**Languages** (`tasks/languages.php`)

- `languages:push`, `languages:pull`, `languages:sync`, `languages:backup:*`

**Uploads** (`tasks/uploads.php`)

- `uploads:push`, `uploads:pull`, `uploads:sync`, `uploads:backup:*`

**Legacy Tasks** (use [packages](#packages-system-taskspackagesphp) instead)

- Themes: `themes:push`, `themes:pull`
- Plugins: `plugins:push`, `plugins:pull`
- MU-Plugins: `mu-plugins:push`, `mu-plugins:pull`

## Recipes

v4 uses `current_path` for rsync-based deployments. Symlinked releases are still possible but not the default.

**`recipes/simple.php`** - Standard WordPress. Rsyncs directly to `current_path`. Recommended for most projects.

**`recipes/bedrock.php`** - [Roots Bedrock](https://roots.io/bedrock/) projects with appropriate structure and
environment handling.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

Issues, feature requests, and pull requests welcome at [GitHub](https://github.com/gaambo/deployer-wordpress). Code
follows PSR-2 and Deployer best practices.

### Testing

The library includes a comprehensive test suite with unit, integration, and functional tests.

- Run `composer precommit` before submitting a PR — it runs lint, code style, PHPStan, and all tests.
- Functional tests use a mocked environment to verify rsync commands and file operations without real remote
  connections.

CI tests Deployer 8 on PHP 8.3, 8.4, and 8.5.
