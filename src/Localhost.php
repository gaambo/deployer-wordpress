<?php

namespace Gaambo\DeployerWordpress;

use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Task\Context;

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
     *     secrets?:array<string,mixed>|null,
     *     nothrow?:bool,
     *     forceOutput?:bool,
     *     shell?:string|null
     * }|null $options
     *   v8 named parameters (timeout, env, nothrow, forceOutput, shell).
     *   Ignored on v7 since no callers pass options.
     * @return string
     */
    public static function run(string $command, ?array $options = null): string
    {
        $result = null;
        on(self::get(), function () use ($command, $options, &$result) {
            // Deployer v7/v8 compat: v8 uses named parameters; v7 used an options array.
            // No callers pass $options, so v7 always falls through to runLocally($command).
            if ($options !== null && Utils::isDeployerVersion('>=', '8.0.0')) {
                $result = runLocally(
                    $command,
                    cwd: $options['cwd'] ?? null,
                    timeout: $options['timeout'] ?? null,
                    idleTimeout: $options['idleTimeout'] ?? null,
                    secrets: $options['secrets'] ?? null,
                    env: $options['env'] ?? null,
                    forceOutput: $options['forceOutput'] ?? false,
                    nothrow: $options['nothrow'] ?? false,
                    shell: $options['shell'] ?? null,
                );
            } else {
                $result = runLocally($command);
            }
        });
        return $result;
    }
}
