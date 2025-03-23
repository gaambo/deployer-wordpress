<?php

namespace Gaambo\DeployerWordpress\Tests\Unit;

use Gaambo\DeployerWordpress\Rsync;
use PHPUnit\Framework\TestCase;

class RsyncTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = __FIXTURES__ . '/rsync';
    }

    public function testBuildExcludesWithExistingFile()
    {
        $excludes = ['*.log', '*.tmp', '.DS_Store'];
        $excludeFile = $this->fixturesDir . '/exclude.txt';
        
        $result = Rsync::buildExcludes($excludes, $excludeFile);
        
        $this->assertContains('--exclude=*.log', $result);
        $this->assertContains('--exclude=*.tmp', $result);
        $this->assertContains('--exclude=.DS_Store', $result);
        $this->assertContains('--exclude-from=' . $excludeFile, $result);
    }

    public function testBuildExcludesWithNonExistingFile()
    {
        $excludes = ['*.log', '*.tmp', '.DS_Store'];
        $excludeFile = '/path/to/nonexistent.txt';
        
        $result = Rsync::buildExcludes($excludes, $excludeFile);
        
        $this->assertContains('--exclude=*.log', $result);
        $this->assertContains('--exclude=*.tmp', $result);
        $this->assertContains('--exclude=.DS_Store', $result);
        $this->assertNotContains('--exclude-from=' . $excludeFile, $result);
    }

    public function testBuildIncludesWithExistingFile()
    {
        $includes = ['*.php', '*.js', '*.css'];
        $includeFile = $this->fixturesDir . '/include.txt';
        
        $result = Rsync::buildIncludes($includes, $includeFile);
        
        $this->assertContains('--include=*.php', $result);
        $this->assertContains('--include=*.js', $result);
        $this->assertContains('--include=*.css', $result);
        $this->assertContains('--include-from=' . $includeFile, $result);
    }

    public function testBuildIncludesWithNonExistingFile()
    {
        $includes = ['*.php', '*.js', '*.css'];
        $includeFile = '/path/to/nonexistent.txt';
        
        $result = Rsync::buildIncludes($includes, $includeFile);
        
        $this->assertContains('--include=*.php', $result);
        $this->assertContains('--include=*.js', $result);
        $this->assertContains('--include=*.css', $result);
        $this->assertNotContains('--include-from=' . $includeFile, $result);
    }

    public function testBuildFilterWithExistingFile()
    {
        $filters = ['+ /wp-content/', '- /wp-content/uploads/*'];
        $filterFile = $this->fixturesDir . '/filter.txt';
        $filterPerDir = '.deployfilter';
        
        $result = Rsync::buildFilter($filters, $filterFile, $filterPerDir);
        
        $this->assertContains('--filter=+ /wp-content/', $result);
        $this->assertContains('--filter=- /wp-content/uploads/*', $result);
        $this->assertContains('--filter=merge ' . $filterFile, $result);
        $this->assertContains('--filter=dir-merge ' . $filterPerDir, $result);
    }

    public function testBuildFilterWithNonExistingFile()
    {
        $filters = ['+ /wp-content/', '- /wp-content/uploads/*'];
        $filterFile = '/path/to/nonexistent.txt';
        $filterPerDir = '.deployfilter';
        
        $result = Rsync::buildFilter($filters, $filterFile, $filterPerDir);
        
        $this->assertContains('--filter=+ /wp-content/', $result);
        $this->assertContains('--filter=- /wp-content/uploads/*', $result);
        $this->assertNotContains('--filter=merge ' . $filterFile, $result);
        $this->assertContains('--filter=dir-merge ' . $filterPerDir, $result);
    }

    public function testBuildExcludesWithArray(): void
    {
        $excludes = ['*.log', '*.tmp', '.DS_Store'];
        $result = Rsync::buildExcludes($excludes);

        $this->assertCount(3, $result);
        $this->assertContains('--exclude=*.log', $result);
        $this->assertContains('--exclude=*.tmp', $result);
        $this->assertContains('--exclude=.DS_Store', $result);
    }

    public function testBuildExcludesWithEmptyArray(): void
    {
        $result = Rsync::buildExcludes([]);
        $this->assertEmpty($result);
    }

    public function testBuildIncludesWithArray(): void
    {
        $includes = ['*.php', '*.js', '*.css'];
        $result = Rsync::buildIncludes($includes);

        $this->assertCount(3, $result);
        $this->assertContains('--include=*.php', $result);
        $this->assertContains('--include=*.js', $result);
        $this->assertContains('--include=*.css', $result);
    }

    public function testBuildIncludesWithEmptyArray(): void
    {
        $result = Rsync::buildIncludes([]);
        $this->assertEmpty($result);
    }

    public function testBuildFilterWithArray(): void
    {
        $filters = [
            '+ /wp-content/',
            '- /wp-content/uploads/*'
        ];
        $result = Rsync::buildFilter($filters);

        $this->assertCount(2, $result);
        $this->assertContains('--filter=+ /wp-content/', $result);
        $this->assertContains('--filter=- /wp-content/uploads/*', $result);
    }

    public function testBuildFilterWithEmptyArray(): void
    {
        $result = Rsync::buildFilter([]);
        $this->assertEmpty($result);
    }
} 