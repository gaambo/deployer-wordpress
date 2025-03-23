<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerWordpress\Rsync;
use PHPUnit\Framework\MockObject\MockObject;

class RsyncIntegrationTest extends IntegrationTestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __FIXTURES__ . '/rsync';

        // Set default rsync configuration
        $this->deployer->config->set('rsync', [
            'exclude' => [
                '.git',
                'deploy.php',
            ],
            'exclude-file' => false,
            'include' => [],
            'include-file' => false,
            'filter' => [],
            'filter-file' => false,
            'filter-perdir' => false,
            'options' => ['delete-after'], // needed so deployfilter files are send and delete is checked afterwards
        ]);
    }

    public function testBuildOptionsWithArray(): void
    {
        $options = ['delete-after', 'recursive', 'verbose'];
        $result = Rsync::buildOptions($options);

        $this->assertCount(3, $result);
        $this->assertContains('--delete-after', $result);
        $this->assertContains('--recursive', $result);
        $this->assertContains('--verbose', $result);
    }

    public function testBuildOptionsWithEmptyArray(): void
    {
        $result = Rsync::buildOptions([]);
        $this->assertEmpty($result);
    }

    public function testBuildOptionsWithSpecialCharacters(): void
    {
        $options = ['delete after', 'recursive-update', 'verbose=true'];
        $result = Rsync::buildOptions($options);

        $this->assertCount(3, $result);
        $this->assertContains('--delete after', $result);
        $this->assertContains('--recursive-update', $result);
        $this->assertContains('--verbose=true', $result);
    }

    public function testBuildOptionsArrayWithCustomConfig(): void
    {
        $config = [
            'exclude' => ['*.log', '*.tmp'],
            'exclude-file' => $this->fixturesDir . '/exclude.txt',
            'include' => ['*.php', '*.js'],
            'include-file' => $this->fixturesDir . '/include.txt',
            'filter' => ['+ /wp-content/'],
            'filter-file' => $this->fixturesDir . '/filter.txt',
            'filter-perdir' => '.deployfilter',
            'options' => ['delete-after', 'recursive']
        ];
        
        $result = Rsync::buildOptionsArray($config);
        
        $this->assertContains('--exclude=*.log', $result);
        $this->assertContains('--exclude=*.tmp', $result);
        $this->assertContains('--exclude-from=' . $config['exclude-file'], $result);
        $this->assertContains('--include=*.php', $result);
        $this->assertContains('--include=*.js', $result);
        $this->assertContains('--include-from=' . $config['include-file'], $result);
        $this->assertContains('--filter=+ /wp-content/', $result);
        $this->assertContains('--filter=merge ' . $config['filter-file'], $result);
        $this->assertContains('--filter=dir-merge ' . $config['filter-perdir'], $result);
        $this->assertContains('--delete-after', $result);
        $this->assertContains('--recursive', $result);
    }

    public function testBuildOptionsArrayWithDefaults(): void
    {
        $result = Rsync::buildOptionsArray();
        
        // Default config should include these
        $this->assertContains('--exclude=.git', $result);
        $this->assertContains('--exclude=deploy.php', $result);
        $this->assertContains('--delete-after', $result);
        
        // These should not be present in default config
        $this->assertNotContains('--include=', $result);
        $this->assertNotContains('--include-from=', $result);
        $this->assertNotContains('--filter=', $result);
        $this->assertNotContains('--filter=merge', $result);
        $this->assertNotContains('--filter=dir-merge', $result);
    }

    public function testBuildOptionsArrayWithEmptyConfig(): void
    {
        $result = Rsync::buildOptionsArray([]);
        
        // Should still include defaults
        $this->assertContains('--exclude=.git', $result);
        $this->assertContains('--exclude=deploy.php', $result);
        $this->assertContains('--delete-after', $result);
    }

    public function testBuildOptionsArrayWithNoDeployerConfig(): void
    {
        // Remove the rsync config
        $this->deployer->config->set('rsync', null);
        
        $result = Rsync::buildOptionsArray();
        
        // Should use hardcoded defaults
        $this->assertContains('--exclude=.git', $result);
        $this->assertContains('--exclude=deploy.php', $result);
        $this->assertContains('--delete-after', $result);
    }

    public function testBuildOptionsArrayWithInvalidDeployerConfig(): void
    {
        // Set an invalid config
        $this->deployer->config->set('rsync', 'invalid');
        
        $result = Rsync::buildOptionsArray();
        
        // Should use hardcoded defaults
        $this->assertContains('--exclude=.git', $result);
        $this->assertContains('--exclude=deploy.php', $result);
        $this->assertContains('--delete-after', $result);
    }

    public function testBuildOptionsArrayWithInvalidFilePaths(): void
    {
        $config = [
            'exclude-file' => '/nonexistent/exclude.txt',
            'include-file' => '/nonexistent/include.txt',
            'filter-file' => '/nonexistent/filter.txt'
        ];
        
        $result = Rsync::buildOptionsArray($config);
        
        // Invalid files should be ignored
        $this->assertNotContains('--exclude-from=/nonexistent/exclude.txt', $result);
        $this->assertNotContains('--include-from=/nonexistent/include.txt', $result);
        $this->assertNotContains('--filter=merge /nonexistent/filter.txt', $result);
    }

    public function testBuildOptionsArrayFiltersEmptyStrings(): void
    {
        $config = [
            'options' => ['', 'delete-after', '', 'recursive', ''],
            'exclude' => ['', '.git', ''],
            'include' => ['', '*.php', ''],
            'filter' => ['', '+ /wp-content/', '']
        ];
        
        $result = Rsync::buildOptionsArray($config);
        
        // Check that non-empty values are present
        $this->assertContains('--delete-after', $result);
        $this->assertContains('--recursive', $result);
        $this->assertContains('--exclude=.git', $result);
        $this->assertContains('--include=*.php', $result);
        $this->assertContains('--filter=+ /wp-content/', $result);
        
        // Check that no empty values are present
        $this->assertNotContains('', $result, "Empty string found in result");
        $this->assertNotContains('--exclude=', $result, "Empty exclude found in result");
        $this->assertNotContains('--include=', $result, "Empty include found in result");
        $this->assertNotContains('--filter=', $result, "Empty filter found in result");
    }
} 