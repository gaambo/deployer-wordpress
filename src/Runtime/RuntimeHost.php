<?php

namespace Gaambo\DeployerWordpress\Runtime;

use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;

use function Deployer\output;
use function Deployer\run;

/**
 * A local Deployer host used only while application commands run.
 *
 * Runtime hosts inherit localhost configuration but are not deployment targets.
 */
abstract class RuntimeHost extends Localhost
{
    private Host $localhost;

    /** Bind inherited configuration without registering this as a deployment host. */
    final public function bind(Host $localhost): void
    {
        $this->localhost = $localhost;
        $this->config()->bind($localhost->config());
        $this->set('working_path', $this->localhostDeployPath());
        $this->bound();
    }

    /**
     * Run a command through Deployer so its normal parsing and lazy config still apply.
     *
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
    final public function run(string $command, ?array $options = null): string
    {
        $runOptions = $this->prepareRunOptions($options ?? []);

        return $this->within(function () use ($command, $runOptions) {
            return run(
                $command,
                cwd: $runOptions['cwd'] ?? null,
                env: $runOptions['env'] ?? null,
                secrets: $runOptions['secrets'] ?? null,
                nothrow: $runOptions['nothrow'] ?? false,
                forceOutput: $runOptions['forceOutput'] ?? false,
                timeout: $runOptions['timeout'] ?? null,
                idleTimeout: $runOptions['idleTimeout'] ?? null,
            );
        });
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    final public function within(callable $callback): mixed
    {
        Context::push(new Context($this));
        try {
            return $callback();
        } finally {
            Context::pop();
        }
    }

    /** The project root on the machine running Deployer. */
    final protected function localhostDeployPath(): string
    {
        $deployPath = $this->localhost->get('deploy_path');
        if (!is_string($deployPath)) {
            throw new \RuntimeException('A runtime requires localhost "deploy_path" to be a string.');
        }

        return $deployPath;
    }

    /** Map a path from localhost into the runtime. */
    abstract public function path(string $hostPath): string;

    protected function bound(): void
    {
    }

    /**
     * Prepare runtime-specific process options before Deployer parses the command.
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    protected function prepareRunOptions(array $options): array
    {
        return $options;
    }

    protected function diagnostic(string $message): void
    {
        if (output()->isVerbose()) {
            output()->writeln("[{$this->getAlias()}] $message");
        }
    }
}
