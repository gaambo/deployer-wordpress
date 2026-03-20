<?php

namespace Gaambo\DeployerWordpress\Tests\Integration;

use Deployer\Deployer;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

abstract class IntegrationTestCase extends TestCase
{
    protected Deployer $deployer;
    protected MockObject|Input $input;
    protected MockObject|Output $output;
    protected Host $host;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a new Deployer instance
        $console = new Application();
        $this->deployer = new Deployer($console);

        // Create mocked Input and Output
        $this->input = $this->createMock(Input::class);
        $this->output = $this->createMock(Output::class);

        // Set up the mocked components in Deployer
        $this->deployer['input'] = $this->input;
        $this->deployer['output'] = $this->output;

        // Create a localhost instance for testing
        $this->host = new Localhost();
        $this->host->set('deploy_path', '/var/www');
        $this->host->set('bin/wp', 'wp');
        $this->host->set('release_or_current_path', '/var/www/current');
        $this->deployer->hosts->set('localhost', $this->host);

        // Push a new context with our host
        Context::push(new Context($this->host));
    }

    protected function tearDown(): void
    {
        // Pop the context to clean up
        Context::pop();

        // Clean up the Deployer instance
        unset($this->deployer);

        parent::tearDown();
    }
}
