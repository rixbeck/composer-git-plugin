<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Security;

use Neologik\ComposerGitInstaller\Exception\SecurityException;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Validates input sanitization and prevents injection attacks.
 */
class InputSanitizationValidator implements ValidatorInterface
{
    /**
     * Validate input sanitization.
     */
    public function validate(ParsedUrl $parsedUrl): void
    {
        $this->checkForDangerousPatterns($parsedUrl->originalUrl, 'original URL');
        $this->checkForDangerousPatterns($parsedUrl->host, 'host');
        $this->checkForDangerousPatterns($parsedUrl->owner, 'owner');
        $this->checkForDangerousPatterns($parsedUrl->repository, 'repository');
        $this->checkForDangerousPatterns($parsedUrl->reference, 'reference');

        if (null !== $parsedUrl->subdirectory) {
            $this->checkForDangerousPatterns($parsedUrl->subdirectory, 'subdirectory');
        }
    }

    /**
     * Get the name of this validator.
     */
    public function getName(): string
    {
        return 'Input Sanitization Validator';
    }

    /**
     * Check for dangerous patterns in input.
     */
    private function checkForDangerousPatterns(string $input, string $fieldName): void
    {
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                throw new SecurityException(
                    sprintf('Dangerous pattern detected in %s: %s', $fieldName, $pattern),
                    'INJECTION_ATTEMPT',
                );
            }
        }
    }
    private const DANGEROUS_PATTERNS = [
        '/\$\{.*\}/',           // Variable substitution
        '/`.*`/',               // Command substitution
        '/\$\(.*\)/',           // Command substitution
        '/\|\s*\w/',            // Pipe commands
        '/;\s*\w/',             // Command chaining
        '/&&\s*\w/',            // Command chaining
        '/\|\|\s*\w/',          // Command chaining
        '/>\s*\//',             // File redirection
        '/<\s*\//',             // File redirection
        '/\bnull\b/i',          // Null bytes
        '/\x00/',               // Null bytes
    ];
}
