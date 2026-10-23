<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Validation\Rule;
use App\Validation\Validator;

/**
 * Base class for endpoint-specific validation (rules, labels and cross-field checks).
 */
abstract class FormRequest
{
    /**
     * @return array<string, list<string|Rule>>
     */
    abstract protected function rules(Request $request): array;

    /**
     * Human readable labels used in error messages.
     *
     * @return array<string, string>
     */
    protected function attributes(): array
    {
        return [];
    }

    /**
     * Cross-field validation executed only when every field rule passed.
     *
     * @param array<string, mixed> $data
     */
    protected function after(Validator $validator, array $data, Request $request): void
    {
    }

    /**
     * Validates the raw request and returns the whitelisted data with strings trimmed.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validate(Request $request): array
    {
        $validator = new Validator($request->all(), $this->rules($request), $this->attributes());
        $validator->after(fn (Validator $v, array $input) => $this->after($v, $input, $request));

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $this->normalize($validator->validated());
    }

    /**
     * Trims every string in the payload.
     *
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function normalize(array $data): array
    {
        return array_map(
            fn (mixed $value): mixed => match (true) {
                is_string($value) => trim($value),
                is_array($value) => $this->normalize($value),
                default => $value,
            },
            $data,
        );
    }
}
