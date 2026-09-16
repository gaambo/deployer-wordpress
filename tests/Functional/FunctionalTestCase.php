<?php

namespace Gaambo\DeployerWordpress\Tests\Functional;

use Deployer\Ssh\RunParams;
use Gaambo\DeployerUtils\Utils;
use Gaambo\DeployerUtils\Tests\Functional\FunctionalTestCase as BaseFunctionalTestCase;

abstract class FunctionalTestCase extends BaseFunctionalTestCase
{
    protected const RECIPE_PATH = __DIR__ . '/../Fixtures/recipes/common.php';

    protected string $localDocRootDir;
    protected string $remoteReleaseDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localDocRootDir = $this->localCurrentDir;
        $this->remoteReleaseDir = $this->remoteCurrentDir;
        $this->localHost->set('dbdump_path', $this->localDir . '/data/db_dumps');
        $this->localHost->set('bin/wp', 'wp');
        $this->remoteHost->set('dbdump_path', $this->remoteDir . '/data/db_dumps');
        $this->remoteHost->set('bin/wp', 'wp');
    }

    protected function runCwd(RunParams $options): ?string
    {
        return $options->cwd;
    }

    protected function runShell(RunParams $options): string
    {
        return $options->shell;
    }

    protected function ddevShell(string $path): string
    {
        return 'ddev exec --dir ' . Utils::quote($path) . ' bash -s';
    }
}
