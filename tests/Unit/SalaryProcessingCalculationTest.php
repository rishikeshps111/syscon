<?php

use App\Http\Controllers\SalaryProcessingController;

uses(Tests\TestCase::class);

test('attendance components retain template amounts across repeated calculations', function (int $calendarDays, float $workedDays) {
    $split = [
        ['name' => 'Basic', 'type' => 'earning', 'amount' => 15000, 'selected' => true],
        ['name' => 'Allowance', 'type' => 'earning', 'amount' => 15000, 'selected' => true],
        ['name' => 'Recovery', 'type' => 'deduction', 'amount' => 300, 'selected' => true],
        ['name' => 'Duty Incentive', 'type' => 'earning', 'amount' => 0, 'selected' => true],
    ];
    $method = salaryProcessingPrivateMethod('calculatePayrollTotals');
    $row = $method->invoke(new SalaryProcessingController(), [
        'basic_salary' => 30000, 'calendar_days' => $calendarDays,
        'total_attendance_days' => $workedDays, 'salary_split' => $split, 'deduction' => 300,
    ]);
    $ratio = min($workedDays, $calendarDays) / $calendarDays;
    expect($row['salary_split'][0]['amount'])->toBe(15000)
        ->and($row['salary_split'][0]['calculated_amount'])->toBe(15000.0 * $ratio)
        ->and($row['template_deduction'])->toBe(round(300 * $ratio, 2))
        ->and($row['pf'])->toBe(round(1800 * $ratio, 2))
        ->and($row['gross_salary'])->toBe(round(30000 / $calendarDays * $workedDays, 2))
        ->and($method->invoke(new SalaryProcessingController(), $row))->toBe($row);
})->with([
    [28, 20.5], [29, 29.0], [30, 0.0], [30, 20.0], [30, 32.0], [31, 22.5],
]);

function salaryProcessingPrivateMethod(string $methodName): ReflectionMethod
{
    $method = new ReflectionMethod(SalaryProcessingController::class, $methodName);
    $method->setAccessible(true);

    return $method;
}

test('salary processing net salary includes attendance earnings and deductions', function () {
    $controller = new SalaryProcessingController();
    $method = salaryProcessingPrivateMethod('applyUnauthorizedLeave');

    $row = $method->invoke($controller, [
        'basic_salary' => 30000,
        'incentive' => 2500,
        'deduction' => 1200,
        'total_working_days' => 30,
        'calendar_days' => 30,
        'total_attendance_days' => 30,
        'salary_day_rate' => 1000,
    ], 2);

    expect($row['lop'])->toBe(2000.0)
        ->and($row['net_salary'])->toBe(26600.0);
});

test('selected duty incentive is excluded from gross while other earnings remain included', function () {
    $controller = new SalaryProcessingController();
    $method = salaryProcessingPrivateMethod('applySelectedComponents');

    $row = $method->invoke($controller, [
        'basic_salary' => 0,
        'incentive' => 0,
        'salary_split' => [
            ['id' => 10, 'name' => 'Basic', 'type' => 'earning', 'amount' => 20000],
            ['id' => 11, 'name' => 'Trip Incentive', 'type' => 'earning', 'amount' => 1500],
            ['id' => 12, 'name' => ' Duty INCENTIVE ', 'type' => 'earning', 'amount' => 9999],
        ],
    ], [10, 11, 12]);

    expect($row['basic_salary'])->toBe(21500.0)
        ->and($row['incentive'])->toBe(0.0);
});

test('duty incentive split matches calculated extra duty without counting it twice', function (float $attendance, float $incentive, float $earned) {
    $controller = new SalaryProcessingController();
    $split = [
        ['id' => 10, 'name' => 'Basic', 'type' => 'earning', 'amount' => 30000, 'selected' => true],
        ['id' => 12, 'name' => ' Duty INCENTIVE ', 'type' => 'earning', 'amount' => 9999, 'selected' => true],
    ];
    $gross = salaryProcessingPrivateMethod('grossSalary')->invoke($controller, new \App\Models\User(), $split);
    $row = salaryProcessingPrivateMethod('calculatePayrollTotals')->invoke($controller, [
        'basic_salary' => $gross,
        'calendar_days' => 30,
        'total_attendance_days' => $attendance,
        'salary_split' => $split,
        'deduction' => 0,
    ]);

    expect($gross)->toBe(30000.0)
        ->and($row['extra_duty_incentive'])->toBe($incentive)
        ->and($row['salary_split'][1]['amount'])->toBe($incentive)
        ->and($row['total_earned'])->toBe($earned)
        ->and($row['gross_salary'])->toBe($earned)
        ->and($split[1]['amount'])->toBe(9999);

    $recalculated = salaryProcessingPrivateMethod('calculatePayrollTotals')->invoke($controller, $row);
    expect($recalculated['total_earned'])->toBe($earned);
})->with([
    'extra duty' => [32.0, 2000.0, 32000.0],
    'no extra duty' => [30.0, 0.0, 30000.0],
    'partial attendance' => [28.0, 0.0, 28000.0],
    'zero attendance' => [0.0, 0.0, 0.0],
]);

test('stored split mapping uses saved incentive even for an excluded component', function () {
    $split = [['id' => 12, 'name' => 'Duty incentive', 'amount' => 900, 'selected' => false]];
    $mapped = salaryProcessingPrivateMethod('dutyIncentiveSplit')->invoke(new SalaryProcessingController(), $split, 0.0);

    expect($mapped[0]['amount'])->toBe(0.0)
        ->and($mapped[0]['selected'])->toBeFalse()
        ->and($split[0]['amount'])->toBe(900);
});

test('monthly gross determines tax while fixed template gross determines ESIC', function (float $fixed, float $days, float $monthly, int $tax, float $esi) {
    $row = salaryProcessingPrivateMethod('calculatePayrollTotals')->invoke(new SalaryProcessingController(), [
        'basic_salary' => $fixed,
        'calendar_days' => 30,
        'total_attendance_days' => $days,
        'deduction' => 25,
        'salary_split' => [
            ['name' => ' Basic ', 'amount' => 10000, 'selected' => true],
            ['name' => 'VDA', 'amount' => 1000, 'selected' => true],
            ['name' => 'Basic', 'amount' => 9999, 'selected' => false],
            ['name' => 'Recovery', 'type' => 'deduction', 'amount' => 25, 'selected' => true],
        ],
    ]);

    expect($row['gross_salary'])->toBe($monthly)
        ->and($row['professional_tax'])->toBe((float) $tax)
        ->and($row['esi'])->toBe($esi)
        ->and($row['pf'])->toBe(round(1320 * min($days, 30) / 30, 2))
        ->and($row['total_deduction'])->toBe(round(25 * min($days, 30) / 30 + $row['pf'] + $tax + $esi, 2))
        ->and($row['net_total'])->toBe(round($monthly - $row['total_deduction'], 2));
})->with([
    '15000 boundary' => [15000.0, 30.0, 15000.0, 0, 112.5],
    'above 15000' => [15001.0, 30.0, 15001.0, 150, 112.51],
    '20000 boundary' => [20000.0, 30.0, 20000.0, 150, 150.0],
    'above 20000' => [20001.0, 30.0, 20001.0, 200, 150.01],
    'incentive crosses tax band' => [15000.0, 31.0, 15500.0, 150, 112.5],
    'extra duty leaves fixed ESI unchanged' => [20000.0, 33.0, 22000.0, 200, 150.0],
    'partial attendance leaves fixed ESI unchanged' => [21000.0, 20.0, 14000.0, 0, 157.5],
    '30000 template earned as 20000' => [30000.0, 20.0, 20000.0, 150, 0.0],
    'exactly 21000 eligible' => [21000.0, 30.0, 21000.0, 200, 157.5],
    'above 21000 ineligible' => [21001.0, 30.0, 21001.0, 200, 0.0],
    'zero attendance retains fixed ESI' => [20000.0, 0.0, 0.0, 0, 150.0],
    'partial attendance 20000 template' => [20000.0, 20.0, 13333.33, 0, 150.0],
    'below 21000 eligible' => [20999.0, 30.0, 20999.0, 200, 157.49],
]);

test('reopening historical payroll presents monthly gross without changing saved deductions', function () {
    $user = new \App\Models\User(['name' => 'Employee']);
    $user->setRelation('roles', collect());
    $user->setRelation('staffProfile', null);
    $item = new \App\Models\SalaryProcessingItem([
        'basic_salary' => 20000, 'gross_salary' => 20000,
        'earned_salary' => 18000, 'extra_duty_incentive' => 1000,
        'pf' => 1200, 'professional_tax' => 200, 'esi' => 150,
        'total_deduction' => 1550, 'net_total' => 17450,
        'salary_split' => [],
    ]);
    $item->setRelation('user', $user);
    $item->setRelation('salaryProcessing', null);
    $before = $item->getAttributes();
    $row = salaryProcessingPrivateMethod('storedRow')->invoke(new SalaryProcessingController(), $item);

    expect($row['gross_salary'])->toBe(19000.0)
        ->and($row['professional_tax'])->toBe(200.0)
        ->and($row['esi'])->toBe(150.0)
        ->and($row['total_deduction'])->toBe(1550.0)
        ->and($row['net_total'])->toBe(17450.0)
        ->and($item->getAttributes())->toBe($before);
});
