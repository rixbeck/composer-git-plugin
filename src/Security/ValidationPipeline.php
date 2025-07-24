<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Security;

use Composer\Composer;
use Composer\IO\IOInterface;
use Neologik\ComposerGitInstaller\Model\ParsedUrl;
use Neologik\ComposerGitInstaller\Exception\SecurityException;

/**
 * Security validation pipeline that applies multiple validation layers.
 */
class ValidationPipeline
{
    private array $validators = [];

    public function __construct(
        private readonly Composer $composer,
        private readonly IOInterface $io
    ) {
        $this->initializeValidators();
    }

    /**
     * Validate a parsed URL through the security pipeline.
     */
    public function validate(ParsedUrl $parsedUrl): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($parsedUrl);
        }

        $this->io->writeError(
            sprintf('<info>Security validation passed for: %s</info>', $parsedUrl->getRepositoryIdentifier()),
            true,
            IOInterface::VERBOSE
        );
    }

    /**
     * Initialize the validation pipeline with default validators.
     */
    private function initializeValidators(): void
    {
        $this->validators = [
            new UrlFormatValidator(),
            new HostWhitelistValidator($this->composer),
            new InputSanitizationValidator(),
            new RateLimitValidator($this->io),
        ];
    }

    /**
     * Add a custom validator to the pipeline.
     */
    public function addValidator(ValidatorInterface $validator): void
    {
        $this->validators[] = $validator;
    }

    /**
     * Get all registered validators.
     */
    public function getValidators(): array
    {
        return $this->validators;
    }
}