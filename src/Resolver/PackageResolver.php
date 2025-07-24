<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Resolver;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\VcsRepository;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\GitInstallException;

/**
 * Resolves git+ URLs into Composer packages and repositories.
 */
class PackageResolver
{
    private RepositoryManager $repositoryManager;

    public function __construct(
        Composer $composer,
        private readonly IOInterface $io
    ) {
        $this->repositoryManager = $composer->getRepositoryManager();
    }

    /**
     * Resolve a parsed URL into a package name and register the repository.
     */
    public function resolve(ParsedUrl $parsedUrl): string
    {
        $packageName = $this->generatePackageName($parsedUrl);
        $this->registerRepository($parsedUrl, $packageName);
        
        $this->io->writeError(
            sprintf('<info>Resolved package: %s from %s</info>', 
                $packageName, 
                $parsedUrl->getRepositoryIdentifier()
            ),
            true,
            IOInterface::VERBOSE
        );
        
        return $packageName;
    }

    /**
     * Generate a package name from the parsed URL.
     */
    private function generatePackageName(ParsedUrl $parsedUrl): string
    {
        $baseName = $parsedUrl->getSuggestedPackageName();
        
        // Check for conflicts and resolve them
        return $this->resolveNamingConflicts($baseName, $parsedUrl);
    }

    /**
     * Register a VCS repository with Composer.
     */
    private function registerRepository(ParsedUrl $parsedUrl, string $packageName): void
    {
        $config = [
            'type' => 'vcs',
            'url' => $parsedUrl->getGitUrl(),
            'reference' => $parsedUrl->reference,
        ];

        // Add subdirectory configuration if needed
        if ($parsedUrl->hasSubdirectory()) {
            $config['subdirectory'] = $parsedUrl->subdirectory;
        }

        try {
            $repository = $this->repositoryManager->createRepository('vcs', $config);
            $this->repositoryManager->prependRepository($repository);
            
            $this->io->writeError(
                sprintf('<info>Registered VCS repository: %s</info>', $parsedUrl->getGitUrl()),
                true,
                IOInterface::VERBOSE
            );
        } catch (\Exception $e) {
            throw new GitInstallException(
                sprintf('Failed to register repository: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Resolve naming conflicts by appending suffixes or hashes.
     */
    private function resolveNamingConflicts(string $baseName, ParsedUrl $parsedUrl): string
    {
        // For now, use the base name - in a full implementation, we would
        // check for existing packages and resolve conflicts
        $resolvedName = $baseName;
        
        // Add host prefix if not from major providers
        $majorProviders = ['github.com', 'gitlab.com', 'bitbucket.org'];
        if (!in_array($parsedUrl->host, $majorProviders, true)) {
            $hostPrefix = str_replace('.', '-', $parsedUrl->host);
            $resolvedName = sprintf('%s/%s-%s', 
                $parsedUrl->owner, 
                $hostPrefix, 
                $parsedUrl->repository
            );
            
            if ($parsedUrl->reference !== 'main' && $parsedUrl->reference !== 'master') {
                $resolvedName .= '-' . $parsedUrl->reference;
            }
        }
        
        return strtolower($resolvedName);
    }

    /**
     * Get the repository manager instance.
     */
    public function getRepositoryManager(): RepositoryManager
    {
        return $this->repositoryManager;
    }
}