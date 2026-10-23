<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Unspsc;

use App\Support\Unspsc\ActivitiesSqlWriter;
use App\Support\Unspsc\UnspscRowParser;
use PHPUnit\Framework\TestCase;

final class ActivitiesSqlWriterTest extends TestCase
{
    public function test_parser_maps_a_classifier_row_to_activity_columns(): void
    {
        $row = [10, 'Material Vivo', 1010, 'Animales vivos', 101015, 'Animales de granja', 10101501, ' Gatos ', null];

        $this->assertSame([
            'segment_code' => 10,
            'segment' => 'Material Vivo',
            'family_code' => 1010,
            'family' => 'Animales vivos',
            'class_code' => 101015,
            'class_name' => 'Animales de granja',
            'product_code' => 10101501,
            'product' => 'Gatos',
        ], (new UnspscRowParser())->parse($row));
    }

    public function test_parser_skips_titles_headers_and_blank_rows(): void
    {
        $parser = new UnspscRowParser();

        $this->assertNull($parser->parse(['#VALUE!', null, 'Una combinación de códigos...']));
        $this->assertNull($parser->parse(['Código segmento', 'Nombre Segmento', 'Código Familia']));
        $this->assertNull($parser->parse([null, null, null, null, null, null, null, null]));
        $this->assertNull($parser->parse([10, 'Segmento', 1010, 'Familia', 101015, 'Clase', 10101501, '']));
    }

    public function test_parser_truncates_names_to_column_length(): void
    {
        $long = str_repeat('á', 250);

        $parsed = (new UnspscRowParser())->parse([10, $long, 1010, 'F', 101015, 'C', 10101501, 'P']);

        $this->assertSame(200, mb_strlen((string) $parsed['segment']));
    }

    public function test_writer_generates_batched_and_escaped_inserts_without_duplicates(): void
    {
        $rows = [
            $this->activity(1, "Producto con 'comillas'"),
            $this->activity(2, 'Ruta C:\temp'),
            $this->activity(1, 'Duplicado'),
            $this->activity(3, 'Tercero'),
        ];

        $output = fopen('php://memory', 'w+');
        $count = (new ActivitiesSqlWriter(batchSize: 2))->write($rows, $output);
        rewind($output);
        $sql = (string) stream_get_contents($output);

        $this->assertSame(3, $count);
        $this->assertSame(2, substr_count($sql, 'INSERT INTO activities'));
        $this->assertStringContainsString("'Producto con ''comillas'''", $sql);
        $this->assertStringContainsString("'Ruta C:\\\\temp'", $sql);
        $this->assertStringNotContainsString('Duplicado', $sql);
        $this->assertStringStartsWith('SET NAMES utf8mb4;', $sql);
    }

    /**
     * @return array<string, int|string>
     */
    private function activity(int $productCode, string $product): array
    {
        return [
            'segment_code' => 10,
            'segment' => 'Segmento',
            'family_code' => 1010,
            'family' => 'Familia',
            'class_code' => 101015,
            'class_name' => 'Clase',
            'product_code' => $productCode,
            'product' => $product,
        ];
    }
}
