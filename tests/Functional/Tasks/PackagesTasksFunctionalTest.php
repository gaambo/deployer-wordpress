<?php

namespace Gaambo\DeployerWordpress\Tests\Functional\Tasks;

use Gaambo\DeployerWordpress\Tests\Functional\FunctionalTestCase;
use RuntimeException;

class PackagesTasksFunctionalTest extends FunctionalTestCase
{
    private string $localMuPluginsDir;
    private string $remoteMuPluginsDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up temporary directories
        $this->localMuPluginsDir = $this->localDocRootDir . '/wp-content/mu-plugins';
        $this->remoteMuPluginsDir = $this->remoteReleaseDir . '/wp-content/mu-plugins';

        // Create directories
        mkdir($this->localMuPluginsDir, 0755, true);
        mkdir($this->remoteMuPluginsDir, 0755, true);

        // Set default npm configuration
        $this->deployer->config->set('bin/npm', 'npm');
    }

    public function testListAvailableTasks(): void
    {
        $this->dep('list', null);
        $output = $this->tester->getDisplay();

        $this->assertStringContainsString('packages:assets:vendors', $output);
        $this->assertStringContainsString('packages:assets:build', $output);
        $this->assertStringContainsString('packages:vendors', $output);
        $this->assertStringContainsString('packages:assets', $output);
        $this->assertStringContainsString('packages', $output);
    }

    public function testPackagesAssetsVendorsOnlyRunsWhenAssetsIsTrue(): void
    {
        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-with-assets';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-without-assets';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-without-assets-flag';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Create test packages configuration
        $this->deployer->config->set('packages', [
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-with-assets',
                'assets' => true
            ],
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-without-assets',
                'assets' => false
            ],
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-without-assets-flag'
                // No assets flag
            ]
        ]);

        // Mock npm install command
        $this->mockCommands([
            'npm install' => function ($host, $command) {
                if(str_contains($command, 'test-plugin-without-assets') || str_contains($command, 'test-plugin-without-assets-flag')) {
                    throw new RuntimeException('npm install called for plugin without assets/package.json');
                }
                return 'npm install completed.';
            },
        ], 'testremote');

        // Run the task
        $result = $this->dep('packages:assets:vendors');
        $output = $this->tester->getDisplay();
        $this->assertEquals(0, $result);
    }

    public function testPackagesAssetsBuildOnlyRunsWhenAssetsIsTrue(): void
    {
        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-with-assets';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-without-assets';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-without-assets-flag';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Create test packages configuration
        $this->deployer->config->set('packages', [
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-with-assets',
                'assets' => true
            ],
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-without-assets',
                'assets' => false
            ],
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-without-assets-flag'
                // No assets flag
            ]
        ]);

        // Mock npm build command
        $this->mockCommands([
            'npm run-script build' => function ($host, $command) {
                if(str_contains($command, 'test-plugin-without-assets') || str_contains($command, 'test-plugin-without-assets-flag')) {
                    throw new RuntimeException('npm build called for plugin without assets/package.json');
                }
                return 'npm build completed.';
            },
        ], 'testremote');

        // Run the task
        $result = $this->dep('packages:assets:build');
        $this->assertEquals(0, $result);
    }

    public function testPackagesVendorsOnlyRunsWhenVendorsIsTrue(): void
    {
        // Copy test plugin from fixtures to local
        $fixturePluginDir = $this->getFixturePath('mu-plugins/test-plugin');

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-with-vendors';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-without-vendors';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        $targetPluginDir = $this->localMuPluginsDir . '/test-plugin-without-vendors-flag';
        mkdir($targetPluginDir, 0755, true);
        $this->copyDirectory($fixturePluginDir, $targetPluginDir);

        // Create test packages configuration
        $this->deployer->config->set('packages', [
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-with-vendors',
                'vendors' => true
            ],
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-without-vendors',
                'vendors' => false
            ],
            [
                'path' => '{{mu-plugins/dir}}/test-plugin-without-vendors-flag'
                // No assets flag
            ]
        ]);

        // Mock npm install command
        $this->mockCommands([
            'composer install' => function ($host, $command) {
                if(str_contains($command, 'test-plugin-without-vendors') || str_contains($command, 'test-plugin-without-vendors-flag')) {
                    throw new RuntimeException('composer install called for plugin without vendors');
                }
                return 'npm install completed.';
            },
        ], 'testremote');

        // Run the task
        $result = $this->dep('packages:vendors');
        $this->assertEquals(0, $result);
    }
}
