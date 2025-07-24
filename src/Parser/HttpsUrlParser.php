<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Parser;

use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Parser for HTTPS git+ URLs.
 * Handles formats like: git+https://github.com/owner/repo@branch.
 */
class HttpsUrlParser extends AbstractUrlParser
{
    /**
     * Check if this parser can handle the given URL format.
     */
    public function canHandle(string $url): bool
    {
        return str_starts_with($url, 'git+https://');
    }

    /**
     * Parse HTTPS git+ URL.
     */
    protected function doParse(string $url): ParsedUrl
    {
        $originalUrl = $url;
        $subdirectory = $this->extractSubdirectory($url);
        $cleanUrl = $this->cleanUrl($url);

        // Remove https:// prefix
        $urlParts = mb_substr($cleanUrl, 8);

        // Split by @ to separate URL from reference
        $atPos = mb_strrpos($urlParts, '@');
        if (false === $atPos) {
            throw new InvalidUrlException($originalUrl, 'Missing @ separator for branch/tag/commit');
        }

        $reference = mb_substr($urlParts, $atPos + 1);
        $urlWithoutRef = mb_substr($urlParts, 0, $atPos);

        if (empty($reference)) {
            throw new InvalidUrlException($originalUrl, 'Empty reference (branch/tag/commit)');
        }

        // Parse the URL path: host/owner/repo
        $pathParts = explode('/', $urlWithoutRef);
        if (count($pathParts) < 3) {
            throw new InvalidUrlException($originalUrl, 'Invalid URL format, expected host/owner/repo');
        }

        $host = $pathParts[0];
        $owner = $pathParts[1];
        $repository = $pathParts[2];

        // Remove .git suffix if present
        if (str_ends_with($repository, '.git')) {
            $repository = mb_substr($repository, 0, -4);
        }

        // Extract port if present in host
        $port = null;
        if (str_contains($host, ':')) {
            [$host, $portStr] = explode(':', $host, 2);
            $port = (int) $portStr;
        }

        [$reference, $referenceType] = $this->extractReference($reference);

        return new ParsedUrl(
            originalUrl: $originalUrl,
            scheme: 'https',
            host: $host,
            owner: $owner,
            repository: $repository,
            reference: $reference,
            referenceType: $referenceType,
            subdirectory: $subdirectory,
            port: $port,
        );
    }
}
