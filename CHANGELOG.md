# Changelog

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
    - The `local` is not available any more. Instead `once` and `runLocally` should be used. For theme assets the example uses a function callback and the `on` helper to optionally run those build tasks on the local host.
    - When deploying you can't select a host by name or stage anymore. Instead you have to use labels (eg a `stage` label). If you've used `dep deploy production` you now have to use `dep deploy stage=production` and set the stage label in your yml file.
- Switched to a single base recipe which can be included and built upon. See `examples/deploy.php`.
- The new recipe and examples uses yml-files for project-specific configuration so the `deploy.php` is a dropin file and has no configuration in it.
- PHP 8 compatibility.
- Fixes issues with rsync flags/options and `'`.

**Upgrading:**
If you've used the default recipe it's probably easiest to copy the new example `deploy.php` and update your yml-file with project-specific configuration. If you have added any other tasks/features to your `deploy.php` make sure you upgrade them too.
If you've used most of the core functions of this library or just the examples, the upgrade should only take a few minutes.