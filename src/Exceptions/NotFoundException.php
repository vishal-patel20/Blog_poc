<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a requested resource cannot be found.
 *
 * Maps to HTTP 404 Not Found.
 */
class NotFoundException extends RuntimeException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message, 404);
    }
}
