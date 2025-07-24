<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Security;

use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\SecurityException;

/**
 * Validates URL format and structure for security compliance.
 */
class UrlFormatValidator implements ValidatorInterface
{
    private const MAX_URL_LENGTH = 2048;
    private const MAX_COMPONENT_LENGTH = 255;
    private const ALLOWED_SCHEMES = ['https', 'ssh'];
    private const BLOCKED_CHARACTERS = ['<', '>', '"', '\'', '&', '\0', '\n', '\r', '\t'];

    /**
     * Validate URL format and structure.
     */
    public function validate(ParsedUrl $parsedUrl): void
    {
        $this->validateUrlLength($parsedUrl);
        $this->validateScheme($parsedUrl);
        $this->validateHost($parsedUrl);
        $this->validateComponents($parsedUrl);
        $this->validateReference($parsedUrl);
        $this->validateSubdirectory($parsedUrl);
    }

    /**
     * Get the name of this validator.
     */
    public function getName(): string
    {
        return 'URL Format Validator';
    }

    /**
     * Validate total URL length.
     */
    private function validateUrlLength(ParsedUrl $parsedUrl): void
    {
        if (strlen($parsedUrl->originalUrl) > self::MAX_URL_LENGTH) {
            throw new SecurityException(
                sprintf('URL exceeds maximum length of %d characters', self::MAX_URL_LENGTH),
                'URL_LENGTH'
            );
        }
    }

    /**
     * Validate URL scheme.
     */
    private function validateScheme(ParsedUrl $parsedUrl): void
    {
        if (!in_array($parsedUrl->scheme, self::ALLOWED_SCHEMES, true)) {
            throw new SecurityException(
                sprintf('Scheme "%s" is not allowed. Allowed schemes: %s', 
                    $parsedUrl->scheme, 
                    implode(', ', self::ALLOWED_SCHEMES)
                ),
                'INVALID_SCHEME'
            );
        }
    }

    /**
     * Validate host format.
     */
    private function validateHost(ParsedUrl $parsedUrl): void
    {
        $host = $parsedUrl->host;
        
        if (strlen($host) > self::MAX_COMPONENT_LENGTH) {
            throw new SecurityException(
                sprintf('Host exceeds maximum length of %d characters', self::MAX_COMPONENT_LENGTH),
                'HOST_LENGTH'
            );
        }

        // Check for IP addresses (should use domain names)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            throw new SecurityException(
                'IP addresses are not allowed, please use domain names',
                'IP_ADDRESS_BLOCKED'
            );
        }

        // Basic domain validation
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $host)) {
            throw new SecurityException(
                'Invalid host format',
                'INVALID_HOST'
            );
        }
    }

    /**
     * Validate repository components (owner, repository).
     */
    private function validateComponents(ParsedUrl $parsedUrl): void
    {
        $components = [
            'owner' => $parsedUrl->owner,
            'repository' => $parsedUrl->repository,
        ];

        foreach ($components as $name => $value) {
            if (strlen($value) > self::MAX_COMPONENT_LENGTH) {
                throw new SecurityException(
                    sprintf('%s exceeds maximum length of %d characters', ucfirst($name), self::MAX_COMPONENT_LENGTH),
                    'COMPONENT_LENGTH'
                );
            }

            if (empty($value)) {
                throw new SecurityException(
                    sprintf('%s cannot be empty', ucfirst($name)),
                    'EMPTY_COMPONENT'
                );
            }

            $this->validateCharacters($value, $name);
        }
    }

    /**
     * Validate reference (branch/tag/commit).
     */
    private function validateReference(ParsedUrl $parsedUrl): void
    {
        $reference = $parsedUrl->reference;
        
        if (strlen($reference) > self::MAX_COMPONENT_LENGTH) {
            throw new SecurityException(
                sprintf('Reference exceeds maximum length of %d characters', self::MAX_COMPONENT_LENGTH),
                'REFERENCE_LENGTH'
            );
        }

        if (empty($reference)) {
            throw new SecurityException(
                'Reference cannot be empty',
                'EMPTY_REFERENCE'
            );
        }

        $this->validateCharacters($reference, 'reference');
    }

    /**
     * Validate subdirectory if present.
     */
    private function validateSubdirectory(ParsedUrl $parsedUrl): void
    {
        $subdirectory = $parsedUrl->subdirectory;
        
        if ($subdirectory === null) {
            return;
        }

        if (strlen($subdirectory) > self::MAX_COMPONENT_LENGTH) {
            throw new SecurityException(
                sprintf('Subdirectory exceeds maximum length of %d characters', self::MAX_COMPONENT_LENGTH),
                'SUBDIRECTORY_LENGTH'
            );
        }

        // Check for path traversal attempts
        if (str_contains($subdirectory, '..') || str_contains($subdirectory, './')) {
            throw new SecurityException(
                'Path traversal detected in subdirectory',
                'PATH_TRAVERSAL'
            );
        }

        $this->validateCharacters($subdirectory, 'subdirectory');
    }

    /**
     * Validate characters in a component.
     */
    private function validateCharacters(string $value, string $componentName): void
    {
        foreach (self::BLOCKED_CHARACTERS as $char) {
            if (str_contains($value, $char)) {
                throw new SecurityException(
                    sprintf('Invalid character detected in %s: %s', $componentName, json_encode($char)),
                    'INVALID_CHARACTER'
                );
            }
        }
    }
}