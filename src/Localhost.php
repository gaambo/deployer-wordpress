<?php

namespace Gaambo\DeployerWordpress;

use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Task\Context;
use Gaambo\DeployerWordpress\Utils;

use function Deployer\on;
use function Deployer\runLocally;

/**
 * Localhost utility class
 * Provides methods to get localhost configuration values
 */
class Localhost
{
    /**
     * Get a localhost configuration value
     * @param string $key The configuration key to get
     * @return mixed The configuration value
     */
    public static function getConfig(string $key): mixed
    {
        // Switch to the localhost config, so all get() calls use that context.
        // Useful for dynamic calculated values.
        Context::push(new Context(self::get()));
        $value = self::get()->get($key);
        Context::pop();
        return $value;
    }

    /**
     * Get the (single) defined localhost host
     *
     * @return Host Localhost host
     */
    public static function get(): Host
    {
        $localhost = Deployer::get()->hosts->get('localhost');
        return $localhost;
    }

    /**
     * A wrapper around runLocally() which parses variables from the local host.
     * The problem with runLocally() is that it parses values from the global context which
     * does not use our Localhost instance.
     *
     * @param string $command Command to run on localhost.
     * @param array{
     *     cwd?:string|null,
     *     timeout?:int|null,
     *     idleTimeout?:int|null,
     *     env?:array<string,string>|null,
     *     secrets?:array<string,string>|null,
     *     nothrow?:bool,
     *     forceOutput?:bool,
     *     shell?:string|null
     * }|null $options On v8 extracted to named parameters; on v7 passed as array.
     * @return string
     */
    public static function run(string $command, ?array $options = null): string
    {
        $result = null;
        on(self::get(), function () use ($command, $options, &$result) {
            $runOpts = $options ?? [];
            if (Utils::isDeployerVersion('>=', '8.0.0')) {
                // v8: runLocally uses named parameters (argument.unknown suppressed by DeployerVersionCompatExtension).
                $result = runLocally(
                    $command,
                    cwd: $runOpts['cwd'] ?? null,
                    timeout: $runOpts['timeout'] ?? null,
                    idleTimeout: $runOpts['idleTimeout'] ?? null,
                    secrets: $runOpts['secrets'] ?? null,
                    env: $runOpts['env'] ?? null,
                    forceOutput: $runOpts['forceOutput'] ?? false,
                    nothrow: $runOpts['nothrow'] ?? false,
                    shell: $runOpts['shell'] ?? null,
                );
            } else {
                // v7 compat: runLocally accepted an options array. Remove when v7 support is dropped.
                $result = runLocally($command, $runOpts);
            }
        });
        return $result;
    }
}
