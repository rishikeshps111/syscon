<?php

use App\Exports\SalaryReportExport;
use App\Http\Controllers\SalaryReportController;
use App\Models\Depot;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\WageRegister;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class);

function registerFixture(float $fixed = 20000, float $days = 20): array
{
    $user = new User(['ref_code' => '00012', 'code' => 'WRONG', 'name' => 'Example Employee']);
    $user->setRelation('roles', collect([new Role(['name' => 'Staff'])]));
    $profile = new StaffProfile(['depot_id' => 1, 'bank_account_number' => '0000123456', 'esic_wc' => '0123456789', 'ifsc_code' => ' hdfc0000001 ']);
    $profile->setRelation('location', null);
    $profile->setRelation('designation', null);
    foreach (['driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $relation) {
        $user->setRelation($relation, null);
    }
    $user->setRelation('staffProfile', $profile);
    $processing = new SalaryProcessing(['year' => 2026, 'month' => 9, 'depot_id' => 1, 'status' => 'Approved']);
    $processing->setRelation('depot', new Depot(['name' => 'Example Depot', 'short_name' => 'NLG']));
    $item = new SalaryProcessingItem([
        'basic_salary' => $fixed, 'total_attendance_days' => $days, 'present_days' => max($days - 4, 0), 'week_off_days' => min(4, $days),
        'extra_duty_incentive' => $fixed / 30 * max($days - 30, 0),
        'gross_salary' => round($fixed / 30 * $days, 2), 'total_earned' => round($fixed / 30 * $days, 2),
        'pf' => 880, 'professional_tax' => 0, 'esi' => 150, 'total_deduction' => 1030, 'net_total' => round($fixed / 30 * $days - 1030, 2),
        'salary_split' => [
            ['name' => 'Basic', 'type' => 'earning', 'amount' => 10000, 'selected' => true],
            ['name' => 'VDA', 'type' => 'earning', 'amount' => 1000, 'selected' => true],
            ['name' => 'Special Allowance', 'type' => 'earning', 'amount' => $fixed - 11000, 'selected' => true],
            ['name' => 'Duty Incentive', 'type' => 'earning', 'amount' => 9999, 'selected' => true],
            ['name' => 'Bonus', 'type' => 'earning', 'amount' => 9999, 'selected' => false],
        ],
    ]);
    $item->setRelation('user', $user);
    $item->setRelation('salaryProcessing', $processing);
    return [$item, $processing];
}

function registerReport(array $rows, SalaryProcessing $processing): array
{
    return ['rows' => $rows, 'headings' => WageRegister::headings(), 'groups' => WageRegister::GROUPS,
        'warnings' => WageRegister::warnings($rows), 'processing' => $processing, 'depot' => $processing->depot,
        'monthName' => 'September', 'year' => 2026, 'roleLabel' => 'All'];
}

test('register has exact A to BK headings including separators and single-column groups', function () {
    $headers = WageRegister::headings();
    expect($headers)->toHaveCount(63);
    foreach ([20, 31, 49, 59] as $column) {
        expect($headers[$column])->toBe('');
    }
    expect($headers[1])->toBe('Employee Code')->and($headers[40])->toBe('Basic Salary')
        ->and($headers[50])->toBe('PF 12%')->and($headers[60])->toBe('12% Employer PF');
    $covered = [];
    foreach (WageRegister::GROUPS as [$start, $length]) {
        array_push($covered, ...range($start, $start + $length - 1));
    }
    expect($covered)->toBe(range(0, 62));
});

test('register maps ref codes snapshot attendance fixed and earned components separately', function () {
    [$item] = registerFixture();
    $before = $item->getAttributes();
    $item->salaryProcessing->remarks = 'Internal payroll remarks';
    $row = WageRegister::row($item, 0);
    expect($row)->toHaveCount(63)->and($row[1])->toBe('00012')->and($row[16])->toBe('0000123456')
        ->and($row[14])->toBe('Operations')->and($row[15])->toBe('HDFC Bank')->and($row[17])->toBe('HDFC0000001')
        ->and($row[9])->toBe('0123456789')->and($row[21])->toBe(30)->and($row[22])->toBe(10000.0)
        ->and($row[29])->toBeNull()->and($row[30])->toBe(20000.0)->and($row[34])->toBe(16.0)
        ->and($row[35])->toBe(4.0)->and($row[37])->toBe(20.0)->and($row[42])->toBe(11000 * (20 / 30))
        ->and($row[48])->toBe(13333.33)->and($row[50])->toBe(880.0)->and($row[52])->toBe(150.0)
        ->and($row[60])->toBe(880.0)->and($row[61])->toBe(650.0)->and($row[58])->toBe(12303.33)
        ->and($row[26])->toBeNull()->and($row[32])->toBeNull()->and($row[36])->toBeNull()->and($row[62])->toBeNull()
        ->and(WageRegister::warnings([$row]))->toBe([])->and($item->getAttributes())->toBe($before);
});

test('employer ESI uses fixed gross with inclusive cutoff and never changes saved net', function (float $fixed, float $expected) {
    [$item] = registerFixture($fixed, 33);
    $row = WageRegister::row($item, 0);
    expect($row[61])->toBe($expected)->and($row[39])->toBe(3.0)
        ->and($row[58])->toBe((float) $item->net_total)->and($row[60])->toBe(1320.0);
})->with([[20000.0, 650.0], [21000.0, 682.5], [21001.0, 0.0]]);

test('register respects saved calculated amounts and mapped deductions', function () {
    [$item] = registerFixture();
    $split = $item->salary_split;
    $split[0]['calculated_amount'] = 6000;
    $split[] = ['name' => 'Salary Advance', 'type' => 'deduction', 'amount' => 150, 'calculated_amount' => 100];
    $item->salary_split = $split;
    $row = WageRegister::row($item, 0);
    expect($row[40])->toBe(6000.0)->and($row[54])->toBe(100.0)
        ->and($row[57])->toBe(1030.0)->and(WageRegister::warnings([$row]))->not->toBeEmpty();
});

test('employee fields resolve from each role profile independently of the payroll role', function (string $role) {
    [$item] = registerFixture();
    $class = 'App\\Models\\'.$role.'Profile';
    $profile = new $class();
    $profile->forceFill(['depot_id' => 1, 'account_number' => '000777']);
    $profile->setRelation('designation', new \App\Models\Designation(['name' => 'Employee Designation']));
    $profile->setRelation('location', new \App\Models\Location(['name' => 'Employee Location']));
    $item->user->setRelation('roles', collect([new Role(['name' => $role])]));
    $item->user->setRelation(lcfirst($role).'Profile', $profile);
    $row = WageRegister::row($item, 0);
    expect($row[7])->toBe('Employee Location')->and($row[18])->toBe('Employee Designation')
        ->and($row[16])->toBe('000777')->and($row[14])->toBe('Operations')
        ->and($row[19])->toBe($role === 'Driver' ? 'NLGDriver' : 'NLGAdmin');
})->with(['Driver', 'Housekeeping', 'Controller', 'Supervisor', 'Staff']);

test('category remains blank when depot abbreviation or employee role is missing', function () {
    [$item, $processing] = registerFixture();
    $processing->depot->short_name = '  ';
    expect(WageRegister::row($item, 0)[19])->toBeNull();
    $processing->depot->short_name = 'NLG';
    $item->user->setRelation('roles', collect());
    expect(WageRegister::row($item, 0)[19])->toBeNull();
});

test('report rounds numeric displays while retaining precision blanks and identifiers', function (float $amount, string $display) {
    [$item, $processing] = registerFixture();
    $row = WageRegister::row($item, 0);
    $row[48] = $amount;
    $row[34] = 20.5;
    $report = registerReport([$row], $processing);
    $html = view('salary-report.partials.report-table', ['report' => $report])->render();
    expect($html)->toContain('>'.$display.'</td>', '>21</td>', '00012', '0000123456')
        ->not->toContain('20.50');
    $export = new SalaryReportExport($report);
    $book = new Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->fromArray($export->array(), null, 'A1', true);
    $export->styles($sheet);
    expect($sheet->getCell('AW5')->getValue())->toBe($amount)
        ->and($sheet->getCell('AW5')->getFormattedValue())->toBe($display)
        ->and($sheet->getCell('AI5')->getFormattedValue())->toBe('21')
        ->and($sheet->getCell('AD5')->getValue())->toBeNull();
    $book->disconnectWorksheets();
})->with([
    [1321.49, '1,321'], [1321.5, '1,322'], [1321.58, '1,322'], [650.25, '650'],
    [0.0, '0'], [-1.49, '-1'], [-1.5, '-2'], [-1.51, '-2'],
]);

test('missing profile and legacy placeholder totals remain blank without fabricating zeroes', function () {
    [$item] = registerFixture();
    $item->user->setRelation('staffProfile', null);
    $item->forceFill(['total_earned' => 0, 'net_total' => 0, 'net_salary' => 19000]);
    $row = WageRegister::row($item, 0);
    expect($row[3])->toBeNull()->and($row[16])->toBeNull()->and($row[48])->toBeNull()
        ->and($row[50])->toBeNull()->and($row[58])->toBe(19000.0)->and(WageRegister::warnings([$row]))->not->toBeEmpty();
});

test('screen workbook and paginated PDF use the shared register rows', function () {
    [$item, $processing] = registerFixture();
    $row = WageRegister::row($item, 0);
    $report = registerReport([$row], $processing);
    $export = new SalaryReportExport($report);
    $book = new Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->fromArray($export->array(), null, 'A1', true);
    $export->styles($sheet);
    expect($sheet->getCell('BK4')->getColumn())->toBe('BK')
        ->and($sheet->getCell('V5')->getValue())->toBe(30)->and($sheet->getCell('BJ5')->getValue())->toBe(650.0);
    $export->bindValue($sheet->getCell('B5'), '00012');
    $export->bindValue($sheet->getCell('Q5'), '0000123456');
    expect($sheet->getCell('B5')->getDataType())->toBe('s')->and($sheet->getCell('Q5')->getValue())->toBe('0000123456');
    $html = view('salary-report.partials.report-table', ['report' => $report])->render();
    expect($html)->toContain('00012', 'EMPLOYER CONTRIBUTION', '650');
    $pdf = (new ReflectionMethod(SalaryReportController::class, 'buildPdf'))->invoke(new SalaryReportController(), $report);
    expect($pdf)->toStartWith('%PDF-')
        ->and(preg_match_all('/\/Type\s*\/Page\b/', $pdf))->toBeGreaterThanOrEqual(7);
    if (getenv('WAGE_REGISTER_QA_DIR')) {
        $manyRows = [];
        for ($i = 1; $i <= 70; $i++) {
            $next = $row;
            $next[0] = $i;
            $next[1] = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $manyRows[] = $next;
        }
        $large = registerReport($manyRows, $processing);
        $largePdf = (new ReflectionMethod(SalaryReportController::class, 'buildPdf'))->invoke(new SalaryReportController(), $large);
        expect(preg_match_all('/\/Type\s*\/Page\b/', $largePdf))->toBeGreaterThan(7);
        file_put_contents(getenv('WAGE_REGISTER_QA_DIR').'/register.pdf', $largePdf);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save(getenv('WAGE_REGISTER_QA_DIR').'/register.xlsx');
    }
    $book->disconnectWorksheets();
});
