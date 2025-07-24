<?php

declare(strict_types=1);

namespace Neologik\ComposerGitInstaller\Exception;

use Exception;

/**
 * Base exception class for all Git Install Plugin related errors.
 */
class GitInstallException extends \Exception
{
    /**
     * GitInstallException constructor.
     *
     * @param string          $message  The exception message
     * @param int             $code     The exception code
     * @param \Exception|null $previous The previous exception for chaining
     */
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
