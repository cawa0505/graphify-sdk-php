<?php

declare(strict_types=1);

namespace Graphify\Sdk\Exception;

/**
 * Thrown when there is a JSON-RPC protocol error.
 */
class ProtocolException extends GraphifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("[Protocol] {$message}", $code, $previous);
    }
}
