<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Neologik\ComposerGitInstaller\EventSubscriber\GitInstallEventSubscriber;
use Neologik\ComposerGitInstaller\GitInstallPlugin;

/**
 * Integration tests for the main plugin class.
 */
class GitInstallPluginTest extends TestCase
{
    private GitInstallPlugin $plugin;
    /** @var Composer&\PHPUnit\Framework\MockObject\MockObject */
    private $composer;
    /** @var IOInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $io;

    protected function setUp(): void
    {
        $this->composer = $this->createMock(Composer::class);
        $this->io = $this->createMock(IOInterface::class);
        $this->plugin = new GitInstallPlugin();
    }

    public function testEventSubscriberRegistration(): void
    {
        // First activate the plugin
        $this->plugin->activate($this->composer, $this->io);
        
        // Check that event subscriber is registered
        $eventSubscriber = $this->plugin->getEventSubscriber();
        $this->assertNotNull($eventSubscriber);
        $this->assertInstanceOf(GitInstallEventSubscriber::class, $eventSubscriber);
        
        // Check subscribed events
        $events = $eventSubscriber::getSubscribedEvents();
        $this->assertIsArray($events);
        $this->assertArrayHasKey(PluginEvents::PRE_COMMAND_RUN, $events);
        $this->assertEquals(['onPreCommandRun', 10], $events[PluginEvents::PRE_COMMAND_RUN]);
    }

    public function testActivate(): void
    {
        $this->io->expects($this->once())
            ->method('writeError')
            ->with(
                $this->stringContains('Git Install Plugin activated'),
                true,
                IOInterface::VERBOSE
            );

        $this->plugin->activate($this->composer, $this->io);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testDeactivate(): void
    {
        // First activate the plugin
        $this->plugin->activate($this->composer, $this->io);
        
        // Then deactivate
        $this->plugin->deactivate($this->composer, $this->io);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testUninstall(): void
    {
        $this->plugin->uninstall($this->composer, $this->io);
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
}