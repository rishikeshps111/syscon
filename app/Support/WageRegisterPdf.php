<?php

namespace App\Support;

use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;

/** Draw tables directly, avoiding an HTML DOM containing an entire depot's register. */
class WageRegisterPdf
{
    public static function render(array $report): string
    {
        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $canvas = new CPDF('a3', 'landscape', $dompdf);
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans');
        $bold = $metrics->getFont('DejaVu Sans', 'bold');
        $wrap = function (string $text, float $width, bool $isBold = false) use ($metrics, $font, $bold): array {
            $lines = [];
            $line = '';
            foreach (mb_str_split(str_replace(["\r", "\n", "\t"], ' ', $text)) as $char) {
                if ($line !== '' && $metrics->getTextWidth($line.$char, $isBold ? $bold : $font, 8) > $width) {
                    $lines[] = rtrim($line);
                    $line = '';
                }
                $line .= $char;
            }
            $lines[] = rtrim($line);
            return $lines;
        };
        $sections = [
            ['EMPLOYEE DATA - Personal', range(3, 12)],
            ['EMPLOYEE DATA - Employment and bank', range(13, 19)],
            ['FIXED SALARY', range(21, 30)], ['ATTENDANCE', range(32, 39)],
            ['EARNINGS', range(40, 48)], ['DEDUCTIONS / Net Salary', range(50, 58)],
            ['EMPLOYER CONTRIBUTION / Remarks', [60, 61, 62]],
        ];
        $first = true;
        foreach ($sections as [$label, $dataColumns]) {
            $columns = array_merge([0, 1, 2], $dataColumns);
            $available = $canvas->get_width() - 48;
            $widths = array_merge([42, 85, 145], array_fill(0, count($dataColumns), ($available - 272) / count($dataColumns)));
            $header = function () use ($canvas, $report, $label, $columns, $widths, $font, $bold, $wrap): float {
                $canvas->text(24, 20, WageRegister::title($report), $bold, 14);
                $canvas->text(24, 45, $label.' - Role: '.$report['roleLabel'], $font, 10);
                $x = 24;
                foreach ($columns as $index => $column) {
                    $width = $widths[$index];
                    $canvas->filled_rectangle($x, 68, $width, 58, [.86, .91, .96]);
                    $canvas->rectangle($x, 68, $width, 58, [.65, .70, .76], .5);
                    $heading = $report['headings'][$column] ?: ($column === 58 ? 'Net Salary' : ($column === 62 ? 'Remarks' : ''));
                    foreach ($wrap($heading, $width - 10, true) as $line => $text) {
                        $canvas->text($x + 5, 73 + $line * 10, $text, $bold, 8);
                    }
                    $x += $width;
                }
                return 126;
            };
            if (! $first) {
                $canvas->new_page();
            }
            $first = false;
            $y = $header();
            foreach ($report['rows'] as $row) {
                $cells = [];
                foreach ($columns as $index => $column) {
                    $value = $row[$column];
                    $cells[] = $wrap(is_float($value) ? number_format($value, 0) : (string) $value, $widths[$index] - 10);
                }
                $height = max(23, max(array_map('count', $cells)) * 10 + 10);
                if ($y + $height > $canvas->get_height() - 32) {
                    $canvas->new_page();
                    $y = $header();
                }
                $x = 24;
                foreach ($cells as $index => $lines) {
                    $canvas->rectangle($x, $y, $widths[$index], $height, [.65, .70, .76], .5);
                    foreach ($lines as $line => $text) {
                        $textX = is_float($row[$columns[$index]])
                            ? $x + $widths[$index] - 5 - $metrics->getTextWidth($text, $font, 8)
                            : $x + 5;
                        $canvas->text($textX, $y + 5 + $line * 10, $text, $font, 8);
                    }
                    $x += $widths[$index];
                }
                $y += $height;
            }
        }
        if ($report['warnings']) {
            $canvas->new_page();
            $heading = function () use ($canvas, $bold, $font): int {
                $canvas->text(24, 24, 'Reconciliation notes', $bold, 14);
                $canvas->text(24, 48, 'Approved payroll amounts have not been changed.', $font, 10);
                return 78;
            };
            $y = $heading();
            foreach ($report['warnings'] as $warning) {
                foreach ($wrap($warning, $canvas->get_width() - 48) as $line) {
                    if ($y > $canvas->get_height() - 40) {
                        $canvas->new_page();
                        $y = $heading();
                    }
                    $canvas->text(24, $y, $line, $font, 8);
                    $y += 13;
                }
                $y += 6;
            }
        }
        $canvas->page_text($canvas->get_width() - 100, $canvas->get_height() - 24, '{PAGE_NUM} / {PAGE_COUNT}', $font, 8);
        return $canvas->output();
    }
}
