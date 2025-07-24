<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Processor;

use Composer\IO\IOInterface;
use Neologik\ComposerGitInstaller\Exception\GitInstallException;
use Neologik\ComposerGitInstaller\Parser\UrlParserChain;
use Neologik\ComposerGitInstaller\Resolver\PackageResolver;
use Neologik\ComposerGitInstaller\Security\ValidationPipeline;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Processes Composer command arguments to detect and transform git+ URLs.
 *
 * This class handles the command line argument analysis, git URL detection,
 * package name resolution, and command argument transformation for normal
 * Composer processing.
 */
class CommandProcessor
{
    private UrlParserChain $urlParser;
    private PackageResolver $packageResolver;
    private ValidationPipeline $validator;
    private IOInterface $io;

    /**
     * CommandProcessor constructor.
     *
     * @param UrlParserChain $urlParser The URL parser chain instance
     * @param PackageResolver $packageResolver The package resolver instance
     * @param ValidationPipeline $validator The validation pipeline instance
     * @param IOInterface $io The input/output interface
     */
    public function __construct(
        UrlParserChain $urlParser,
        PackageResolver $packageResolver,
        ValidationPipeline $validator,
        IOInterface $io,
    ) {
        $this->urlParser = $urlParser;
        $this->packageResolver = $packageResolver;
        $this->validator = $validator;
        $this->io = $io;
    }

    /**
     * Process command input to detect and transform git+ URLs.
     *
     * @param InputInterface $input   The command input containing arguments
     * @param string         $command The command name being executed
     *
     * @throws GitInstallException If URL processing fails
     */
    public function processCommand(InputInterface $input, string $command): void
    {
        $arguments = $input->getArguments();
        $packages = $arguments['packages'] ?? [];

        if (empty($packages)) {
            return;
        }

        $this->io->writeError(
            sprintf('<info>Git Install Plugin: Analyzing %d package(s) for git+ URLs</info>', count($packages)),
            true,
            IOInterface::DEBUG,
        );

        $modified = false;
        $gitPackages = [];

        /** @var string[] $packages */
        foreach ($packages as $index => $package) {
            if ($this->isGitUrl($package)) {
                $this->io->writeError(
                    sprintf('<info>Processing git+ URL: %s</info>', $package),
                    true,
                    IOInterface::VERBOSE,
                );

                $transformedPackage = $this->transformGitUrl($package);
                $packages[$index] = $transformedPackage;
                $gitPackages[] = $package;
                $modified = true;
            }
        }

        if ($modified) {
            $this->updateCommandArguments($input, $packages);
            $this->logTransformation($command, $gitPackages);
        }
    }

    /**
     * Check if a package string contains git+ URL syntax.
     *
     * @param string $package The package string to check
     *
     * @return bool True if package is a git+ URL
     */
    public function isGitUrl(string $package): bool
    {
        return str_starts_with($package, 'git+');
    }

    /**
     * Transform a git+ URL into a Composer-compatible package reference.
     *
     * This method parses the git+ URL, validates it through the security pipeline,
     * registers the VCS repository with Composer, and returns the package name.
     *
     * @param string $gitUrl The git+ URL to transform
     *
     * @throws GitInstallException If URL parsing or validation fails
     *
     * @return string The resolved package name
     */
    public function transformGitUrl(string $gitUrl): string
    {
        try {
            // Parse the git+ URL using the chain of responsibility pattern
            $parsedUrl = $this->urlParser->parse($gitUrl);

            // Validate through security pipeline (strategy pattern)
            $this->validator->validate($parsedUrl);

            // Register repository and resolve package name
            $packageName = $this->packageResolver->resolve($parsedUrl);

            $this->io->writeError(
                sprintf('<info>Registered VCS repository for package: %s</info>', $packageName),
                true,
                IOInterface::VERBOSE,
            );

            return $packageName;
        } catch (GitInstallException $e) {
            $this->io->writeError(
                sprintf('<error>Failed to process git+ URL %s: %s</error>', $gitUrl, $e->getMessage()),
            );
            throw $e;
        }
    }

    /**
     * Extract git+ URLs from package arguments.
     *
     * @param array<string> $packages Array of package arguments
     *
     * @return array<string> Array containing only git+ URLs
     */
    public function extractGitUrls(array $packages): array
    {
        return array_filter($packages, [$this, 'isGitUrl']);
    }

    /**
     * Get package names for git+ URLs without processing them.
     *
     * @param array<string> $gitUrls Array of git+ URLs
     *
     * @return array<string> Array of suggested package names
     */
    public function getPackageNames(array $gitUrls): array
    {
        $packageNames = [];

        foreach ($gitUrls as $gitUrl) {
            try {
                $parsedUrl = $this->urlParser->parse($gitUrl);
                $packageNames[] = $parsedUrl->getSuggestedPackageName();
            } catch (GitInstallException $e) {
                $this->io->writeError(
                    sprintf('<warning>Could not extract package name from %s: %s</warning>', $gitUrl, $e->getMessage()),
                    true,
                    IOInterface::VERBOSE,
                );
            }
        }

        return $packageNames;
    }

    /**
     * Handle conflicts when multiple git+ packages suggest the same name.
     *
     * @param array<string> $gitUrls Array of git+ URLs
     *
     * @return array<string,string> Resolved package names with conflict resolution
     */
    public function handlePackageNameConflicts(array $gitUrls): array
    {
        $packageNames = [];
        $conflicts = [];

        foreach ($gitUrls as $gitUrl) {
            try {
                $parsedUrl = $this->urlParser->parse($gitUrl);
                $suggestedName = $parsedUrl->getSuggestedPackageName();

                if (isset($packageNames[$suggestedName])) {
                    $conflicts[] = $suggestedName;
                    // Add repository identifier suffix to resolve conflict
                    $packageNames[$gitUrl] = $suggestedName . '-' . $parsedUrl->getRepositoryIdentifier();
                } else {
                    $packageNames[$suggestedName] = $gitUrl;
                }
            } catch (GitInstallException $e) {
                $this->io->writeError(
                    sprintf('<error>Error resolving package name for %s: %s</error>', $gitUrl, $e->getMessage()),
                );
            }
        }

        if (!empty($conflicts)) {
            $this->io->writeError(
                sprintf('<warning>Resolved %d package name conflicts</warning>', count($conflicts)),
                true,
                IOInterface::VERBOSE,
            );
        }

        return array_flip($packageNames);
    }

    /**
     * Update command arguments with transformed packages.
     *
     * @param InputInterface $input    The command input to modify
     * @param array<string>  $packages The updated packages array
     */
    private function updateCommandArguments(InputInterface $input, array $packages): void
    {
        $input->setArgument('packages', $packages);

        $this->io->writeError(
            '<info>Git Install Plugin: Command arguments updated with transformed packages</info>',
            true,
            IOInterface::DEBUG,
        );
    }

    /**
     * Log the transformation results for audit purposes.
     *
     * @param string        $command     The command that was processed
     * @param array<string> $gitPackages The git+ URLs that were processed
     */
    private function logTransformation(string $command, array $gitPackages): void
    {
        $this->io->writeError(
            sprintf(
                '<info>Git Install Plugin: Successfully processed %d git+ URL(s) in %s command</info>',
                count($gitPackages),
                $command,
            ),
            true,
            IOInterface::VERBOSE,
        );

        foreach ($gitPackages as $gitUrl) {
            $this->io->writeError(
                sprintf('  - %s', $gitUrl),
                true,
                IOInterface::DEBUG,
            );
        }
    }
}
