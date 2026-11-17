<?php

use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\PfFileData;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class);

function pfItem(array $attributes = [], int $year = 2026, int $month = 9): SalaryProcessingItem
{
    $user = new User(['name' => 'Employee']);
    $user->setRelation('roles', collect([new Role(['name' => 'Staff'])]));
    foreach (['driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $relation) $user->setRelation($relation, null);
    $user->setRelation('staffProfile', new StaffProfile(['uan' => ' 000000000123 ', 'depot_id' => 1]));
    $item = new SalaryProcessingItem(array_replace([
        'total_attendance_days' => 20, 'pf' => 880, 'total_earned' => 13333.33,
        'salary_split' => [
            ['name' => 'Basic', 'type' => 'earning', 'amount' => 10000],
            ['name' => 'VDA', 'type' => 'earning', 'amount' => 1000],
            ['name' => 'Allowance', 'type' => 'earning', 'amount' => 9000],
        ],
    ], $attributes));
    $item->setRelation('user', $user);
    $item->setRelation('salaryProcessing', new SalaryProcessing(['year' => $year, 'month' => $month, 'depot_id' => 1]));

    return $item;
}

test('PF data uses saved gross PF and snapshot basic plus VDA', function () {
    $item = pfItem(['pf' => 999.45]);
    $before = $item->getAttributes();
    expect(PfFileData::row($item))->toBe(['000000000123', 'Employee', 13333.33, 7333.33, 999.45, 20.0])
        ->and($item->getAttributes())->toBe($before);
});

test('PF worked shifts cap at actual calendar days and retain fractions', function (int $year, int $month, float $worked, float $days, float $wages) {
    $row = PfFileData::row(pfItem(['total_attendance_days' => $worked], $year, $month));
    expect($row[5])->toEqual($days)->and($row[3])->toBe($wages)->and($row[4])->toBe(880.0);
})->with([
    [2026, 9, 33.75, 30.0, 11000.0], [2026, 12, 33.75, 31.0, 11000.0],
    [2027, 2, 31.5, 28.0, 11000.0], [2028, 2, 31.5, 29.0, 11000.0],
    [2026, 9, 26.25, 26.25, 9625.0], [2026, 9, 0.0, 0.0, 0.0],
]);

test('PF wages use saved calculated snapshot amounts and ignore excluded components', function () {
    $item = pfItem(['salary_split' => [
        ['name' => ' Basic Salary ', 'type' => 'earning', 'amount' => 99999, 'calculated_amount' => 3000.5],
        ['name' => 'VDA', 'type' => 'earning', 'amount' => 99999, 'calculated_amount' => 400.25],
        ['name' => 'Basic', 'type' => 'earning', 'amount' => 99999, 'selected' => false],
        ['name' => 'VDA', 'type' => 'deduction', 'amount' => 99999],
    ]]);
    expect(PfFileData::row($item)[3])->toBe(3400.75);
});

test('PF wages treat an absent basic or VDA component as zero but do not guess missing snapshots', function () {
    $basic = pfItem(['salary_split' => [['name' => 'Basic', 'type' => 'earning', 'amount' => 10000]]]);
    $vda = pfItem(['salary_split' => [['name' => 'VDA', 'type' => 'earning', 'amount' => 1000]]]);
    expect(PfFileData::row($basic)[3])->toBe(6666.67)->and(PfFileData::row($vda)[3])->toBe(666.67)
        ->and(PfFileData::row(pfItem(['salary_split' => []]))[3])->toBeNull();
});

test('PF legacy missing totals remain blank instead of appearing as paid zero wages', function () {
    $row = PfFileData::row(pfItem(['total_earned' => 0, 'net_total' => 0, 'net_salary' => 10000, 'salary_split' => []]));
    expect($row[2])->toBeNull()->and($row[3])->toBeNull()->and($row[4])->toBe(880.0);
});
