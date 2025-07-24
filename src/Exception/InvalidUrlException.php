<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Exception;

/**
 * Exception thrown when a git+ URL cannot be parsed or is invalid.
 */
class InvalidUrlException extends GitInstallException
{
    /**
     * InvalidUrlException constructor.
     *
     * @param string          $url      The invalid URL that caused the exception
     * @param string          $reason   Additional reason for the invalid URL
     * @param int             $code     The exception code
     * @param \Exception|null $previous The previous exception for chaining
     */
    public function __construct(string $url, string $reason = '', int $code = 0, ?\Exception $previous = null)
    {
        $message = sprintf('Invalid git+ URL: %s', $url);
        if ($reason) {
            $message .= sprintf(' (%s)', $reason);
        }

        parent::__construct($message, $code, $previous);
    }
}
