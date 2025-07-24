<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Exception;

/**
 * Exception thrown when a git+ URL cannot be parsed or is invalid.
 */
class InvalidUrlException extends GitInstallException
{
    public function __construct(string $url, string $reason = '', int $code = 0, ?\Exception $previous = null)
    {
        $message = sprintf('Invalid git+ URL: %s', $url);
        if ($reason) {
            $message .= sprintf(' (%s)', $reason);
        }
        
        parent::__construct($message, $code, $previous);
    }
}