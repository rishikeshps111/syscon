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

test('monthly gross determines tax and ESIC while fixed gross determines eligibility', function (float $fixed, float $days, float $monthly, int $tax, float $esi) {
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
    '15000 boundary' => [15000.0, 30.0, 15000.0, 0, 113.0],
    'above 15000' => [15001.0, 30.0, 15001.0, 150, 113.0],
    '20000 boundary' => [20000.0, 30.0, 20000.0, 150, 150.0],
    'above 20000' => [20001.0, 30.0, 20001.0, 200, 150.0],
    'incentive crosses tax band' => [15000.0, 31.0, 15500.0, 150, 116.0],
    'extra duty increases earned gross ESI' => [20000.0, 33.0, 22000.0, 200, 165.0],
    'partial attendance reduces ESI' => [21000.0, 20.0, 14000.0, 0, 105.0],
    '30000 template earned as 20000' => [30000.0, 20.0, 20000.0, 150, 0.0],
    'exactly 21000 eligible' => [21000.0, 30.0, 21000.0, 200, 158.0],
    'above 21000 ineligible' => [21001.0, 30.0, 21001.0, 200, 0.0],
    'zero attendance means zero ESI' => [20000.0, 0.0, 0.0, 0, 0.0],
    'partial attendance 20000 template' => [20000.0, 20.0, 13333.33, 0, 100.0],
    'below 21000 eligible' => [20999.0, 30.0, 20999.0, 200, 157.0],
]);

test('reopening historical payroll presents monthly gross without changing saved deductions', function () {
    $user = new \App\Models\User(['name' => 'Employee']);
    $user->setRelation('roles', collect());
    $user->setRelation('staffProfile', null);
    $item = new \App\Models\SalaryProcessingItem([
        'basic_salary' => 20000, 'gross_salary' => 20000,
        'earned_salary' => 18000, 'extra_duty_incentive' => 1000,
        'pf' => 1200, 'professional_tax' => 200, 'esi' => 150,
        'tds' => 100, 'salary_advance' => 200, 'lwf' => 10, 'penalty' => 20,
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
        ->and([$row['tds'], $row['salary_advance'], $row['lwf'], $row['penalty']])->toBe([100.0, 200.0, 10.0, 20.0])
        ->and($row['total_deduction'])->toBe(1550.0)
        ->and($row['net_total'])->toBe(17450.0)
        ->and($item->getAttributes())->toBe($before);
});


test('manual payroll deductions add once without attendance proration', function (array $manual, float $extra) {
    $method = salaryProcessingPrivateMethod('applyUnauthorizedLeave');
    $base = [
        'basic_salary' => 20000, 'calendar_days' => 30, 'total_attendance_days' => 15,
        'deduction' => 0, 'salary_split' => [
            ['id' => 1, 'name' => 'Basic', 'type' => 'earning', 'amount' => 20000],
            ['id' => 2, 'name' => 'TDS', 'type' => 'deduction', 'amount' => 200],
        ],
    ];
    $row = $method->invoke(new SalaryProcessingController(), $base + $manual, 1);
    // Half-month earnings: 10000; template deduction 100; LOP 666.67; PF 1200; ESI 75.
    expect($row['total_deduction'])->toBe(round(2041.67 + $extra, 2))
        ->and($row['net_total'])->toBe(round(7958.33 - $extra, 2))
        ->and($row['net_salary'])->toBe($row['net_total']);
    foreach (['tds', 'salary_advance', 'lwf', 'penalty'] as $field) {
        expect($row[$field])->toBe(round((float) ($manual[$field] ?? 0)));
    }
})->with([
    'defaults' => [[], 0.0],
    'cleared inputs' => [['tds' => '', 'salary_advance' => null, 'lwf' => '', 'penalty' => null], 0.0],
    'TDS' => [['tds' => 123.45], 123.0],
    'advance' => [['salary_advance' => 234.56], 235.0],
    'LWF' => [['lwf' => 12.34], 12.0],
    'penalty' => [['penalty' => 45.67], 46.0],
    'combined' => [['tds' => 123.45, 'salary_advance' => 234.56, 'lwf' => 12.34, 'penalty' => 45.67], 416.0],
    'negative net remains supported' => [['salary_advance' => 12000], 12000.0],
]);


test('entered ESI replaces automatic ESI and contributes once to payroll totals', function (float $gross, float $esi) {
    $calculate = salaryProcessingPrivateMethod('applyUnauthorizedLeave');
    $row = $calculate->invoke(new SalaryProcessingController(), [
        'basic_salary' => $gross, 'calendar_days' => 30, 'total_attendance_days' => 30,
        'salary_split' => [], 'deduction' => 0, 'esi_override' => $esi,
    ], 0);
    $tax = $gross <= 15000 ? 0 : ($gross <= 20000 ? 150 : 200);
    expect($row['esi'])->toBe(round($esi))
        ->and($row['total_deduction'])->toBe(round($tax + round($esi), 2))
        ->and($row['net_total'])->toBe(round($gross - $tax - round($esi), 2))
        ->and($row['net_salary'])->toBe($row['net_total']);
})->with([[20000.0, 89.49], [20000.0, 89.50], [20000.0, 89.51], [20000.0, 0.0], [30000.0, 123.45]]);


test('whole rupee edit presentation adjusts totals without changing historical records', function () {
    $original = ['esi' => 150.49, 'tds' => 100.5, 'salary_advance' => 200.51, 'lwf' => 0.49, 'penalty' => 0.5,
        'total_deduction' => 500.49, 'net_total' => 9499.51];
    $rounded = \App\Support\SalaryCalculation::wholeRupeeDeductions($original);
    expect(array_intersect_key($rounded, array_flip(['esi', 'tds', 'salary_advance', 'lwf', 'penalty'])))
        ->toBe(['esi' => 150.0, 'tds' => 101.0, 'salary_advance' => 201.0, 'lwf' => 0.0, 'penalty' => 1.0])
        ->and($rounded['total_deduction'])->toBe(501.0)->and($rounded['net_total'])->toBe(9499.0)
        ->and($original['esi'])->toBe(150.49)->and($original['total_deduction'])->toBe(500.49)
        ->and(\App\Support\SalaryCalculation::wholeRupeeDeductions($rounded))->toBe($rounded);
});


test('UAN controls PF eligibility for every employee role at any salary', function (string $role, mixed $uan, bool $eligible) {
    $class = 'App\\Models\\'.$role.'Profile';
    $profile = new $class(['uan' => $uan]);
    $profile->setRelation('depot', null)->setRelation('designation', null);
    $user = new \App\Models\User(['name' => 'Employee']);
    $user->setRelation('roles', collect([new \Spatie\Permission\Models\Role(['name' => $role])]));
    $user->setRelation(lcfirst($role).'Profile', $profile);
    $item = new \App\Models\SalaryProcessingItem(['salary_split' => [], 'pf' => 1200, 'total_deduction' => 1400, 'net_total' => 28600]);
    $item->setRelation('user', $user)->setRelation('salaryProcessing', null);
    $controller = new SalaryProcessingController;
    $stored = salaryProcessingPrivateMethod('storedRow')->invoke($controller, $item);
    expect($stored['pf_applicable'])->toBe($eligible)->and($item->pf)->toBe('1200.00');
    $calculated = salaryProcessingPrivateMethod('calculatePayrollTotals')->invoke($controller, [
        'pf_applicable' => $stored['pf_applicable'], 'basic_salary' => 30000, 'calendar_days' => 30,
        'total_attendance_days' => 30, 'salary_split' => [
            ['id' => 1, 'name' => 'Basic', 'type' => 'earning', 'amount' => 10000],
        ],
    ]);
    expect($calculated['pf'])->toBe($eligible ? 1200.0 : 0.0)
        ->and($calculated['total_deduction'])->toBe($eligible ? 1400.0 : 200.0)
        ->and($calculated['net_total'])->toBe($eligible ? 28600.0 : 29800.0);
})->with(['Driver', 'Staff', 'Housekeeping', 'Controller', 'Supervisor'])
    ->with([[null, false], ['', false], ['  ', false], ['001234567890', true]]);

test('employee forms normalize nullable UAN and retain length validation', function (string $requestClass) {
    foreach ([null, '', '  ', '  001234567890  '] as $uan) {
        $request = $requestClass::create('/', 'POST', ['uan' => $uan, 'role' => 'Staff']);
        (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
        $expected = trim((string) $uan) === '' ? null : trim((string) $uan);
        expect($request->input('uan'))->toBe($expected);
        $rules = $request->rules()['uan'];
        expect(\Illuminate\Support\Facades\Validator::make(['uan' => $request->input('uan')], ['uan' => $rules])->fails())->toBeFalse()
            ->and(\Illuminate\Support\Facades\Validator::make([], ['uan' => $rules])->fails())->toBeFalse()
            ->and(\Illuminate\Support\Facades\Validator::make(['uan' => str_repeat('1', 51)], ['uan' => $rules])->fails())->toBeTrue();
    }
    $request = $requestClass::create('/', 'POST', ['uan' => ['invalid'], 'role' => 'Staff']);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
    expect(\Illuminate\Support\Facades\Validator::make(['uan' => $request->input('uan')], ['uan' => $request->rules()['uan']])->fails())->toBeTrue();
})->with([
    \App\Http\Requests\StoreDriverManagementRequest::class,
    \App\Http\Requests\StoreStaffManagementRequest::class,
    \App\Http\Requests\StoreControllerManagementRequest::class,
    \App\Http\Requests\StoreSupervisorManagementRequest::class,
    \App\Http\Requests\SaveUnifiedStaffRequest::class,
]);


test('ESIC eligibility uses fixed gross while both contributions use earned gross', function (float $fixed, float $earned, float $employee, float $employer) {
    expect(\App\Support\SalaryCalculation::esicContributions($fixed, $earned))
        ->toBe(['esi' => $employee, 'employer_esi' => $employer]);
})->with([
    [20000.0, 10000.0, 75.0, 325.0], [21000.0, 10000.0, 75.0, 325.0],
    [21001.0, 10000.0, 0.0, 0.0], [30000.0, 20000.0, 0.0, 0.0],
    [20000.0, 0.0, 0.0, 0.0], [0.0, 0.0, 0.0, 0.0],
    [20000.0, 22000.0, 165.0, 715.0], [21000.0, 23100.0, 173.0, 750.75],
    [20000.0, 13333.33, 100.0, 433.33],
]);
