# Upgrading from v4 to v5

v5 requires PHP 8.3 or newer and Deployer 8. Generic helpers now come from the
stable `gaambo/deployer-utils:^1.0.1` package, installed automatically by Composer.
WordPress task names and the simple/Bedrock recipe entry points stay the same.

## 1. Update dependencies

Once v5 is released:

```sh
composer require --dev gaambo/deployer-wordpress:^5.0 --with-all-dependencies
```

Remove any explicit Deployer 7 constraint or update it to `^8.0`. Review
[Deployer's upgrade guide](https://deployer.org/docs/8.x/UPGRADE) for custom tasks.
If you require Deployer Utils directly, change its constraint to `^1.0.1` too.
No custom Composer repository is needed.

## 2. Set localhost paths

Replace the old, unused `project_path` setting with `deploy_path`. Set an explicit
WordPress root in `current_path`, as shown in the updated `examples/` files:

```php
localhost()
    ->set('public_url', 'https://example.test')
    ->set('deploy_path', __DIR__)
    ->set('current_path', '{{deploy_path}}/public')
    ->set('dbdump_path', 'data/db_dumps')
    ->set('backup_path', __DIR__ . '/data/backups');
```

Adjust `public` to your layout. For Bedrock, use the WordPress root configured by
your existing recipe; do not change the site's layout just to match this example.
Remote hosts also need `deploy_path` and `current_path`.

Relative `dbdump_path` values now resolve against each host's `deploy_path`, not
its WordPress root. For example, the config above stores dumps in
`<project>/data/db_dumps`, not `<project>/public/data/db_dumps`. Set an absolute
dump path if you need to keep the old location. Keep database dumps outside the
public web root.

## 3. Check custom file transfers

Paths starting with `/` now mean absolute filesystem paths. If a custom
`Files::pushFiles()` or `Files::pullFiles()` call used `/wp-content/themes` to
mean a path below the WordPress root, change it to `wp-content/themes`.

Relative file-transfer paths still resolve against localhost's `current_path`
and the remote host's `release_or_current_path`. This differs from database dump
paths, which resolve against `deploy_path`.

## 4. Update helper imports

Move these imports from `Gaambo\DeployerWordpress` to `Gaambo\DeployerUtils`:

- `Composer`
- `Files`
- `Localhost`
- `NPM`
- `Rsync`
- `Utils`

For example:

```php
use Gaambo\DeployerUtils\Localhost;
use Gaambo\DeployerUtils\Files;
use Gaambo\DeployerWordpress\WPCLI;
```

The six old helper classes remain deprecated wrappers without runtime warnings.
`WPCLI` stays in `Gaambo\DeployerWordpress`.

`Utils::quote()` no longer exists. Use Deployer's function instead:

```php
use function Deployer\quote;

$argument = quote($value);
```

The WordPress recipes already load the shared file-backup tasks. Keep loading
`deployer-wordpress/tasks/files.php` if you need the WordPress `files:push` and
`files:pull` aggregate tasks; do not replace it with the Utils task file.

## 5. Check custom WP-CLI and runtime calls

`WPCLI::runCommandLocally()` now defaults to localhost's `current_path`, rather
than leaving the working directory unset. Pass another path explicitly if a
custom task needs it, or pass `null` to leave it unset.

Local and remote WP-CLI calls now use the runtime configured on their source
host. Database tasks map dump paths into that runtime automatically. For custom
WP-CLI calls that include file paths, pass the host-side paths in `runtimePaths`:

```php
WPCLI::runCommandLocally(
    "db export $dumpFile",
    runtimePaths: [$dumpFile]
);
```

Runtimes are optional. Without one, commands run on the host as usual. For DDEV,
keep dump paths below `deploy_path` so they can map into the container.
`Localhost::run()` also honors runtimes; use `Localhost::runNative()` for commands
that must run outside one.

See the [Deployer Utils runtime docs](https://github.com/gaambo/deployer-utils#runtimes)
for PHP/YAML configuration, path mapping, and runtime APIs.
