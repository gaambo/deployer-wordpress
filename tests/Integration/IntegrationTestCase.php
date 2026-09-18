<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerUtils\Tests\Integration\IntegrationTestCase as BaseIntegrationTestCase;

abstract class IntegrationTestCase extends BaseIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->host->set('bin/wp', 'wp');
    }
}
