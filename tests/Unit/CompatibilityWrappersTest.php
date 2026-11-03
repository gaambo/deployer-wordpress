<?php

namespace Gaambo\DeployerWordpress\Tests\Unit;

class CompatibilityWrappersTest extends UnitTestCase
{
    public function testGenericClassWrappersExtendSharedClasses(): void
    {
        $wrappers = [
            \Gaambo\DeployerWordpress\Composer::class => \Gaambo\DeployerUtils\Composer::class,
            \Gaambo\DeployerWordpress\Files::class => \Gaambo\DeployerUtils\Files::class,
            \Gaambo\DeployerWordpress\Localhost::class => \Gaambo\DeployerUtils\Localhost::class,
            \Gaambo\DeployerWordpress\NPM::class => \Gaambo\DeployerUtils\NPM::class,
            \Gaambo\DeployerWordpress\Rsync::class => \Gaambo\DeployerUtils\Rsync::class,
            \Gaambo\DeployerWordpress\Utils::class => \Gaambo\DeployerUtils\Utils::class,
        ];

        foreach ($wrappers as $wrapper => $sharedClass) {
            $this->assertTrue(is_subclass_of($wrapper, $sharedClass));
        }
    }
}
