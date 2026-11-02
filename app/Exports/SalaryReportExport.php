<?php

namespace App\Exports;

use App\Support\WageRegister;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryReportExport extends DefaultValueBinder implements FromArray, WithStyles, WithCustomValueBinder, WithStrictNullComparison
{
    public function __construct(private array $report) {}

    public function array(): array
    {
        $groups = array_fill(0, 63, null);
        foreach (WageRegister::GROUPS as [$start, $length, $label]) {
            $groups[$start] = $label;
        }
        // An empty array is flattened away by Laravel Excel's appendRows pipeline.
        $blank = array_fill(0, 63, null);
        $rows = [[WageRegister::title($this->report)], $blank, $groups, WageRegister::headings(), ...$this->report['rows']];
        if ($this->report['warnings']) {
            $rows[] = $blank;
            $rows[] = ['Reconciliation notes (approved amounts have not been changed)'];
            foreach ($this->report['warnings'] as $warning) {
                $rows[] = [$warning];
            }
        }

        return $rows;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $last = max(4, 4 + count($this->report['rows']));
        $sheet->mergeCells('A1:BK1');
        $sheet->getStyle('A1:BK1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
        $sheet->getStyle('A1:BK4')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(20);
        $sheet->getStyle('A1:BK4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getRowDimension(3)->setRowHeight(30);
        $sheet->getRowDimension(4)->setRowHeight(64);
        foreach (WageRegister::GROUPS as [$start, $length, $label, $color]) {
            $from = Coordinate::stringFromColumnIndex($start + 1);
            $to = Coordinate::stringFromColumnIndex($start + $length);
            if ($length > 1) {
                $sheet->mergeCells("{$from}3:{$to}3");
            }
            $sheet->getStyle("{$from}3:{$to}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
            for ($c = $start + 1; $c <= $start + $length; $c++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth($label === '' ? 3 : 17);
            }
        }
        foreach (['C' => 30, 'D' => 25, 'K' => 32, 'S' => 25, 'P' => 25, 'Q' => 24, 'BK' => 35] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle("A3:BK{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($last >= 5) {
            $sheet->getStyle("V5:BJ{$last}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A5:BK{$last}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        }
        $sheet->freezePane('D5');
        $sheet->setAutoFilter("A4:BK{$last}");
        $sheet->getPageSetup()->setOrientation('landscape')->setRowsToRepeatAtTopByStartAndEnd(1, 4);
        $sheet->getPageSetup()->setPrintArea("A1:BK{$last}");
        foreach ($this->report['warnings'] as $index => $warning) {
            $line = $last + 3 + $index;
            $sheet->mergeCells("A{$line}:BK{$line}");
            $sheet->getStyle("A{$line}")->getAlignment()->setWrapText(true);
        }
        return [];
    }
}
