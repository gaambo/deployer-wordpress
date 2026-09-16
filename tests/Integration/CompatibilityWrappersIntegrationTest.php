<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerUtils\Runtime\Runtime as SharedRuntime;
use Gaambo\DeployerUtils\Runtime\RuntimeHost as SharedRuntimeHost;
use Gaambo\DeployerWordpress\Runtime\DdevRuntimeHost;

use function Gaambo\DeployerWordpress\runtime;

class CompatibilityWrappersIntegrationTest extends IntegrationTestCase
{
    public function testLegacyRuntimeFactoryCreatesAUtilsCompatibleRuntime(): void
    {
        $runtime = runtime(DdevRuntimeHost::class);
        $this->host->set('runtime', $runtime);

        $this->assertInstanceOf(SharedRuntimeHost::class, $runtime);
        $this->assertSame('/var/www/html', SharedRuntime::getConfig('deploy_path'));
    }
}
