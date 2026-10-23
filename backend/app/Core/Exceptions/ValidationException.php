<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * 422: the request payload did not pass validation.
 */
final class ValidationException extends HttpException
{
    /**
     * @param array<string, list<string>> $errors Messages indexed by field name.
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Los datos enviados no son válidos.',
    ) {
        parent::__construct(422, $message);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
