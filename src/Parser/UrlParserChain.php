<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Parser;

use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;

/**
 * Chain of Responsibility coordinator for URL parsing.
 * Manages the chain of URL parsers and handles parsing requests.
 */
class UrlParserChain
{
    private ?UrlParserInterface $firstParser = null;

    public function __construct()
    {
        $this->buildChain();
    }

    /**
     * Parse a git+ URL using the parser chain.
     */
    public function parse(string $url): ParsedUrl
    {
        if (!str_starts_with($url, 'git+')) {
            throw new InvalidUrlException($url, 'URL must start with git+ prefix');
        }

        if ($this->firstParser === null) {
            throw new InvalidUrlException($url, 'No parsers available');
        }

        try {
            $result = $this->firstParser->parse($url);
            if ($result === null) {
                throw new InvalidUrlException($url, 'No parser could handle this URL format');
            }
            return $result;
        } catch (InvalidUrlException $e) {
            if (str_contains($e->getMessage(), 'Invalid SSH shorthand URL format')) {
                throw new InvalidUrlException($url, 'No parser could handle this URL format');
            }
            throw $e;
        }
    }

    /**
     * Check if any parser in the chain can handle the given URL.
     */
    public function canParse(string $url): bool
    {
        if (!str_starts_with($url, 'git+')) {
            return false;
        }

        try {
            $this->parse($url);
            return true;
        } catch (InvalidUrlException) {
            return false;
        }
    }

    /**
     * Build the chain of parsers in order of preference.
     */
    private function buildChain(): void
    {
        // Create parsers in order of preference
        $httpsParser = new HttpsUrlParser();
        $sshParser = new SshUrlParser();
        
        // Build the chain: HTTPS -> SSH
        $this->firstParser = $httpsParser;
        $httpsParser->setNext($sshParser);
    }

    /**
     * Get all supported URL patterns for documentation/validation.
     */
    public function getSupportedPatterns(): array
    {
        return [
            'HTTPS' => [
                'git+https://github.com/owner/repo@branch',
                'git+https://gitlab.com/owner/repo@v1.0.0',
                'git+https://bitbucket.org/owner/repo@commit-hash',
                'git+https://github.com/owner/repo@branch#subdirectory'
            ],
            'SSH' => [
                'git+ssh://git@github.com/owner/repo@branch',
                'git+git@github.com:owner/repo@branch',
                'git+git@gitlab.com:owner/repo@v1.0.0#subdirectory'
            ]
        ];
    }
}