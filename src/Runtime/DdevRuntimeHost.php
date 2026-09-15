<?php

namespace Gaambo\DeployerWordpress\Runtime;

use Gaambo\DeployerWordpress\Utils;

/** Runs application commands inside the DDEV web container. */
class DdevRuntimeHost extends RuntimeHost
{
    public function __construct()
    {
        parent::__construct('runtime:ddev');
        $this->set('ddev_deploy_path', '/var/www/html');
        $this->set('deploy_path', '{{ddev_deploy_path}}');
    }

    public function path(string $hostPath): string
    {
        $runtimePath = $this->mapPath($hostPath);
        $this->diagnostic("path: $hostPath -> $runtimePath");

        return $runtimePath;
    }

    /** Set the default shell used by direct Deployer calls inside Runtime::within(). */
    protected function bound(): void
    {
        $this->configureShell($this->localhostDeployPath(), false);
    }

    /** Map the requested cwd into DDEV while starting DDEV from the host project root. */
    protected function prepareRunOptions(array $options): array
    {
        $localhostProjectRoot = $this->localhostDeployPath();
        $localhostCwd = $options['cwd'] ?? $localhostProjectRoot;
        $this->configureShell($localhostCwd);
        $options['cwd'] = $localhostProjectRoot;
        unset($options['shell']);

        return $options;
    }

    private function mapPath(string $hostPath): string
    {
        $this->requireAbsolutePath($hostPath, 'Runtime path');
        $hostProjectRoot = $this->localhostDeployPath();
        $this->requireAbsolutePath($hostProjectRoot, 'Localhost deploy path');
        $hostProjectRoot = rtrim($hostProjectRoot, '/') ?: '/';

        $isInsideProject = $hostProjectRoot === '/'
            ? str_starts_with($hostPath, '/')
            : str_starts_with($hostPath, $hostProjectRoot . '/');
        if ($hostPath !== $hostProjectRoot && !$isInsideProject) {
            throw new \RuntimeException(
                "Cannot map path \"$hostPath\" into DDEV: it is outside project root \"$hostProjectRoot\"."
            );
        }

        $relativePath = $hostProjectRoot === '/' ? $hostPath : substr($hostPath, strlen($hostProjectRoot));
        $runtimeProjectRoot = $this->get('deploy_path');
        $this->requireAbsolutePath($runtimeProjectRoot, 'DDEV deploy path');
        $runtimeProjectRoot = rtrim($runtimeProjectRoot, '/') ?: '/';
        $runtimePath = ($runtimeProjectRoot === '/' ? '' : $runtimeProjectRoot) . $relativePath;
        $runtimePath = $runtimePath === '' ? '/' : $runtimePath;
        return $runtimePath;
    }

    private function configureShell(string $hostCwd, bool $showDiagnostic = true): void
    {
        $runtimeCwd = $this->mapPath($hostCwd);
        if ($showDiagnostic) {
            $this->diagnostic("cwd: $hostCwd -> $runtimeCwd");
        }
        $this->setShell('ddev exec --dir ' . Utils::quote($runtimeCwd) . ' bash -s');
    }

    private function requireAbsolutePath(string $path, string $description): void
    {
        if (!str_starts_with($path, '/')) {
            throw new \RuntimeException("$description must be an absolute path, got \"$path\".");
        }
    }
}
