<?php

namespace Gaambo\DeployerWordpress;

use function Deployer\output;

class DdevRuntime implements RuntimeInterface
{
    /** @var array<string,mixed> */
    private array $config;

    /**
     * @param array<string,mixed> $config Runtime-specific configuration overrides.
     */
    public function __construct(
        private readonly string $containerProjectRoot = '/var/www/html',
        private readonly ?string $projectRoot = null,
        array $config = []
    ) {
        $this->config = array_merge([
            'bin/wp' => 'wp',
            'bin/composer' => 'composer',
            'bin/npm' => 'npm',
            'bin/php' => 'php',
        ], $config);
    }

    public function run(string $command, ?array $options = null): string
    {
        $runOptions = $options ?? [];
        $hostProjectRoot = $this->hostProjectRoot();
        $runtimeCommand = 'ddev exec';

        if (isset($runOptions['cwd'])) {
            $runtimeCwd = $this->path($runOptions['cwd']);
            $this->diagnostic("cwd: {$runOptions['cwd']} -> $runtimeCwd");
            $runtimeCommand .= ' --dir ' . Utils::quote($runtimeCwd);
        }

        $runtimeCommand .= " $command";
        $runOptions['cwd'] = $hostProjectRoot;

        return Localhost::runNative($runtimeCommand, $runOptions);
    }

    public function path(string $hostPath): string
    {
        $hostPath = $this->normalizeAbsolutePath($hostPath, 'Runtime path');
        $hostProjectRoot = $this->hostProjectRoot();

        $isInsideProject = $hostProjectRoot === '/'
            ? str_starts_with($hostPath, '/')
            : str_starts_with($hostPath, $hostProjectRoot . '/');
        if ($hostPath !== $hostProjectRoot && !$isInsideProject) {
            throw new \RuntimeException(
                "Cannot map path \"$hostPath\" into DDEV: it is outside project root \"$hostProjectRoot\"."
            );
        }

        $relativePath = $hostProjectRoot === '/' ? $hostPath : substr($hostPath, strlen($hostProjectRoot));
        $containerProjectRoot = $this->normalizeAbsolutePath($this->containerProjectRoot, 'DDEV project root');
        $runtimePath = ($containerProjectRoot === '/' ? '' : $containerProjectRoot) . $relativePath;
        $runtimePath = $runtimePath === '' ? '/' : $runtimePath;
        $this->diagnostic("path: $hostPath -> $runtimePath");

        return $runtimePath;
    }

    public function hasConfig(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    public function getConfig(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

    private function hostProjectRoot(): string
    {
        $projectRoot = $this->projectRoot ?? Localhost::getConfig('deploy_path');
        if (!is_string($projectRoot)) {
            throw new \RuntimeException('DDEV requires localhost "deploy_path" or an explicit projectRoot.');
        }

        return $this->normalizeAbsolutePath($projectRoot, 'DDEV host project root');
    }

    private function normalizeAbsolutePath(string $path, string $description): string
    {
        if (!str_starts_with($path, '/')) {
            throw new \RuntimeException("$description must be an absolute path, got \"$path\".");
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return '/' . implode('/', $parts);
    }

    private function diagnostic(string $message): void
    {
        if (output()->isVerbose()) {
            output()->writeln("[runtime:ddev] $message");
        }
    }
}
