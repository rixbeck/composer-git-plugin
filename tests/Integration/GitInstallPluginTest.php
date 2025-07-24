<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Tests\Integration;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Neologik\ComposerGitInstaller\EventSubscriber\GitInstallEventSubscriber;
use Neologik\ComposerGitInstaller\GitInstallPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the main plugin class.
 *
 * @internal
 *
 * @small
 */
class GitInstallPluginTest extends TestCase
{
    private GitInstallPlugin $plugin;
    /** @var Composer&\PHPUnit\Framework\MockObject\MockObject */
    private $composer;
    /** @var IOInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $io;

    /**
     * @test
     */
    public function eventSubscriberRegistration(): void
    {
        // First activate the plugin
        $this->plugin->activate($this->composer, $this->io);

        // Check that event subscriber is registered
        $eventSubscriber = $this->plugin->getEventSubscriber();
        self::assertNotNull($eventSubscriber);
        self::assertInstanceOf(GitInstallEventSubscriber::class, $eventSubscriber);

        // Check subscribed events
        $events = $eventSubscriber::getSubscribedEvents();
        self::assertIsArray($events);
        self::assertArrayHasKey(PluginEvents::PRE_COMMAND_RUN, $events);
        self::assertEquals(['onPreCommandRun', 10], $events[PluginEvents::PRE_COMMAND_RUN]);
    }

    /**
     * @test
     */
    public function activate(): void
    {
        $this->io->expects(self::once())
            ->method('writeError')
            ->with(
                self::stringContains('Git Install Plugin activated'),
                true,
                IOInterface::VERBOSE,
            );

        $this->plugin->activate($this->composer, $this->io);

        // No exception should be thrown
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function deactivate(): void
    {
        // First activate the plugin
        $this->plugin->activate($this->composer, $this->io);

        // Then deactivate
        $this->plugin->deactivate($this->composer, $this->io);

        // No exception should be thrown
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function uninstall(): void
    {
        $this->plugin->uninstall($this->composer, $this->io);

        // No exception should be thrown
        self::assertTrue(true);
    }

    /**
     * Sets up the test environment before each test.
     */
    protected function setUp(): void
    {
        $this->composer = $this->createMock(Composer::class);
        $this->io = $this->createMock(IOInterface::class);
        $this->plugin = new GitInstallPlugin();
    }
}
