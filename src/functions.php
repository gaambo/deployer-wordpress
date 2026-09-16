<?php

namespace Gaambo\DeployerWordpress;

use Gaambo\DeployerUtils\Runtime\RuntimeHost;

/**
 * @deprecated Use \Gaambo\DeployerUtils\runtime().
 * @template T of RuntimeHost
 * @param class-string<T> $runtimeHost
 * @return T
 */
function runtime(string $runtimeHost): RuntimeHost
{
    return \Gaambo\DeployerUtils\runtime($runtimeHost);
}
