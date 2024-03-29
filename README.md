# Deployer WordPress Recipes

A collection of [Deployer](https://deployer.org) Tasks/Recipes to deploy WordPress sites. From simple sites deployed via copying files up to building custom themes/mu-plugins and installing npm/composer vendors - It handles all kinds of WordPress installation types.

## Table Of Contents

- [Deployer WordPress Recipes](#deployer-wordpress-recipes)
  - [Table Of Contents](#table-of-contents)
  - [Installation](#installation)
  - [Requirements](#requirements)
  - [Configuration](#configuration)
    - [Default Directory Structure](#default-directory-structure)
    - [wp-config.php](#wp-configphp)
    - [Rsync filters/excludes/includes](#rsync-filtersexcludesincludes)
  - [Tasks](#tasks)
    - [Database Tasks (`tasks/database.php`)](#database-tasks-tasksdatabasephp)
    - [File Tasks (`tasks/files.php`)](#file-tasks-tasksfilesphp)
    - [Theme Tasks (`tasks/theme.php`)](#theme-tasks-tasksthemephp)
    - [Uploads Tasks (`tasks/uploads.php`)](#uploads-tasks-tasksuploadsphp)
    - [Plugin Tasks (`tasks/plugins.php`)](#plugin-tasks-taskspluginsphp)
    - [MU Plugin Tasks (`tasks/mu-plugins.php`)](#mu-plugin-tasks-tasksmu-pluginsphp)
    - [WordPress Tasks (`tasks/wp.php`)](#wordpress-tasks-taskswpphp)
      - [WP-CLI](#wp-cli)
  - [Recipes](#recipes)
    - [Base](#base)
    - [Advanced](#advanced)
      - [Custom Theme](#custom-theme)
      - [Custom MU-Plugin](#custom-mu-plugin)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
    - [Testing](#testing)
  - [Built by](#built-by)

## Installation

1. Run `composer require gaambo/deployer-wordpress --dev` in your root directory
2. Choose one of the [recipes](#recipes) and copy the corresponding example files (`examples/base` or `examples/advanced`) into your root directory - **or** write your own.
3. Read through the recipe and customize it to your needs - here's a checklist:
   - [ ] Check localhost configuration
   - [ ] Set paths to your directory structure
   - [ ] If you have a custom theme set it's name - if not remove the configuration and the theme build-tasks
   - [ ] If you have a custom mu-plugin set it's name - if not remove the configuration and the mu-plugin build-tasks
   - [ ] Check if the deployment flow meets your needs and maybe delete/add/overwrite tasks
4. Make your remote hosts ready for deployment (install composer, WP CLI; setup paths,...). Allthough the library checks for most of them and installs them.
5. Make a **test deployment** to a test/staging server. Do not directly deploy to your production site, you may break it.
6. Develop, deploy and be happy :)

## Requirements

Obviously:

- [PHP](https://php.net/) and [composer](https://getcomposer.org) for installing and using Deployer
- [Deployer](https://deployer.org) core (`deployer/deployer`) is required dependencies of this package defined in `composer.json`
- WordPress installation + local web server and database to use it

Most of the tasks only run in *nix shells - so a *nix **operating system** is preferred. If you run Windows have a look at [WSL](https://docs.microsoft.com/en-us/windows/wsl/install-win10) to run Ubuntu Bash inside Windows.

Some tasks have additional requirements, eg:

- [composer](https://getcomposer.org) for PHP dependencies
- [Node.js/npm](https://nodejs.org/en/) for JavaScript dependencies
- [WP CLI](https://wp-cli.org/de/) for database backups/exports/imports
- `rsync` and `zip` command installed

## Configuration

All tasks are documented and describe which options/variables need to be configured. `set.php` is included in all example recipes - This and the example recipes should have you covered regarding all required configurations. Further variables which need to be set by you are marked accordingly in the recipes.

To help understand all the configurations here are the thoughts behind theme:
The tasks are built to work with any kind of WordPress setup (vanilla, composer, subdirectory,..) - therefore all paths and directorys are configurable via variables. `set.php` contains some sane defaults which makes all tasks work out of the box with a default installation.

### Default Directory Structure

My [Vanilla WordPress Boilerplate](https://github.com/gaambo/vanilla-wp/) uses this library. You can find a example configuration in the GitHub repository.

### wp-config.php

To make WordPress deployable you need to extract the host-dependent configuration (eg database access) into a seperate file which does not live in your git repository and is not deployed. I suggest using a **`wp-config-local.php`** file. This file should be required in your `wp-config.php` and be ignored by git (via `.gitignore`). This way `wp-config.php` can (should) be in your git repository and also be deployed. The default `wp/filter` configuration assumes this.
Another advantage of using a `wp-config-local.php` is to set `WP_DEBUG` on a per host basis.

### Rsync filters/excludes/includes

The default rsync config for syncing files (used by all \*:push/\*:pull tasks) is set in the `rsync` variable.
By default it set's a `filter-perDir` argument as `.deployfilter` - which means rsync will look for a file named `.deployfilter` in each directory to parse filters for this directory. See [rsync man](https://linux.die.net/man/1/rsync) section "Filter Rules" for syntax.

This can be handy to put int your custom theme or mu-plugin - for example:

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
```

This prevents any development files/development tools from syncing. I strongly recommend you put something like this in your custom theme and mu-plugins or overwrite any of the `themes/filter` or `mu-plugins/filter` configurations.

## Tasks

All tasks reside in the `src/tasks` directory and are documented well. Here's a summary of all tasks - for details (eg required variables/config) see their source.
You can also run `dep list` to see all available tasks and their description.

### Database Tasks (`tasks/database.php`)

- `db:remote:backup`: Backup remote database and download to localhost
- `db:local:backup`: Backup local database and upload to remote host
- `db:remote:import`: Import current database backup (from localhost) on remote host
- `db:local:import`: Import current database backup (from remote host) on local host
- `db:push`: Pushes local database to remote host (combines `db:local:backup` and `db:remote:import`)
- `db:pull`: Pulls remote database to localhost (combines `db:remote:backup` and `db:local:import`)

### File Tasks (`tasks/files.php`)

- `files:push`: Pushes all files from local to remote host (combines `wp:push`, `uploads:push`, `plugins:push`, `mu-plugins:push`, `themes:push`)
- `files:pull`: Pulls all files from remote to local host (combines `wp:pull`, `uploads:pull`, `plugins:pull`, `mu-plugins:pull`, `themes:pull`)

### Theme Tasks (`tasks/theme.php`)

- `theme:assets:vendors`: Install theme assets vendors/dependencies (npm), can be run locally or remote
- `theme:assets:build`: Run theme assets (npm) build script, can be run locally or remote
- `theme:assets`: A combined tasks to build theme assets - combines `theme:assets:vendors` and `theme:assets:build`
- `theme:vendors`: Install theme vendors (composer), can be run locally or remote
- `theme`: A combined task to prepare the theme - combines `theme:assets` and `theme:vendors`
- `themes:push`: Push themes from local to remote
- `themes:pull`: Pull themes from remote to local
- `themes:sync`: Syncs themes between remote and local
- `themes:backup:remote`: Backup themes on remote host and download zip
- `themes:backup:local`: Backup themes on localhost

### Uploads Tasks (`tasks/uploads.php`)

- `uploads:push`: Push uploads from local to remote
- `uploads:pull`: Pull uploads from remote to local
- `uploads:sync`: Syncs uploads between remote and local
- `uploads:backup:remote`: Backup uploads on remote host and download zip
- `uploads:backup:local`: Backup uploads on localhost

### Plugin Tasks (`tasks/plugins.php`)

- `plugins:push`: Push plugins from local to remote
- `plugins:pull`: Pull plugins from remote to local
- `plugins:sync`: Syncs plugins between remote and local
- `plugins:backup:remote`: Backup plugins on remote host and download zip
- `plugins:backup:local`: Backup plugins on localhost

### MU Plugin Tasks (`tasks/mu-plugins.php`)

- `mu-plugin:vendors`: Install mu-plugin vendors (composer), can be run locally or remote
- `mu-plugin`: A combined tasks to prepare the theme - at the moment only runs mu-plugin:vendors task
- `mu-plugins:push`: Push mu-plugins from local to remote
- `mu-plugins:pull`: Pull mu-plugins from remote to local
- `mu-plugins:sync`: Syncs mu-plugins between remote and local
- `mu-plugins:backup:remote`: Backup mu-plugins on remote host and download zip
- `mu-plugins:backup:local`: Backup mu-plugins on localhost

### WordPress Tasks (`tasks/wp.php`)

- `wp:download-core`: Installs WordPress core via WP CLI
- `wp:push`: Pushes WordPress core files via rsync
- `wp:pull`: Pulls WordPress core files via rsync
- `wp:info`: Runs the --info command via WP CLI - just a helper/test task
- `wp:install-wpcli`: Install the WP-CLI binary manually with the `wp:install-wpcli` task and set the path as `/bin/wp` afterwards.

#### WP-CLI

Handling and installing the WP-CLI binary can be done in one of multiple ways: 

1. The default `bin/wp` in `set.php` checks for a usable WP-CLI binary and if none is found it downloads and installs it to `{{deploy_path}}/.dep/wp-cli.phar` (this path is checked in the future as well).
2. If you want this behaviour (check if installed, else install) but in another path, overwrite the `bin/wp` variable with a function and change the path it should be installed to.
3. Set the `bin/wp` variable path on the host configuration, if WP-CLI is already installed.
4. Install the WP-CLI binary manually with the `wp:install-wpcli` task and set the path as `/bin/wp` afterwards.
You can pass the installPath, binaryFile and sudo usage via CLI: 
`dep wp:install-wpcli stage=production -o installPath='{{deploy_path}}/.bin -o binaryFile=wp -o sudo=true`

See [original PR](https://github.com/gaambo/deployer-wordpress/pull/5) for more information.

There's a task for downloading core and `--info`. You can generate your own tasks to handle other WP-CLI commands, there's a util function `Gaambo\DeployerWordpress\Utils\WPCLI\runCommand` (`src/utils/wp-cli.php`);

## Recipes

Deployer WordPress ships with two base recipes which handle my use cases.
Both recipes are based on the default PHPDeployer common recipe and have their own recipe file which you can include in your `deploy.php` as a start. The examples folder provides examples for each recipe.
Both recipes log the deployed versions in PHPDeployers default format (`.dep` folder).
Both recipes overwrites the `deploy:update_code` Deployer task with a `deploy:update_code` task to deploy code via rsync instead of git 

### Base

This is for WordPress sites where you don't need symlinking per version or atomic releases. This means that on your remote/production host you just have on folder which contains all of WordPress files and this is served by your web server.
Since this is still based on the default PHPDeployer recipe which uses symlinking and to provide compatibility with all tasks, this just hardcodes the `release_path` and `current_path`.

### Advanced

This uses symlinking like the default common recipe from PHPDeployer. Each release gets deployed in its own folder unter `{{deploy_path}}/releases/` and `{{deploy_path}}/current` is a symlink to the most current version. The symlink is automatically updated after the deployment finishes successfully. You can configure your webserver to just server `{{deploy_path}}/current`.

#### Custom Theme

Set custom theme name (= directory) in variable `theme/name`.
By default it runs `theme:assets:vendors` and `theme:assets:build` locally and just pushes the built/dist files to the server (--> no need to install Node.js/npm on server). The `theme:assets` task (which groups the two tasks above) is hooked into _before_ `deploy:push_code`.

Installing PHP/composer vendors/dependencies is done on the server. The `theme:vendors` task is therefore hooked into _after_ `deploy:push_code`.

#### Custom MU-Plugin

Set custom mu-plugin name (=directory) in variable `mu-plugin/name`.
Installing PHP/composer vendors/dependencies is done on the server. The `mu-plugin:vendors` task is therefore hooked into _after_ `deploy:push_code`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

If you have feature requests, find bugs or need help just open an issue on [GitHub](https://github.com/gaambo/deployer-wordpress).
Pull requests are always welcome. PSR2 coding standard are used and I try to adhere to Deployer best-practices.

### Testing

1. Download my [Vanilla WordPress Boilerplate](https://github.com/gaambo/vanilla-wp/) or set up a local dev environment with a deploy config
2. Setup a remote test server
3. Configure yml/deploy.php
4. Run common tasks (`deploy`, `plugins:pull/push`, `db`) for both base as well as advanced recipe

## Built by

[Gaambo](https://github.com/gaambo) and [Contributors](https://github.com/gaambo/deployer-wordpress/graphs/contributors)