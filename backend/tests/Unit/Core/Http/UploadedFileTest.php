<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Http;

use App\Core\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

final class UploadedFileTest extends TestCase
{
    public function test_moving_the_file_removes_the_temporary_copy(): void
    {
        $file = $this->pdf();
        $destination = sys_get_temp_dir() . '/uploaded-' . uniqid() . '/doc.pdf';

        $file->moveTo($destination);

        $this->assertFileExists($destination);
        $this->assertFileDoesNotExist($file->path());
    }

    public function test_detected_mime_type_survives_the_move(): void
    {
        $file = $this->pdf();

        $file->moveTo(sys_get_temp_dir() . '/uploaded-' . uniqid() . '/doc.pdf');

        $this->assertSame('application/pdf', $file->detectedMimeType());
    }

    public function test_it_strips_directories_from_the_client_name(): void
    {
        $file = new UploadedFile('/tmp/x', '..\\..\\evil/Pliego.PDF', 'application/pdf', 1, UPLOAD_ERR_OK);

        $this->assertSame('Pliego.PDF', $file->originalName());
        $this->assertSame('pdf', $file->extension());
    }

    private function pdf(): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

        return new UploadedFile($path, 'doc.pdf', 'application/pdf', (int) filesize($path), UPLOAD_ERR_OK);
    }
}
