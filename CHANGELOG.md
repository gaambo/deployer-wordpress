# Changelog

## v4.0.1 - 2026-04-28

- Update composer packages

## v4.0.0 - 2025-03-20

v4 is a major refactor focusing on simpler, rsync-based deployments with better developer experience.

_Info:_ This release does not yet officially support Deployer v8, since it's not released yet.

### Breaking Changes

- **Architecture**: Now uses `current_path` as primary deployment target instead of symlinked `release_path`. Atomic
  releases via symlinks are still possible but no longer the default.
- **PSR-4 structure**: Internal code reorganized with PSR-4 autoloading. Task files moved from `src/tasks/` to `tasks/`.
- **Removed `advanced` recipe**: Use `simple` or `bedrock` recipes instead.
- **Removed `:sync` tasks**: Use explicit `:push` and `:pull` tasks instead.

### Added

- **Packages system**: Unified way to manage custom themes, plugins, and mu-plugins with individual build scripts and
  vendor configs.
- Older `themes`/`plugins`/`mu-plugins` are still available for backwards compatibility, but will be removed in a future
  release.
- **Multisite support**: Set `wp/multisite` flag for proper URL replacements during database sync.
- **Language tasks**: New tasks for syncing language files (`languages:push`, `languages:pull`, `languages:backup:*`).
- **Localhost context**: Proper context handling for local operations with consistent path resolution.
- **New recipes**: `recipes/simple.php` for rsync deployments, `recipes/bedrock.php` for Bedrock projects.
- **Comprehensive tests**: Full integration and functional test suite.

### Changed

- Improved bin detection and auto-installation for `composer` and `wp-cli`.
- Enhanced database task logic for URL/path replacements.
- Better sudo prefix handling in binary execution.

### Fixed

- Database pulling and local importing.
- Recipe include paths when installed via composer.
- Path resolution in localhost operations.

### Upgrading from v3.x

1. **Set `current_path`**: Define where your WordPress root lives on each host (including localhost).
2. **Migrate to packages**: Move theme/plugin build config to the new `packages` configuration.
3. **Update recipe**: Switch from old recipes to `recipes/simple.php` or `recipes/bedrock.php`.
4. **Check examples**: Review `examples/simple/` or `examples/bedrock/` for updated patterns.
5. **Replace `:sync` tasks**: Update custom tasks using `:sync` to use `:push` or `:pull`.

## v3.1.0

- Added a `deploy:build_assets` step into the default deploy task to build theme assets on local.
  This allows for easier overwriting this task (eg to build custom plugin assets) and fixes running duplicates on some
  configurations.

## v3.0.0

- Did a large refactor of paths (release_path, current_path, document_root)
- Provide two [recipes](#recipes) (base and advanced) and examples for both
- v2.0.0 did not work with symlink deployments, this now works again (see #8)
- Updated from Deployer 7.2 to 7.3
- New config options (see `set.php`):
  - `wp/configFiles` for wp-config/wp-config-local files which should be protected by more restrict file permissions
  - `wp/configFiles/permissions` for the chmod permissions to apply to the configFiles
  - Removed `document_root` - use `release_or_current_path` instead
- New/changed task names:
  - `push_code` now is called `update_code` again for parity with PHPDeployer.

**Upgrading:**

- If you haven't upgraded to v2.0.0 yet, it's best to upgrade to 3.0.0 directly
- Have a look at the example files. Your deploy.php will get much smaller and require less configuration.
- Also the new version is more smiliar to PHPDeployers default common recipe.

## v2.0.0

- Updated from Deployer 6.x to 7.x
  See [docs](https://deployer.org/docs/7.x/UPGRADE#upgrade-from-6x-to-7x) for more information.
  Most notable changes:
  - New format for yml-files which can now also include configuration.
  - The `local` is not available any more. Instead `once` and `runLocally` should be used. For theme assets the example
    uses a function callback and the `on` helper to optionally run those build tasks on the local host.
  - When deploying you can't select a host by name or stage anymore. Instead you have to use labels (eg a `stage`
    label). If you've used `dep deploy production` you now have to use `dep deploy stage=production` and set the stage
    label in your yml file.
- Switched to a single base recipe which can be included and built upon. See `examples/deploy.php`.
- The new recipe and examples uses yml-files for project-specific configuration so the `deploy.php` is a dropin file and
  has no configuration in it.
- PHP 8 compatibility.
- Fixes issues with rsync flags/options and `'`.

**Upgrading:**
If you've used the default recipe it's probably easiest to copy the new example `deploy.php` and update your yml-file
with project-specific configuration. If you have added any other tasks/features to your `deploy.php` make sure you
upgrade them too.
If you've used most of the core functions of this library or just the examples, the upgrade should only take a few
minutes.
