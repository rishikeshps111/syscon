<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceConsolidateWorkbook
{
    public function download(iterable $rows, string $title, string $filename)
    {
        return response()->streamDownload(function () use ($rows, $title) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet()->setTitle('Attendance Consolidate');
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValueExplicit('A1', $title, DataType::TYPE_STRING);
                $sheet->fromArray(AttendanceConsolidateCsv::HEADERS, null, 'A2');
                $line = 3;
                foreach ($rows as $row) {
                    $sheet->setCellValueExplicit('A'.$line, (string) $row['employee_ref_code'], DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('B'.$line, (string) $row['employee_name'], DataType::TYPE_STRING);
                    foreach (['C' => 'present_days', 'D' => 'week_off_days', 'E' => 'absent_days', 'F' => 'total_days'] as $column => $field) {
                        if (isset($row[$field])) {
                            $sheet->setCellValueExplicit($column.$line, (float) $row[$field], DataType::TYPE_NUMERIC);
                        }
                    }
                    $line++;
                }
                $last = max(2, $line - 1);
                $sheet->getStyle('A1:F'.$last)->getFont()->setName('Calibri')->setSize(11);
                $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('92D050');
                $sheet->getStyle('A2:F2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                $sheet->getStyle('A1:F2')->getFont()->setBold(true);
                $sheet->getStyle('A1:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                $sheet->getStyle('A2:F'.$last)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(45);
                foreach (['C', 'D', 'E', 'F'] as $column) {
                    $sheet->getColumnDimension($column)->setWidth(12);
                }
                if ($last >= 3) {
                    $sheet->getStyle('C3:F'.$last)->getNumberFormat()->setFormatCode('General');
                }
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getRowDimension(2)->setRowHeight(30);
                $sheet->freezePane('C3');
                $sheet->setAutoFilter('A2:F'.$last);
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'X-Content-Type-Options' => 'nosniff']);
    }
}
