<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Neologik\ComposerGitInstaller\EventSubscriber\GitInstallEventSubscriber;
use Neologik\ComposerGitInstaller\Parser\UrlParserChain;
use Neologik\ComposerGitInstaller\Resolver\PackageResolver;
use Neologik\ComposerGitInstaller\Security\ValidationPipeline;

/**
 * Main plugin class that handles git+<repo>@<branch> syntax support for Composer.
 *
 * This plugin intercepts Composer commands to detect git+ URLs and transforms them
 * into proper VCS repositories that Composer can handle natively.
 */
class GitInstallPlugin implements PluginInterface
{
    private ?Composer $composer = null;
    private ?IOInterface $io = null;
    private ?GitInstallEventSubscriber $eventSubscriber = null;

    /**
     * Activate the plugin with Composer and IO interfaces.
     *
     * @param Composer    $composer The composer instance
     * @param IOInterface $io       The IO interface instance
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        // Initialize components
        $urlParser = new UrlParserChain();
        $packageResolver = new PackageResolver($composer, $io);
        $validator = new ValidationPipeline($composer, $io);

        // Create and register event subscriber
        $this->eventSubscriber = new GitInstallEventSubscriber(
            $composer,
            $io,
            $urlParser,
            $packageResolver,
            $validator
        );

        // Register the event subscriber with Composer's event dispatcher
        $composer->getEventDispatcher()->addSubscriber($this->eventSubscriber);

        $io->writeError('<info>Git Install Plugin activated</info>', true, IOInterface::VERBOSE);
    }

    /**
     * Deactivate the plugin.
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Remove event subscriber from dispatcher
        if ($this->eventSubscriber !== null && $this->composer !== null) {
            $eventDispatcher = $this->composer->getEventDispatcher();
            foreach ($this->eventSubscriber->getSubscribedEvents() as $eventName => $params) {
                if (is_string($params)) {
                    $eventDispatcher->removeListener($eventName, [$this->eventSubscriber, $params]);
                } elseif (is_array($params) && isset($params[0])) {
                    $method = $params[0];
                    $eventDispatcher->removeListener($eventName, [$this->eventSubscriber, $method]);
                }
            }
        }

        $this->composer = null;
        $this->io = null;
        $this->eventSubscriber = null;
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Cleanup any persistent data if needed
    }

    /**
     * Get the event subscriber instance for testing purposes.
     */
    public function getEventSubscriber(): ?GitInstallEventSubscriber
    {
        return $this->eventSubscriber;
    }
}
