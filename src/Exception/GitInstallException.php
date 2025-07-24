<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Exception;

use Exception;

/**
 * Base exception class for all Git Install Plugin related errors.
 */
class GitInstallException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}