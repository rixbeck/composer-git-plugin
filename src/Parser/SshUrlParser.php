<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Parser;

use Neologik\ComposerGitInstaller\Exception\InvalidUrlException;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Parser for SSH git+ URLs.
 * Handles formats like:
 * - git+ssh://git@github.com/owner/repo@branch
 * - git+git@github.com:owner/repo@branch.
 */
class SshUrlParser extends AbstractUrlParser
{
    /**
     * Check if this parser can handle the given URL format.
     */
    public function canHandle(string $url): bool
    {
        return str_starts_with($url, 'git+ssh://')
               || (str_starts_with($url, 'git+') && str_contains($url, '@') && !str_contains($url, 'git+https'));
    }

    /**
     * Parse SSH git+ URL.
     */
    protected function doParse(string $url): ParsedUrl
    {
        $originalUrl = $url;
        $subdirectory = $this->extractSubdirectory($url);
        $cleanUrl = $this->cleanUrl($url);

        if (str_starts_with($cleanUrl, 'ssh://')) {
            return $this->parseSshProtocolUrl($originalUrl, $cleanUrl, $subdirectory);
        }

        return $this->parseSshShorthandUrl($originalUrl, $cleanUrl, $subdirectory);
    }

    /**
     * Parse SSH protocol URL: ssh://git@host/owner/repo@branch.
     */
    private function parseSshProtocolUrl(string $originalUrl, string $cleanUrl, ?string $subdirectory): ParsedUrl
    {
        // Remove ssh:// prefix
        $urlParts = mb_substr($cleanUrl, 6);

        // Split by @ to separate URL from reference
        $atPositions = [];
        $offset = 0;
        while (($pos = mb_strpos($urlParts, '@', $offset)) !== false) {
            $atPositions[] = $pos;
            $offset = $pos + 1;
        }

        if (count($atPositions) < 2) {
            throw new InvalidUrlException($originalUrl, 'Invalid SSH URL format');
        }

        // Last @ separates URL from reference
        $lastAtPos = end($atPositions);
        $reference = mb_substr($urlParts, $lastAtPos + 1);
        $urlWithoutRef = mb_substr($urlParts, 0, $lastAtPos);

        if (empty($reference)) {
            throw new InvalidUrlException($originalUrl, 'Empty reference (branch/tag/commit)');
        }

        // Parse user@host/path format
        $firstAtPos = $atPositions[0];
        $user = mb_substr($urlWithoutRef, 0, $firstAtPos);
        $hostAndPath = mb_substr($urlWithoutRef, $firstAtPos + 1);

        $pathParts = explode('/', $hostAndPath);
        if (count($pathParts) < 3) {
            throw new InvalidUrlException($originalUrl, 'Invalid SSH URL format, expected host/owner/repo');
        }

        $host = $pathParts[0];
        $owner = $pathParts[1];
        $repository = $pathParts[2];

        // Remove .git suffix if present
        if (str_ends_with($repository, '.git')) {
            $repository = mb_substr($repository, 0, -4);
        }

        [$reference, $referenceType] = $this->extractReference($reference);

        return new ParsedUrl(
            originalUrl: $originalUrl,
            scheme: 'ssh',
            host: $host,
            owner: $owner,
            repository: $repository,
            reference: $reference,
            referenceType: $referenceType,
            subdirectory: $subdirectory,
        );
    }

    /**
     * Parse SSH shorthand URL: git@host:owner/repo@branch.
     */
    private function parseSshShorthandUrl(string $originalUrl, string $cleanUrl, ?string $subdirectory): ParsedUrl
    {
        // Find all @ positions
        $atPositions = [];
        $offset = 0;
        while (($pos = mb_strpos($cleanUrl, '@', $offset)) !== false) {
            $atPositions[] = $pos;
            $offset = $pos + 1;
        }

        if (count($atPositions) < 2 || !str_contains($cleanUrl, ':')) {
            throw new InvalidUrlException($originalUrl, 'Invalid SSH shorthand URL format');
        }

        // Last @ separates URL from reference
        $lastAtPos = end($atPositions);
        $reference = mb_substr($cleanUrl, $lastAtPos + 1);
        $urlWithoutRef = mb_substr($cleanUrl, 0, $lastAtPos);

        if (empty($reference)) {
            throw new InvalidUrlException($originalUrl, 'Empty reference (branch/tag/commit)');
        }

        // Parse user@host:path format
        $firstAtPos = $atPositions[0];
        $user = mb_substr($urlWithoutRef, 0, $firstAtPos);
        $hostAndPath = mb_substr($urlWithoutRef, $firstAtPos + 1);

        $colonPos = mb_strpos($hostAndPath, ':');
        if (false === $colonPos) {
            throw new InvalidUrlException($originalUrl, 'Missing colon in SSH shorthand URL');
        }

        $host = mb_substr($hostAndPath, 0, $colonPos);
        $path = mb_substr($hostAndPath, $colonPos + 1);

        $pathParts = explode('/', $path);
        if (count($pathParts) < 2) {
            throw new InvalidUrlException($originalUrl, 'Invalid path format, expected owner/repo');
        }

        $owner = $pathParts[0];
        $repository = $pathParts[1];

        // Remove .git suffix if present
        if (str_ends_with($repository, '.git')) {
            $repository = mb_substr($repository, 0, -4);
        }

        [$reference, $referenceType] = $this->extractReference($reference);

        return new ParsedUrl(
            originalUrl: $originalUrl,
            scheme: 'ssh',
            host: $host,
            owner: $owner,
            repository: $repository,
            reference: $reference,
            referenceType: $referenceType,
            subdirectory: $subdirectory,
        );
    }
}
