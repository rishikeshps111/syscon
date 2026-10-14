<?php

use App\Http\Controllers\GeneratePaySlipController;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\User;

uses(Tests\TestCase::class);

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
    $html = view('payroll.pay-slip.partials.preview', [
        'processing' => $processing, 'item' => $item, 'monthName' => 'October',
    ])->render();
    $method = new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf');
    $pdf = $method->invoke(new GeneratePaySlipController(), $processing, $item);
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
