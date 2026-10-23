<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Core\Http\UploadedFile;
use App\Models\Activity;

/**
 * Builders for tender related test data.
 */
trait CreatesTenders
{
    protected function activity(int $productCode = 43211503, string $product = 'Computadores portátiles'): Activity
    {
        return Activity::query()->firstOrCreate(['product_code' => $productCode], [
            'segment_code' => 43000000,
            'segment' => 'Tecnologías de la información',
            'family_code' => 43210000,
            'family' => 'Equipo informático',
            'class_code' => 43211500,
            'class_name' => 'Computadores',
            'product' => $product,
        ]);
    }

    /**
     * Valid payload for creating a tender.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function tenderPayload(array $overrides = []): array
    {
        $activityId = $overrides['activity_id'] ?? $this->activity()->id;

        return array_merge([
            'subject' => 'Compra de portátiles',
            'description' => 'Adquisición de 50 equipos portátiles para el área comercial.',
            'currency' => 'COP',
            'budget' => '150000000.50',
            'activity_id' => (string) $activityId,
            'start_date' => '2026-11-01',
            'start_time' => '08:00',
            'closing_date' => '2026-11-15',
            'closing_time' => '17:30',
        ], $overrides);
    }

    protected function pdfUpload(string $name = 'pliego.pdf'): UploadedFile
    {
        return $this->upload("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n", $name, 'application/pdf');
    }

    protected function zipUpload(string $name = 'anexos.zip'): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('anexo.txt', 'contenido');
        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', (int) filesize($path), UPLOAD_ERR_OK);
    }

    protected function upload(string $contents, string $name, string $clientMime): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, $clientMime, strlen($contents), UPLOAD_ERR_OK);
    }
}
