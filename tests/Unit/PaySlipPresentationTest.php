<?php

use App\Http\Controllers\GeneratePaySlipController;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\User;

uses(Tests\TestCase::class);

test('payslip preview and PDF use fixed template ESI saved by payroll', function (float $fixed, float $days, float $esi) {
    $split = [
        ['name' => 'Basic', 'type' => 'earning', 'amount' => 10000, 'selected' => true],
        ['name' => 'VDA', 'type' => 'earning', 'amount' => 1000, 'selected' => true],
        ['name' => 'Allowance', 'type' => 'earning', 'amount' => $fixed - 11000, 'selected' => true],
        ['name' => 'Duty Incentive', 'type' => 'earning', 'amount' => 9999, 'selected' => true],
    ];
    $calculate = new ReflectionMethod(\App\Http\Controllers\SalaryProcessingController::class, 'calculatePayrollTotals');
    $row = $calculate->invoke(new \App\Http\Controllers\SalaryProcessingController(), [
        'basic_salary' => $fixed, 'calendar_days' => 30, 'total_attendance_days' => $days,
        'salary_split' => $split, 'deduction' => 0,
    ]);
    expect($row['esi'])->toBe($esi);
    $item = new SalaryProcessingItem($row);
    $user = new User(['name' => 'Sample Employee']);
    $user->setRelation('roles', collect());
    foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $relation) {
        $user->setRelation($relation, null);
    }
    $item->setRelation('user', $user);
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 9, 'status' => 'Approved']);
    $processing->setRelation('depot', null);
    $processing->setRelation('approver', null);
    $before = $item->getAttributes();
    $html = view('payroll.pay-slip.partials.preview', ['processing' => $processing, 'item' => $item, 'monthName' => 'September'])->render();
    $pdf = (new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf'))->invoke(new GeneratePaySlipController(), $processing, $item);
    foreach ([$esi, $row['total_deduction'], $row['net_total']] as $amount) {
        expect($html)->toContain(number_format($amount, 0));
        expect($pdf)->toContain(number_format($amount, 0));
    }
    expect($item->getAttributes())->toBe($before);
})->with([[20000.0, 20.0, 150.0], [20000.0, 33.0, 150.0], [21000.0, 20.0, 157.5], [21001.0, 20.0, 0.0]]);

test('payslip money rounds half away from zero without changing amounts', function (float $value, string $expected) {
    $method = new ReflectionMethod(GeneratePaySlipController::class, 'money');
    expect($method->invoke(new GeneratePaySlipController(), $value))->toBe($expected);
})->with([
    [1.49, '1'], [1.5, '2'], [1.51, '2'],
    [-1.49, '-1'], [-1.5, '-2'], [-1.51, '-2'], [1234.5, '1,235'],
]);

test('preview and PDF show monthly gross and preserve historical deductions', function () {
    $user = new User(['name' => 'Sample Employee', 'code' => 'EMP001']);
    $user->setRelation('roles', collect());
    foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $relation) {
        $user->setRelation($relation, null);
    }
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 10, 'status' => 'Pending']);
    $processing->setRelation('depot', null);
    $processing->setRelation('approver', null);
    $item = new SalaryProcessingItem([
        'gross_salary' => 20000, 'basic_salary' => 20000,
        'earned_salary' => 18000.25, 'extra_duty_incentive' => 1000.25,
        'pf' => 1200.5, 'professional_tax' => 200, 'esi' => 150.49,
        'total_deduction' => 1550.99, 'net_total' => 17449.51,
        'total_attendance_days' => 28.5, 'salary_day_rate' => 666.67,
        'salary_split' => [
            ['name' => 'Basic', 'type' => 'earning', 'amount' => 10000.5],
            ['name' => 'Duty incentive', 'type' => 'earning', 'amount' => 9999],
        ],
    ]);
    $item->setRelation('user', $user);
    $before = $item->getAttributes();
    $calculated = \App\Support\SalaryCalculation::payslipSplit($processing, $item);
    expect($calculated[0]['amount'])->toBe(10000.5 * (28.5 / 31))
        ->and($calculated[1]['amount'])->toBe(1000.25);
    $html = view('payroll.pay-slip.partials.preview', [
        'processing' => $processing, 'item' => $item, 'monthName' => 'October',
    ])->render();
    $method = new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf');
    $pdf = $method->invoke(new GeneratePaySlipController(), $processing, $item);
    expect($html)->toContain(number_format($calculated[0]['amount'], 0));
    expect($pdf)->toContain(number_format($calculated[0]['amount'], 0));
    foreach (['19,001', '1,201', '150', '17,450', '1,000', '28.50', 'ESIC'] as $text) {
        expect($html)->toContain($text);
        expect($pdf)->toContain($text);
    }
    foreach (['Earned Salary', 'Extra Duty Incentive', 'Total Earned'] as $label) {
        expect($html)->not->toContain($label);
        expect($pdf)->not->toContain($label);
    }
    expect($item->getAttributes())->toBe($before);
    if (getenv('PAYSLIP_QA_PDF')) {
        file_put_contents(getenv('PAYSLIP_QA_PDF'), $pdf);
    }
});
