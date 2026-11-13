<?php

use App\Http\Controllers\GeneratePaySlipController;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\User;

uses(Tests\TestCase::class);

test('payslip preview and PDF use earned gross ESIC saved by payroll', function (float $fixed, float $days, float $esi) {
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
    $slip = \App\Support\PaySlipData::build($processing, $item);
    expect($slip['employer_pf'])->toBe($row['pf'])
        ->and($slip['employer_esi'])->toBe($fixed <= 21000 ? round($row['total_earned'] * .0325, 2) : 0.0);
    $html = view('payroll.pay-slip.partials.preview', ['processing' => $processing, 'item' => $item, 'monthName' => 'September'])->render();
    $pdf = (new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf'))->invoke(new GeneratePaySlipController(), $processing, $item);
    foreach ([$esi, $row['total_deduction'], $row['net_total']] as $amount) {
        expect($html)->toContain(number_format($amount, 0));
        expect($pdf)->toContain(number_format($amount, 0));
    }
    expect($item->getAttributes())->toBe($before);
})->with([[20000.0, 20.0, 100.0], [20000.0, 33.0, 165.0], [21000.0, 20.0, 105.0], [21001.0, 20.0, 0.0]]);

test('payslip money rounds half away from zero without changing amounts', function (float $value, string $expected) {
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 9]);
    $processing->setRelation('depot', null);
    $item = new SalaryProcessingItem(['net_total' => $value]);
    $item->setRelation('user', null);
    $before = $item->getAttributes();
    $html = view('payroll.pay-slip.partials.preview', compact('processing', 'item'))->render();
    $pdf = (new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf'))->invoke(new GeneratePaySlipController, $processing, $item);
    expect($html)->toContain('Net Salary - Rs. '.$expected)->and($pdf)->toContain('Net Salary - Rs. '.$expected)
        ->and($item->getAttributes())->toBe($before);
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
    foreach (['19,001', '1,201', '150', '17,450', '1,000', '28.5', 'ESIC'] as $text) {
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


test('payslip preview and PDF show manual plus template deductions and saved totals', function () {
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 9]);
    $processing->setRelation('depot', null)->setRelation('approver', null);
    $user = new User(['name' => 'Manual Deduction Employee']);
    $user->setRelation('roles', collect())->setRelation('staffProfile', null);
    $item = new SalaryProcessingItem([
        'tds' => 1234, 'salary_advance' => 2345, 'lwf' => 56, 'penalty' => 789,
        'total_attendance_days' => 15, 'earned_salary' => 10000,
        'esi' => 91.25, 'total_deduction' => 4992.25, 'net_total' => 5007.75,
        'salary_split' => [['name' => 'TDS', 'type' => 'deduction', 'amount' => 200]],
    ]);
    $item->setRelation('user', $user);
    $html = view('payroll.pay-slip.partials.preview', ['processing' => $processing, 'item' => $item, 'monthName' => 'September'])->render();
    $pdf = (new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf'))->invoke(new GeneratePaySlipController(), $processing, $item);
    foreach (['TDS', 'Salary Advance', 'LWF', 'Penalty', '1,334', '2,345', '56', '789', '91', '4,992', '5,008'] as $text) {
        expect($html)->toContain($text);
        expect($pdf)->toContain($text);
    }
    if (getenv('MANUAL_DEDUCTION_QA_PDF')) file_put_contents(getenv('MANUAL_DEDUCTION_QA_PDF'), $pdf);
});


test('reference payslip layout maps every role and preserves saved payroll amounts', function (string $role) {
    $profileClass = 'App\\Models\\'.$role.'Profile';
    $profile = new $profileClass;
    $profile->forceFill(['uan' => null, 'esic_wc' => 'ESIC-001', 'account_number' => '0000123456',
        'bank_account_number' => '0000123456', 'ifsc_code' => 'ubin0591980']);
    if ($role === 'Driver') $profile->forceFill(['wc_policy' => 'OG-25-1607-TEST']);
    $profile->setRelation('designation', new \App\Models\Designation(['name' => 'NLG '.$role]));
    $profile->setRelation('location', new \App\Models\Location(['name' => 'Nalgonda']));
    $user = new User(['ref_code' => '6213', 'code' => 'GENERATED-CODE', 'name' => 'SHAIK YUNIS BABA']);
    $user->setRelation('roles', collect([new \Spatie\Permission\Models\Role(['name' => $role])]));
    $user->setRelation(lcfirst($role).'Profile', $profile);
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 3]);
    $processing->setRelation('depot', new \App\Models\Depot(['name' => 'Nalgonda Depot']));
    $split = [];
    foreach (['Basic Salary' => 4319, 'VDA' => 7555, 'HRA' => 4745, 'Duty Incentive' => 1296, 'Special Allowance' => 4094, 'Bonus' => 1000] as $name => $amount) {
        $split[] = ['name' => $name, 'type' => 'earning', 'amount' => $amount, 'selected' => true];
    }
    $split[] = ['name' => 'Excluded', 'type' => 'earning', 'amount' => 99999, 'selected' => false];
    $item = new SalaryProcessingItem(['salary_split' => $split, 'total_attendance_days' => 31, 'present_days' => 31,
        'pf' => 0, 'professional_tax' => 200, 'esi' => 0, 'tds' => 0, 'salary_advance' => 100, 'lwf' => 10, 'penalty' => 20,
        'extra_duty_incentive' => 1296, 'total_earned' => 23009, 'total_deduction' => 330, 'net_total' => 22679]);
    $item->setRelation('user', $user);
    $before = $item->getAttributes();
    $slip = \App\Support\PaySlipData::build($processing, $item);
    expect($slip['employee_code'])->toBe('6213')->and($slip['designation'])->toBe('NLG '.$role)
        ->and($slip['account_number'])->toBe('0000123456')->and($slip['ifsc'])->toBe('UBIN0591980')
        ->and($slip['uan'])->toBe('-')->and($slip['present_shifts'])->toBe(31.0)
        ->and($slip['employer_pf'])->toBe(0.0)->and($slip['employer_esi'])->toBe(0.0)
        ->and($slip['earnings'])->not->toHaveKey('Excluded')->and($slip['gross'])->toBe(23009.0)
        ->and($slip['net'])->toBe(22679.0)->and($slip['logo'])->toStartWith('data:image/png;base64,');
    $html = view('payroll.pay-slip.partials.preview', compact('processing', 'item'))->render();
    $pdf = (new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf'))->invoke(new GeneratePaySlipController, $processing, $item);
    foreach (['6213', 'SHAIK YUNIS BABA', 'Nalgonda', 'UBIN0591980', 'Present Shifts', 'Gross Earnings', 'Gross Deductions', 'Employer Contributions', '23,009', '22,679'] as $text) {
        expect($html)->toContain($text);
        expect($pdf)->toContain($text);
    }
    expect(preg_match_all('/\/Type\s*\/Page\b/', $pdf))->toBe(1);
    expect($html)->not->toContain('Present Days', 'Total Days', 'Payment &amp; Approval', 'GENERATED-CODE')
        ->and($pdf)->toContain('/Subtype /Image')->and($item->getAttributes())->toBe($before);
    if ($role === 'Driver') {
        expect($html)->toContain('OG-25-1607-TEST')->and($pdf)->toContain('OG-25-1607-TEST');
        if (getenv('REFERENCE_PAYSLIP_QA_PDF')) file_put_contents(getenv('REFERENCE_PAYSLIP_QA_PDF'), $pdf);
    } else {
        expect($html)->not->toContain('under policy number');
    }
    $user->ref_code = null;
    expect(\App\Support\PaySlipData::build($processing, $item)['employee_code'])->toBe('GENERATED-CODE');
})->with(['Driver', 'Staff', 'Housekeeping', 'Controller', 'Supervisor']);


test('payslip PDF retains long names and every component across overflow pages', function () {
    $user = new User(['name' => 'An Employee With A Long Name That Must Wrap Inside The Employee Information Cell', 'code' => 'EMP-LONG']);
    $user->setRelation('roles', collect());
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 9]);
    $processing->setRelation('depot', null);
    $split = [['name' => 'Basic', 'type' => 'earning', 'amount' => 10000]];
    foreach (range(1, 60) as $index) $split[] = ['name' => sprintf('Benefit %02d', $index), 'type' => 'earning', 'amount' => 100];
    $item = new SalaryProcessingItem(['salary_split' => $split, 'total_attendance_days' => 30, 'present_days' => 30,
        'total_earned' => 16000, 'pf' => 1000, 'total_deduction' => 1000, 'net_total' => 15000]);
    $item->setRelation('user', $user);
    $pdf = (new ReflectionMethod(GeneratePaySlipController::class, 'buildPdf'))->invoke(new GeneratePaySlipController, $processing, $item);
    expect(preg_match_all('/\/Type\s*\/Page\b/', $pdf))->toBeGreaterThan(1);
    foreach (range(1, 60) as $index) expect($pdf)->toContain(sprintf('Benefit %02d', $index));
    expect($pdf)->toContain('15,000', 'Net Salary');
    if (getenv('OVERFLOW_PAYSLIP_QA_PDF')) file_put_contents(getenv('OVERFLOW_PAYSLIP_QA_PDF'), $pdf);
});
