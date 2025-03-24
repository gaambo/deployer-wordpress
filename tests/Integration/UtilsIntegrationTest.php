<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Gaambo\DeployerWordpress\Utils;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;

class UtilsIntegrationTest extends IntegrationTestCase
{
    private MockObject $outputMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the output interface
        $this->outputMock = $this->createMock(OutputInterface::class);
        $this->deployer['output'] = $this->outputMock;
    }

    /**
     * @dataProvider verbosityProvider
     */
    public function testGetVerbosityArgument(bool $isVerbose, bool $isVeryVerbose, bool $isDebug, string $expectedArgument): void
    {
        $this->outputMock->method('isVerbose')->willReturn($isVerbose);
        $this->outputMock->method('isVeryVerbose')->willReturn($isVeryVerbose);
        $this->outputMock->method('isDebug')->willReturn($isDebug);

        $result = Utils::getVerbosityArgument();
        $this->assertEquals($expectedArgument, $result);
    }

    public static function verbosityProvider(): array
    {
        return [
            'normal output' => [false, false, false, ''],
            'verbose output' => [true, false, false, '-v'],
            'very verbose output' => [false, true, false, '-vv'],
            'debug output' => [false, false, true, '-vvv'],
        ];
    }
} 