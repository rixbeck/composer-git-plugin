<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Exception;

/**
 * Exception thrown when security validation fails.
 */
class SecurityException extends GitInstallException
{
    /**
     * SecurityException constructor.
     *
     * @param string          $message  The exception message
     * @param string          $context  Security context for the exception
     * @param int             $code     The exception code
     * @param \Exception|null $previous The previous exception for chaining
     */
    public function __construct(string $message, string $context = '', int $code = 0, ?\Exception $previous = null)
    {
        if ($context) {
            $message = sprintf('[%s] %s', $context, $message);
        }

        parent::__construct($message, $code, $previous);
    }
}
