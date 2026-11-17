<?php

use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Support\ShiftQuantity;
use App\Support\WageRegister;
use App\Support\WageRegisterPdf;

uses(Tests\TestCase::class);

test('shift quantities show two decimals only for fractional values', function (float $value, string $expected) {
    expect(ShiftQuantity::format($value))->toBe($expected)
        ->and(WageRegister::formatCell($value, 37))->toBe($expected)
        ->and(ShiftQuantity::excelFormat($value))->toBe(str_contains($expected, '.') ? '#,##0.00' : '#,##0');
})->with([[0.0, '0'], [31.0, '31'], [31.50, '31.50'], [26.25, '26.25'], [33.75, '33.75'], [1.5, '1.50']]);

test('shift formatting does not change money identifiers or missing report values', function () {
    expect(WageRegister::formatCell(31.5, 48))->toBe('32')
        ->and(WageRegister::formatCell('000031', 1))->toBe('000031')
        ->and(WageRegister::formatCell(null, 37))->toBe('');
});

test('payslips keep fractional present shifts and hide decimals for whole shifts', function (float $value, string $expected) {
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 9]);
    $processing->setRelation('depot', null);
    $item = new SalaryProcessingItem(['present_days' => $value, 'total_attendance_days' => $value]);
    $item->setRelation('user', null);
    $before = $item->getAttributes();
    $html = view('payroll.pay-slip.partials.preview', compact('processing', 'item'))->render();
    expect($html)->toContain('Present Shifts</td><td class="slip-value">'.$expected.'</td>')
        ->and($item->getAttributes())->toBe($before);
})->with([[31.0, '31'], [31.5, '31.50'], [26.25, '26.25'], [33.75, '33.75']]);

test('wage register PDF draws fractional quantities without rounding shifts to integers', function () {
    $row = array_fill(0, 63, null);
    $row[0] = 1; $row[1] = '0001'; $row[2] = 'Employee';
    $row[34] = 31.5; $row[35] = 26.25; $row[37] = 33.75; $row[39] = 3.75;
    $pdf = WageRegisterPdf::render(['depot' => null, 'monthName' => 'September', 'year' => 2026,
        'roleLabel' => 'Staff', 'headings' => WageRegister::headings(), 'rows' => [$row], 'warnings' => []]);
    preg_match_all('/stream\r?\n(.*?)\r?\n?endstream/s', $pdf, $matches);
    $streams = '';
    foreach ($matches[1] as $stream) $streams .= (@gzuncompress($stream) ?: $stream);
    foreach (['31.50', '26.25', '33.75', '3.75'] as $expected) {
        expect($streams)->toContain(mb_convert_encoding($expected, 'UTF-16BE', 'UTF-8'));
    }
});
