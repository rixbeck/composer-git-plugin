<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Parser;

use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Interface for URL parsers in the Chain of Responsibility pattern.
 */
interface UrlParserInterface
{
    /**
     * Set the next parser in the chain.
     */
    public function setNext(UrlParserInterface $parser): UrlParserInterface;

    /**
     * Attempt to parse the given URL.
     * Returns ParsedUrl if successful, null if cannot handle this URL format.
     */
    public function parse(string $url): ?ParsedUrl;

    /**
     * Check if this parser can handle the given URL format.
     */
    public function canHandle(string $url): bool;
}