<?php

declare(strict_types=1);

namespace Graphify\Sdk\Exception;

/**
 * Thrown when there is an I/O or process management error with the MCP transport.
 */
class TransportException extends GraphifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("[Transport] {$message}", $code, $previous);
    }
}
