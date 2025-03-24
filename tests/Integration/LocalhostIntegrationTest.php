<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerWordpress\Localhost;
use PHPUnit\Framework\MockObject\MockObject;

class LocalhostIntegrationTest extends IntegrationTestCase
{
    public function testGetConfig(): void
    {
        // Set up test configuration
        $this->host->set('test_key', 'test_value');

        // Test getting configuration
        $value = Localhost::getConfig('test_key');
        $this->assertEquals('test_value', $value);
    }

    public function testGetConfigWithNonExistentKey(): void
    {
        // Test getting non-existent configuration
        $value = Localhost::getConfig('non_existent_key');
        $this->assertNull($value);
    }

    public function testGet(): void
    {
        // Test getting localhost instance
        $host = Localhost::get();
        
        // Verify it's the same instance we set up in IntegrationTestCase
        $this->assertSame($this->host, $host);
        
        // Verify it has the expected configuration
        $this->assertEquals('/var/www', $host->get('deploy_path'));
        $this->assertEquals('wp', $host->get('bin/wp'));
        $this->assertEquals('/var/www/current', $host->get('release_or_current_path'));
    }
} 