<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;
use DateTimeImmutable;

/**
 * The value must be a real date/time in an exact format (e.g. Y-m-d or H:i).
 */
final class DateFormat implements Rule
{
    private const LABELS = [
        'Y-m-d' => 'una fecha válida con formato AAAA-MM-DD',
        'H:i' => 'una hora válida con formato HH:mm (24 horas)',
    ];

    public function __construct(private readonly string $format)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (is_string($value)) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $this->format, $value);
            if ($parsed !== false && $parsed->format($this->format) === $value) {
                return null;
            }
        }

        $label = self::LABELS[$this->format] ?? "una fecha con formato {$this->format}";

        return "El campo :attribute debe ser {$label}.";
    }
}
