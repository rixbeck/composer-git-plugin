<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Model;

/**
 * Value object representing a parsed git+ URL.
 */
class ParsedUrl
{
    public function __construct(
        public readonly string $originalUrl,
        public readonly string $scheme,
        public readonly string $host,
        public readonly string $owner,
        public readonly string $repository,
        public readonly string $reference,
        public readonly string $referenceType,
        public readonly ?string $subdirectory = null,
        public readonly ?int $port = null
    ) {
    }

    /**
     * Get the clean git URL (without git+ prefix).
     */
    public function getGitUrl(): string
    {
        $url = sprintf('%s://%s', $this->scheme, $this->host);
        
        if ($this->port !== null) {
            $url .= ':' . $this->port;
        }
        
        if ($this->scheme === 'ssh' && str_contains($this->originalUrl, '@')) {
            // SSH format: git@host:owner/repo
            $url = sprintf('%s@%s:%s/%s', 'git', $this->host, $this->owner, $this->repository);
        } else {
            // HTTPS format: https://host/owner/repo
            $url .= sprintf('/%s/%s', $this->owner, $this->repository);
        }
        
        return $url;
    }

    /**
     * Generate a suggested package name.
     */
    public function getSuggestedPackageName(): string
    {
        $name = sprintf('%s/%s', $this->owner, $this->repository);
        
        if ($this->reference !== 'main' && $this->reference !== 'master') {
            $name .= '-' . $this->reference;
        }
        
        return strtolower($name);
    }

    /**
     * Check if this URL points to a subdirectory within the repository.
     */
    public function hasSubdirectory(): bool
    {
        return $this->subdirectory !== null;
    }

    /**
     * Get the repository identifier (owner/repo).
     */
    public function getRepositoryIdentifier(): string
    {
        return sprintf('%s/%s', $this->owner, $this->repository);
    }
}