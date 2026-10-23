<?php

declare(strict_types=1);

namespace App\Validation;

use App\Validation\Rules\ArrayType;
use App\Validation\Rules\DateFormat;
use App\Validation\Rules\Decimal;
use App\Validation\Rules\File;
use App\Validation\Rules\GreaterThan;
use App\Validation\Rules\In;
use App\Validation\Rules\IntegerType;
use App\Validation\Rules\Max;
use App\Validation\Rules\MaxKilobytes;
use App\Validation\Rules\MaxValue;
use App\Validation\Rules\Mimes;
use App\Validation\Rules\MinItems;
use App\Validation\Rules\Required;
use App\Validation\Rules\StringType;
use InvalidArgumentException;

/**
 * Validates an input array against per-field rules.
 *
 * Rules can be strings ("required", "max:150", "in:COP,USD") or Rule objects.
 * Keys may use one wildcard level ("documents.*.title") to validate each item of a list.
 * Validation stops at the first failing rule of each field.
 */
final class Validator
{
    /** @var array<string, list<string>>|null */
    private ?array $errors = null;

    /** @var list<callable(Validator, array<string, mixed>): void> */
    private array $afterHooks = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, list<string|Rule>> $rules
     * @param array<string, string> $attributes Human readable field labels.
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $attributes = [],
    ) {
    }

    /**
     * Registers a callback executed after field rules pass (cross-field checks).
     *
     * @param callable(Validator, array<string, mixed>): void $hook
     */
    public function after(callable $hook): self
    {
        $this->afterHooks[] = $hook;

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors() !== [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        if ($this->errors === null) {
            $this->run();
        }

        return $this->errors ?? [];
    }

    public function addError(string $field, string $message): void
    {
        $this->errors ??= [];
        $this->errors[$field][] = $message;
    }

    /**
     * Returns only the top-level fields that have rules.
     *
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        $fields = array_unique(array_map(
            fn (string $key): string => explode('.', $key)[0],
            array_keys($this->rules),
        ));

        return array_intersect_key($this->data, array_flip($fields));
    }

    private function run(): void
    {
        $this->errors = [];

        foreach ($this->rules as $key => $rules) {
            foreach ($this->expand($key) as $field) {
                $this->validateField($field, $this->labelFor($key), $rules);
            }
        }

        if ($this->errors === []) {
            foreach ($this->afterHooks as $hook) {
                $hook($this, $this->data);
            }
        }
    }

    /**
     * @param list<string|Rule> $rules
     */
    private function validateField(string $field, string $label, array $rules): void
    {
        $value = $this->get($field);
        $required = in_array('required', $rules, true);

        if (!$required && ($value === null || $value === '')) {
            return;
        }

        foreach ($rules as $rule) {
            $message = $this->resolve($rule)->validate($value, $this->data);
            if ($message !== null) {
                $this->addError($field, str_replace(':attribute', $label, $message));

                return;
            }
        }
    }

    /**
     * Expands "documents.*.title" into "documents.0.title", "documents.1.title"...
     *
     * @return list<string>
     */
    private function expand(string $key): array
    {
        if (!str_contains($key, '.*')) {
            return [$key];
        }

        [$parent, $child] = explode('.*', $key, 2);
        $items = $this->get($parent);

        if (!is_array($items)) {
            return [];
        }

        return array_map(fn (int|string $index): string => $parent . '.' . $index . $child, array_keys($items));
    }

    private function get(string $path): mixed
    {
        $value = $this->data;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private function labelFor(string $key): string
    {
        return $this->attributes[$key] ?? str_replace('_', ' ', (string) preg_replace('/\.\*?\.?/', ' ', $key));
    }

    private function resolve(string|Rule $rule): Rule
    {
        if ($rule instanceof Rule) {
            return $rule;
        }

        [$name, $argument] = array_pad(explode(':', $rule, 2), 2, '');

        return match ($name) {
            'required' => new Required(),
            'string' => new StringType(),
            'integer' => new IntegerType(),
            'array' => new ArrayType(),
            'max' => new Max((int) $argument),
            'in' => new In(explode(',', $argument)),
            'decimal' => new Decimal((int) $argument),
            'gt' => new GreaterThan($argument),
            'max_value' => new MaxValue($argument),
            'date_format' => new DateFormat($argument),
            'min_items' => new MinItems((int) $argument),
            'file' => new File(),
            'mimes' => new Mimes(explode(',', $argument)),
            'max_kb' => new MaxKilobytes((int) $argument),
            default => throw new InvalidArgumentException("Unknown validation rule [{$name}]."),
        };
    }
}
