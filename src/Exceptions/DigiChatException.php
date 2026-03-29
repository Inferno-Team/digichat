<?php

namespace Digiworld\DigiChat\Exceptions;

use Exception;

/**
 * What: Package-specific exception that can carry structured DigiChat error details.
 * When: Thrown internally when validation or request preparation fails before a normal API response is returned.
 * Why: Attaching details to one exception type keeps internal failure handling easier to normalize.
 */
class DigiChatException extends Exception
{
    /**
     * What: Creates a DigiChat exception with an optional message, code, and detail payload.
     * When: Used anywhere the package needs to stop execution with a structured failure.
     * Why: Keeping details on the exception lets the manager convert local failures into consistent response arrays.
     */
    public function __construct(
        string $message = 'DigiChat API error',
        int $code = 0,
        protected array $details = []
    ) {
        parent::__construct($message, $code);
    }

    /**
     * What: Returns the structured detail payload attached to the exception.
     * When: Called during exception formatting or debugging.
     * Why: Consumers of the exception need access to the machine-readable failure context.
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
