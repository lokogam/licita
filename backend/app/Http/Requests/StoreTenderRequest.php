<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Http\Request;
use App\Enums\Currency;
use App\Models\Activity;
use App\Validation\Rules\Exists;
use App\Validation\Validator;

/**
 * Validation for creating a tender: basic information + schedule.
 */
class StoreTenderRequest extends FormRequest
{
    protected function rules(Request $request): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:400'],
            'currency' => ['required', 'in:' . implode(',', Currency::values())],
            'budget' => ['required', 'decimal:2', 'gt:0', 'max_value:9999999999999.99'],
            'activity_id' => [
                'required',
                'integer',
                new Exists(
                    fn (mixed $id): bool => Activity::query()->whereKey((int) $id)->exists(),
                    'La actividad seleccionada no existe.',
                ),
            ],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'closing_date' => ['required', 'date_format:Y-m-d'],
            'closing_time' => ['required', 'date_format:H:i'],
        ];
    }

    protected function attributes(): array
    {
        return [
            'subject' => 'objeto',
            'description' => 'descripción',
            'currency' => 'moneda',
            'budget' => 'presupuesto',
            'activity_id' => 'actividad',
            'start_date' => 'fecha de inicio',
            'start_time' => 'hora de inicio',
            'closing_date' => 'fecha de cierre',
            'closing_time' => 'hora de cierre',
        ];
    }

    /**
     * The closing moment must be after the start moment.
     */
    protected function after(Validator $validator, array $data, Request $request): void
    {
        if ($data['closing_date'] < $data['start_date']) {
            $validator->addError('closing_date', 'La fecha de cierre no puede ser menor a la fecha de inicio.');

            return;
        }

        if ($data['closing_date'] === $data['start_date'] && $data['closing_time'] <= $data['start_time']) {
            $validator->addError(
                'closing_time',
                'La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.',
            );
        }
    }
}
