<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when request input fails validation.
 *
 * Maps to HTTP 422 Unprocessable Entity.
 * Carries a structured errors array for the JSON response.
 */
class ValidationException extends RuntimeException
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param array<string, string[]> $errors  Field-level validation errors.
     */
    public function __construct(array $errors)
    {
        parent::__construct('Validation failed.', 422);
        $this->errors = $errors;
    }

    /**
     * Return the field-level error messages.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
