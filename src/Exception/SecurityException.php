<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Exception;

/**
 * Exception thrown when security validation fails.
 */
class SecurityException extends GitInstallException
{
    public function __construct(string $message, string $securityContext = '', int $code = 0, ?\Exception $previous = null)
    {
        if ($securityContext) {
            $message = sprintf('[%s] %s', $securityContext, $message);
        }
        
        parent::__construct($message, $code, $previous);
    }
}