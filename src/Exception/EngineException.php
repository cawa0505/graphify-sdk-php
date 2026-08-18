<?php

declare(strict_types=1);

namespace Graphify\Sdk\Exception;

/**
 * Thrown when graphify-mcp returns an engine-level error.
 */
class EngineException extends GraphifyException
{
    /** @var array|null Raw error data from the response */
    public readonly ?array $errorData;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $errorData = null,
        ?\Throwable $previous = null,
    ) {
        $this->errorData = $errorData;
        parent::__construct("[Engine] {$message}", $code, $previous);
    }
}
