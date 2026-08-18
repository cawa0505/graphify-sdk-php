<?php

declare(strict_types=1);

namespace Graphify\Sdk\Exception;

/**
 * Base exception for all Graphify SDK errors.
 */
class GraphifyException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
