<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerWordpress\Rsync;

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

    public function testBuildOptionsArrayWithComplexPaths(): void
    {
        $config = [
            'exclude' => [
                'path/with spaces/file.txt',
                'path/with/special@chars',
                'path/with/backslash\\file.txt',
                'path/with/dollar$file.txt',
                'path/with/asterisk*file.txt',
                'path/with/question?file.txt'
            ],
            'include' => [
                'path/with/quotes"file.txt',
                'path/with/single\'quote.txt',
                'path/with/backtick`file.txt'
            ]
        ];

        $result = Rsync::buildOptionsArray($config);

        // Test spaces in paths
        $this->assertContains('--exclude=path/with spaces/file.txt', $result);
        
        // Test special characters
        $this->assertContains('--exclude=path/with/special@chars', $result);
        $this->assertContains('--exclude=path/with/backslash\\file.txt', $result);
        $this->assertContains('--exclude=path/with/dollar$file.txt', $result);
        $this->assertContains('--exclude=path/with/asterisk*file.txt', $result);
        $this->assertContains('--exclude=path/with/question?file.txt', $result);
        
        // Test quotes and backticks
        $this->assertContains('--include=path/with/quotes"file.txt', $result);
        $this->assertContains('--include=path/with/single\'quote.txt', $result);
        $this->assertContains('--include=path/with/backtick`file.txt', $result);
    }

    public function testBuildOptionsArrayWithRelativePaths(): void
    {
        $config = [
            'exclude' => [
                './local/path',
                '../parent/path',
                '../../root/path',
                '././nested/./path',
                '.././mixed/../path'
            ],
            'include' => [
                '~/home/path',
                '~user/home/path'
            ]
        ];

        $result = Rsync::buildOptionsArray($config);

        // Test relative paths
        $this->assertContains('--exclude=./local/path', $result);
        $this->assertContains('--exclude=../parent/path', $result);
        $this->assertContains('--exclude=../../root/path', $result);
        
        // Test mixed relative paths
        $this->assertContains('--exclude=././nested/./path', $result);
        $this->assertContains('--exclude=.././mixed/../path', $result);
        
        // Test home directory paths
        $this->assertContains('--include=~/home/path', $result);
        $this->assertContains('--include=~user/home/path', $result);
    }

    public function testBuildOptionsArrayWithEmptyAndWhitespaceValues(): void
    {
        $config = [
            'options' => ['', ' ', '  ', "\t", "\n", 'valid-option'],
            'exclude' => ['', ' ', '  ', "\t", "\n", 'valid-exclude'],
            'include' => ['', ' ', '  ', "\t", "\n", 'valid-include'],
            'filter' => ['', ' ', '  ', "\t", "\n", 'valid-filter']
        ];

        $result = Rsync::buildOptionsArray($config);

        // Check that non-empty values are present
        $this->assertContains('--valid-option', $result);
        $this->assertContains('--exclude=valid-exclude', $result);
        $this->assertContains('--include=valid-include', $result);
        $this->assertContains('--filter=valid-filter', $result);

        // Check that no empty values are present
        $this->assertNotContains('', $result, "Empty string found in result");
        $this->assertNotContains('--exclude=', $result, "Empty exclude found in result");
        $this->assertNotContains('--include=', $result, "Empty include found in result");
        $this->assertNotContains('--filter=', $result, "Empty filter found in result");

        // Check that defaults are not present (user values should override)
        $this->assertNotContains('--delete-after', $result);
        $this->assertNotContains('--exclude=.git', $result);
        $this->assertNotContains('--exclude=deploy.php', $result);
    }

    public function testBuildOptionsArrayWithVeryLongPaths(): void
    {
        // Create a path that's 255 characters long (common filesystem limit)
        $longPath = str_repeat('a', 200) . '/path/to/file.txt';
        
        $config = [
            'exclude' => [$longPath],
            'include' => [$longPath],
            'filter' => ['+ ' . $longPath]
        ];

        $result = Rsync::buildOptionsArray($config);

        $this->assertContains('--exclude=' . $longPath, $result);
        $this->assertContains('--include=' . $longPath, $result);
        $this->assertContains('--filter=+ ' . $longPath, $result);
    }

    public function testBuildOptionsArrayWithUnicodeCharacters(): void
    {
        $config = [
            'exclude' => [
                'path/with/unicode/测试.txt',
                'path/with/unicode/über.txt',
                'path/with/unicode/🎉.txt'
            ],
            'include' => [
                'path/with/unicode/日本語.txt',
                'path/with/unicode/русский.txt'
            ]
        ];

        $result = Rsync::buildOptionsArray($config);

        $this->assertContains('--exclude=path/with/unicode/测试.txt', $result);
        $this->assertContains('--exclude=path/with/unicode/über.txt', $result);
        $this->assertContains('--exclude=path/with/unicode/🎉.txt', $result);
        $this->assertContains('--include=path/with/unicode/日本語.txt', $result);
        $this->assertContains('--include=path/with/unicode/русский.txt', $result);
    }

    public function testBuildOptionsArrayWithInvalidConfigurationTypes(): void
    {
        $config = [
            'exclude' => 'not-an-array',
            'include' => 123,
            'filter' => true,
            'options' => null,
            'exclude-file' => ['should-be-string'],
            'include-file' => 456,
            'filter-file' => false,
            'filter-perdir' => ['should-be-string']
        ];

        $result = Rsync::buildOptionsArray($config);

        $this->assertContains('--exclude=not-an-array', $result);
        $this->assertNotContains('--exclude=deploy.php', $result); // Not default value
        $this->assertNotContains('--delete-after', $result); // Not default value
        $this->assertNotContains('--exclude-file=should-be-string', $result);
        $this->assertNotContains('--filter=dir-merge should-be-string', $result);
    }

    public function testBuildOptionsArrayWithEmptyConfigurationValues(): void
    {
        $config = [
            'exclude' => [],
            'include' => [],
            'filter' => [],
            'options' => []
        ];

        $result = Rsync::buildOptionsArray($config);

        // Empty arrays should replace defaults
        $this->assertNotContains('--exclude=.git', $result);
        $this->assertNotContains('--exclude=deploy.php', $result);
        $this->assertNotContains('--delete-after', $result);
    }

    public function testBuildOptionsArrayWithPartialConfiguration(): void
    {
        $config = [
            'exclude' => ['custom.txt'],
            'options' => ['recursive']
        ];

        $result = Rsync::buildOptionsArray($config);

        // Should use custom values over defaults
        $this->assertContains('--exclude=custom.txt', $result);
        $this->assertNotContains('--exclude=.git', $result);
        $this->assertNotContains('--exclude=deploy.php', $result);
        $this->assertContains('--recursive', $result);
        $this->assertNotContains('--delete-after', $result);
    }

    public function testBuildOptionsArrayWithOverriddenDefaults(): void
    {
        $config = [
            'exclude' => ['custom.txt'],
            'options' => ['recursive']
        ];

        // Override default config
        $this->deployer->config->set('rsync', [
            'exclude' => ['default.txt'],
            'options' => ['verbose']
        ]);

        $result = Rsync::buildOptionsArray($config);

        // Should use custom values over defaults
        $this->assertContains('--exclude=custom.txt', $result);
        $this->assertNotContains('--exclude=default.txt', $result);
        $this->assertContains('--recursive', $result);
        $this->assertNotContains('--verbose', $result);
    }
}
