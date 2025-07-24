<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\EventSubscriber;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PreCommandRunEvent;
use Neologik\ComposerGitInstaller\Exception\GitInstallException;
use Neologik\ComposerGitInstaller\Parser\UrlParserChain;
use Neologik\ComposerGitInstaller\Processor\CommandProcessor;
use Neologik\ComposerGitInstaller\Resolver\PackageResolver;
use Neologik\ComposerGitInstaller\Security\ValidationPipeline;

/**
 * Event subscriber that handles PRE_COMMAND_RUN events to intercept git+ URLs.
 * 
 * This subscriber detects git+ syntax in composer require commands and processes
 * them through the validation and transformation pipeline.
 */
class GitInstallEventSubscriber implements EventSubscriberInterface
{
    private IOInterface $io;
    private CommandProcessor $commandProcessor;

    public function __construct(
        IOInterface $io,
        UrlParserChain $urlParser,
        PackageResolver $packageResolver,
        ValidationPipeline $validator
    ) {
        $this->io = $io;
        $this->commandProcessor = new CommandProcessor(
            $urlParser,
            $packageResolver,
            $validator,
            $io
        );
    }

    /**
     * Subscribe to Composer events.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PRE_COMMAND_RUN => ['onPreCommandRun', 10],
        ];
    }

    /**
     * Handle PRE_COMMAND_RUN event to intercept git+ URLs.
     * 
     * This method processes composer require/install/update commands and detects
     * git+ syntax in package arguments. When found, it validates and transforms
     * the URLs into proper VCS repositories that Composer can handle.
     */
    public function onPreCommandRun(PreCommandRunEvent $event): void
    {
        $input = $event->getInput();
        $command = $event->getCommand();

        // Only process install/require/update commands
        if (!in_array($command, ['install', 'require', 'update'], true)) {
            return;
        }

        $this->io->writeError(
            sprintf('<info>Git Install Plugin: Processing %s command</info>', $command),
            true,
            IOInterface::DEBUG
        );

        try {
            $this->commandProcessor->processCommand($input, $command);
        } catch (GitInstallException $e) {
            $this->io->writeError(
                sprintf('<error>Git Install Plugin Error: %s</error>', $e->getMessage())
            );
            throw $e;
        }
    }
}