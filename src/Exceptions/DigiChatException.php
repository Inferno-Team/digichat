<?php

namespace Digiworld\DigiChat\Exceptions;

use Exception;

class DigiChatException extends Exception
{
    /**
     * Create a new exception instance with API error details
     *
     * @param string $message
     * @param int $code
     * @param array $details
     */
    public function __construct(
        string $message = 'DigiChat API error',
        int $code = 0,
        protected array $details = []
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Get the exception details
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
