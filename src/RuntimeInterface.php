<?php

namespace Gaambo\DeployerWordpress;

interface RuntimeInterface
{
    /**
     * @param array{
     *     cwd?:string|null,
     *     timeout?:int|null,
     *     idleTimeout?:int|null,
     *     env?:array<string,string>|null,
     *     secrets?:array<string,string>|null,
     *     nothrow?:bool,
     *     forceOutput?:bool,
     *     shell?:string|null
     * }|null $options
     */
    public function run(string $command, ?array $options = null): string;

    public function path(string $hostPath): string;

    public function hasConfig(string $key): bool;

    public function getConfig(string $key): mixed;
}
