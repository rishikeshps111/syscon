<?php

namespace App\Exports;

use App\Support\ShiftQuantity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PfFileExport extends DefaultValueBinder implements FromArray, WithCustomValueBinder, WithStyles, WithTitle, WithStrictNullComparison
{
    public function __construct(private array $report) {}

    public function title(): string
    {
        return 'PF File';
    }

    public function array(): array
    {
        return [$this->report['headings'], ...$this->report['rows']];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $last = count($this->report['rows']) + 1;
        $sheet->getStyle('A1:F'.$last)->getFont()->setName('Calibri')->setSize(11);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(42);
        foreach (['A' => 14.11, 'B' => 37.66, 'C' => 14, 'D' => 23.55, 'E' => 28, 'F' => 60.44] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle('A2:A'.$last)->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('C2:E'.$last)->getNumberFormat()->setFormatCode('#,##0');
        foreach ($this->report['rows'] as $index => $row) {
            $sheet->getStyle('F'.($index + 2))->getNumberFormat()->setFormatCode(ShiftQuantity::excelFormat($row[5]));
            if ($row[2] === null || $row[3] === null || $row[5] === null) {
                $sheet->getComment('D'.($index + 2))->getText()->createTextRun('Missing historical values are left blank; approved payroll has not been recalculated.');
            }
        }
        $sheet->freezePane('C2');
        $sheet->setAutoFilter('A1:F'.$last);
        $sheet->getPageSetup()->setOrientation('landscape')->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        return [];
    }
}
