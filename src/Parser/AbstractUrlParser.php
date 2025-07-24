<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Parser;

use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Abstract base class for URL parsers implementing Chain of Responsibility pattern.
 */
abstract class AbstractUrlParser implements UrlParserInterface
{
    private ?UrlParserInterface $nextParser = null;

    /**
     * Set the next parser in the chain.
     */
    public function setNext(UrlParserInterface $parser): UrlParserInterface
    {
        $this->nextParser = $parser;
        return $parser;
    }

    /**
     * Attempt to parse the given URL.
     * If this parser cannot handle it, delegate to the next parser in the chain.
     */
    public function parse(string $url): ?ParsedUrl
    {
        if ($this->canHandle($url)) {
            return $this->doParse($url);
        }

        if ($this->nextParser !== null) {
            return $this->nextParser->parse($url);
        }

        return null;
    }

    /**
     * Abstract method for specific parsing logic.
     * Each concrete parser implements this method.
     */
    abstract protected function doParse(string $url): ParsedUrl;

    /**
     * Extract reference (branch/tag/commit) and determine its type.
     */
    protected function extractReference(string $reference): array
    {
        // Default to branch type
        $referenceType = 'branch';
        
        // Check if it looks like a commit hash (40 character hex string)
        if (preg_match('/^[a-f0-9]{40}$/i', $reference)) {
            $referenceType = 'commit';
        }
        // Check if it looks like a tag (starts with v followed by semantic version)
        elseif (preg_match('/^v?\d+\.\d+(\.\d+)?/', $reference)) {
            $referenceType = 'tag';
        }

        return [$reference, $referenceType];
    }

    /**
     * Extract subdirectory from URL fragment (after #).
     */
    protected function extractSubdirectory(string $url): ?string
    {
        $fragmentPos = strpos($url, '#');
        if ($fragmentPos === false) {
            return null;
        }

        $subdirectory = substr($url, $fragmentPos + 1);
        return trim($subdirectory, '/') ?: null;
    }

    /**
     * Remove git+ prefix and fragment from URL for processing.
     */
    protected function cleanUrl(string $url): string
    {
        // Remove git+ prefix
        $url = preg_replace('/^git\+/', '', $url);
        
        // Remove fragment (subdirectory)
        $fragmentPos = strpos($url, '#');
        if ($fragmentPos !== false) {
            $url = substr($url, 0, $fragmentPos);
        }

        return $url;
    }
}