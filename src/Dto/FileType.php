<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * File type classification in the Graphify knowledge graph.
 */
enum FileType: string
{
    case Code = 'code';
    case Document = 'document';
    case Paper = 'paper';
    case Image = 'image';
    case Rationale = 'rationale';
    case Concept = 'concept';

    /**
     * Create from a string value, case-insensitive.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower($value))
            ?? self::Code; // Default to Code for unknown values
    }
}
