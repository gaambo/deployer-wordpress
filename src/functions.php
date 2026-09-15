<?php

namespace Gaambo\DeployerWordpress;

use Gaambo\DeployerWordpress\Runtime\RuntimeHost;

/**
 * Create an unregistered runtime host that inherits localhost configuration.
 *
 * @template T of RuntimeHost
 * @param class-string<T> $runtimeHost
 * @return T
 */
function runtime(string $runtimeHost): RuntimeHost
{
    if (!Utils::isDeployerVersion('>=', '8.0.0')) {
        throw new \RuntimeException('Runtime hosts require Deployer 8 or newer.');
    }

    $runtime = new $runtimeHost();
    $runtime->bind(Localhost::get());

    return $runtime;
}
