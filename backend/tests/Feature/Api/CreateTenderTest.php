<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tender;
use App\Support\Clock;
use App\Support\FrozenClock;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class CreateTenderTest extends DatabaseTestCase
{
    use CreatesTenders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-10-20 10:00:00'));
    }

    public function test_it_creates_a_tender_with_generated_consecutive_and_status(): void
    {
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload());

        $this->assertSame(201, $response->status());
        $data = $this->json($response)['data'];
        $this->assertSame('O-0001-26', $data['consecutive']);
        $this->assertSame('Compra de portátiles', $data['subject']);
        $this->assertSame('150000000.50', $data['budget']);
        $this->assertSame('08:00', $data['start_time']);
        $this->assertSame(['value' => 'scheduled', 'label' => 'Programada'], $data['status']);
        $this->assertSame('Computadores portátiles', $data['activity']['product']);
        $this->assertSame([], $data['documents']);

        $this->assertSame(1, Tender::query()->count());
    }

    public function test_consecutive_increments_for_each_new_tender(): void
    {
        $this->call('POST', '/api/tenders', $this->tenderPayload());
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload());

        $this->assertSame('O-0002-26', $this->json($response)['data']['consecutive']);
    }

    public function test_it_trims_text_fields_before_saving(): void
    {
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload(['subject' => '  Compra  ']));

        $this->assertSame('Compra', $this->json($response)['data']['subject']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'subject missing' => [['subject' => ''], 'subject', 'El campo objeto es obligatorio.'];
        yield 'subject only spaces' => [['subject' => '    '], 'subject', 'El campo objeto no puede contener solo espacios.'];
        yield 'subject too long' => [['subject' => str_repeat('a', 151)], 'subject', 'El campo objeto no debe superar 150 caracteres.'];
        yield 'description too long' => [['description' => str_repeat('a', 401)], 'description', 'El campo descripción no debe superar 400 caracteres.'];
        yield 'currency not allowed' => [['currency' => 'MXN'], 'currency', 'El campo moneda debe ser uno de: COP, USD, EUR.'];
        yield 'budget zero' => [['budget' => '0'], 'budget', 'El campo presupuesto debe ser mayor a 0.'];
        yield 'budget three decimals' => [['budget' => '10.123'], 'budget', 'El campo presupuesto debe ser un número con máximo 2 decimales (solo números y punto).'];
        yield 'budget letters' => [['budget' => '10a'], 'budget', 'El campo presupuesto debe ser un número con máximo 2 decimales (solo números y punto).'];
        yield 'activity missing in db' => [['activity_id' => '999999'], 'activity_id', 'La actividad seleccionada no existe.'];
        yield 'invalid start date' => [['start_date' => '2026-02-30'], 'start_date', 'El campo fecha de inicio debe ser una fecha válida con formato AAAA-MM-DD.'];
        yield 'invalid start time' => [['start_time' => '25:00'], 'start_time', 'El campo hora de inicio debe ser una hora válida con formato HH:mm (24 horas).'];
        yield 'closing date before start' => [['closing_date' => '2026-10-31'], 'closing_date', 'La fecha de cierre no puede ser menor a la fecha de inicio.'];
        yield 'same day closing before start time' => [
            ['closing_date' => '2026-11-01', 'closing_time' => '07:00'],
            'closing_time',
            'La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.',
        ];
        yield 'same day same time' => [
            ['closing_date' => '2026-11-01', 'closing_time' => '08:00'],
            'closing_time',
            'La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.',
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_data(array $overrides, string $field, string $message): void
    {
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload($overrides));

        $this->assertSame(422, $response->status());
        $this->assertSame([$message], $this->json($response)['errors'][$field] ?? null);
        $this->assertSame(0, Tender::query()->count());
    }

    public function test_same_day_tender_with_later_closing_time_is_valid(): void
    {
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload([
            'closing_date' => '2026-11-01',
            'closing_time' => '08:01',
        ]));

        $this->assertSame(201, $response->status());
    }

    public function test_it_ignores_client_supplied_consecutive_and_status(): void
    {
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload([
            'consecutive' => 'HACK-1',
            'status' => 'closed',
        ]));

        $data = $this->json($response)['data'];
        $this->assertSame('O-0001-26', $data['consecutive']);
        $this->assertSame('scheduled', $data['status']['value']);
    }
}
