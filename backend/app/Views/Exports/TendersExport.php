<?php

declare(strict_types=1);

namespace App\Views\Exports;

use App\Models\Tender;
use App\Support\Clock;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel view of the tenders list (same columns as the list screen).
 */
final class TendersExport
{
    private const HEADERS = ['Consecutivo', 'Objeto', 'Descripción', 'Fecha de inicio', 'Fecha de cierre', 'Estado'];

    private const COLUMN_WIDTHS = ['A' => 14, 'B' => 40, 'C' => 70, 'D' => 18, 'E' => 18, 'F' => 14];

    public function __construct(private readonly Clock $clock)
    {
    }

    /**
     * Renders the workbook to a temporary file and returns its path.
     *
     * @param iterable<Tender> $tenders
     */
    public function render(iterable $tenders): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Licitaciones')->setCreator('Módulo de Licitaciones');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Licitaciones');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $now = $this->clock->now();
        $row = 2;
        foreach ($tenders as $tender) {
            $values = [
                $tender->consecutive,
                $tender->subject,
                $tender->description,
                "{$tender->start_date} {$tender->start_time}",
                "{$tender->closing_date} {$tender->closing_time}",
                $tender->currentStatus($now)->label(),
            ];

            foreach ($values as $index => $value) {
                // Explicit strings: user text starting with "=" is never evaluated as a formula.
                $sheet->setCellValueExplicit([$index + 1, $row], $value, DataType::TYPE_STRING);
            }
            $row++;
        }

        $this->style($spreadsheet, $row - 1);

        $path = (string) tempnam(sys_get_temp_dir(), 'tenders-export');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function style(Spreadsheet $spreadsheet, int $lastRow): void
    {
        $sheet = $spreadsheet->getActiveSheet();

        $header = $sheet->getStyle('A1:F1');
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('123C3F');

        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:F{$lastRow}");
    }
}
