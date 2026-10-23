<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\Currency;
use App\Enums\TenderStatus;
use App\Models\Activity;
use App\Models\Tender;
use App\Models\TenderDocument;
use Tests\DatabaseTestCase;

final class TenderModelTest extends DatabaseTestCase
{
    public function test_a_tender_belongs_to_an_activity_and_has_documents(): void
    {
        $activity = $this->createActivity();
        $tender = $this->createTender($activity);

        TenderDocument::query()->create([
            'tender_id' => $tender->id,
            'title' => 'Pliego de condiciones',
            'description' => 'Documento principal',
            'file_path' => 'tenders/1/abc.pdf',
            'original_name' => 'pliego.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $fresh = Tender::query()->with(['activity', 'documents'])->findOrFail($tender->id);

        $this->assertSame('Computadores portátiles', $fresh->activity->product);
        $this->assertCount(1, $fresh->documents);
        $this->assertNotNull($fresh->documents->first()?->created_at);
    }

    public function test_it_casts_currency_and_formats_schedule_values(): void
    {
        $tender = $this->createTender($this->createActivity());
        $fresh = Tender::query()->findOrFail($tender->id);

        $this->assertSame(Currency::USD, $fresh->currency);
        $this->assertSame('1500000.50', $fresh->budget);
        $this->assertSame('2026-03-01', $fresh->start_date);
        $this->assertSame('08:00', $fresh->start_time);
        $this->assertSame('17:30', $fresh->closing_time);
    }

    public function test_current_status_is_computed_from_the_schedule(): void
    {
        $tender = $this->createTender($this->createActivity());

        $this->assertSame(TenderStatus::Closed, $tender->currentStatus(new \DateTimeImmutable('2026-04-01 00:00')));
        $this->assertSame(TenderStatus::Open, $tender->currentStatus(new \DateTimeImmutable('2026-03-05 10:00')));
    }

    private function createActivity(): Activity
    {
        return Activity::query()->create([
            'segment_code' => 43000000,
            'segment' => 'Difusión de Tecnologías de Información y Telecomunicaciones',
            'family_code' => 43210000,
            'family' => 'Equipo informático y accesorios',
            'class_code' => 43211500,
            'class_name' => 'Computadores',
            'product_code' => 43211503,
            'product' => 'Computadores portátiles',
        ]);
    }

    private function createTender(Activity $activity): Tender
    {
        return Tender::query()->create([
            'consecutive' => 'O-0001-26',
            'subject' => 'Compra de portátiles',
            'description' => 'Adquisición de equipos para el área comercial',
            'currency' => Currency::USD,
            'budget' => '1500000.50',
            'activity_id' => $activity->id,
            'start_date' => '2026-03-01',
            'start_time' => '08:00',
            'closing_date' => '2026-03-15',
            'closing_time' => '17:30',
            'status' => TenderStatus::Scheduled,
        ]);
    }
}
