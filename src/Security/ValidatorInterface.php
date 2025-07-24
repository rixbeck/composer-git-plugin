<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Security;

use Neologik\ComposerGitInstaller\Model\ParsedUrl;

/**
 * Interface for security validators in the validation pipeline.
 */
interface ValidatorInterface
{
    /**
     * Validate a parsed URL.
     * 
     * @throws SecurityException if validation fails
     */
    public function validate(ParsedUrl $parsedUrl): void;

    /**
     * Get the name of this validator for logging purposes.
     */
    public function getName(): string;
}